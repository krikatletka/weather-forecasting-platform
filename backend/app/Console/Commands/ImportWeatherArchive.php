<?php

namespace App\Console\Commands;

use App\Models\City;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class ImportWeatherArchive extends Command
{
    protected $signature = 'weather:import-archive {--days=30}';

    protected $description = 'Import historical ERA5 weather data for all active cities';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1 || $days > 365) {
            $this->error('Days must be between 1 and 365.');
            return self::FAILURE;
        }

        // ERA5 data has approximately 5 days of delay.
        // Use a 6-day buffer to avoid incomplete recent data.
        $endDate = CarbonImmutable::now('UTC')
            ->startOfDay()
            ->subDays(6);

        $startDate = $endDate->subDays($days - 1);

        $this->info('Importing ERA5 archive weather data.');
        $this->info("Period: {$startDate->toDateString()} - {$endDate->toDateString()}");

        $cities = City::where('is_active', true)->orderBy('id')->get();

        if ($cities->isEmpty()) {
            $this->error('No active cities found.');
            return self::FAILURE;
        }

        $totalRows = 0;
        $failedCities = 0;

        foreach ($cities as $city) {
            $this->line("Importing: {$city->name}");

            try {
                $response = Http::timeout(60)
                    ->retry(2, 1000)
                    ->get('https://archive-api.open-meteo.com/v1/archive', [
                        'latitude' => (float) $city->latitude,
                        'longitude' => (float) $city->longitude,
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'hourly' => implode(',', [
                            'temperature_2m',
                            'relative_humidity_2m',
                            'surface_pressure',
                            'wind_speed_10m',
                            'wind_direction_10m',
                            'precipitation',
                            'weather_code',
                        ]),
                        'models' => 'era5',
                        'temperature_unit' => 'celsius',
                        'wind_speed_unit' => 'ms',
                        'precipitation_unit' => 'mm',
                        'timezone' => 'UTC',
                    ]);

                if (!$response->successful()) {
                    $reason = $response->json('reason')
                        ?? 'HTTP ' . $response->status();

                    $this->error("Failed for {$city->name}: {$reason}");
                    $failedCities++;
                    continue;
                }

                $hourly = $response->json('hourly');

                $fields = [
                    'time',
                    'temperature_2m',
                    'relative_humidity_2m',
                    'surface_pressure',
                    'wind_speed_10m',
                    'wind_direction_10m',
                    'precipitation',
                    'weather_code',
                ];

                $valid = is_array($hourly);

                foreach ($fields as $field) {
                    if (
                        !isset($hourly[$field]) ||
                        !is_array($hourly[$field])
                    ) {
                        $valid = false;
                        break;
                    }
                }

                if (!$valid) {
                    $this->error("Invalid hourly response for {$city->name}.");
                    $failedCities++;
                    continue;
                }

                $rows = [];
                $count = count($hourly['time']);

                for ($i = 0; $i < $count; $i++) {
                    $values = [];

                    foreach ($fields as $field) {
                        if (
                            !array_key_exists($i, $hourly[$field]) ||
                            !is_numeric($hourly[$field][$i])
                            && $field !== 'time'
                        ) {
                            continue 2;
                        }

                        $values[$field] = $hourly[$field][$i];
                    }

                    $recordedAt = CarbonImmutable::parse(
                        $values['time'],
                        'UTC'
                    );

                    $temperature = (float) $values['temperature_2m'];
                    $humidity = (int) round(
                        (float) $values['relative_humidity_2m']
                    );
                    $pressure = (float) $values['surface_pressure'];
                    $windSpeed = (float) $values['wind_speed_10m'];
                    $windDirection = (int) round(
                        (float) $values['wind_direction_10m']
                    );
                    $precipitation = (float) $values['precipitation'];
                    $weatherCode = (int) $values['weather_code'];

                    if (
                        $humidity < 0 || $humidity > 100 ||
                        $pressure <= 0 ||
                        $windSpeed < 0 ||
                        $precipitation < 0
                    ) {
                        continue;
                    }

                    $rows[] = [
                        'city_id' => $city->id,
                        'recorded_at' => $recordedAt->format(
                            'Y-m-d H:i:s'
                        ),
                        'temperature' => round($temperature, 2),
                        'surface_pressure' => round($pressure, 2),
                        'humidity' => $humidity,
                        'wind_speed' => round($windSpeed, 2),
                        'wind_direction' => (
                            ($windDirection % 360) + 360
                        ) % 360,
                        'precipitation' => round($precipitation, 2),
                        'weather_code' => $weatherCode,
                        'data_source' => 'open_meteo_era5',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Insert new records or update existing archive records.
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('weather_archive_logs')->upsert(
                        $chunk,
                        ['city_id', 'recorded_at'],
                        [
                            'temperature',
                            'surface_pressure',
                            'humidity',
                            'wind_speed',
                            'wind_direction',
                            'precipitation',
                            'weather_code',
                            'data_source',
                            'updated_at',
                        ]
                    );
                }

                $totalRows += count($rows);

                $this->info(
                    "{$city->name}: processed " . count($rows) . ' hourly records.'
                );
            } catch (Throwable $e) {
                $this->error(
                    "Error for {$city->name}: {$e->getMessage()}"
                );

                $failedCities++;
            }
        }

        $this->newLine();
        $this->info("Total processed records: {$totalRows}");
        $this->info("Failed cities: {$failedCities}");

        return $failedCities === 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
