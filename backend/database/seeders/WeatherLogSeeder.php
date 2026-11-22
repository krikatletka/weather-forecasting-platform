<?php

namespace Database\Seeders;

use App\Models\City;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WeatherLogSeeder extends Seeder
{
    public function run(): void
    {

        $end = CarbonImmutable::parse(
            '2026-11-11 19:00:00',
            'UTC'
        );

        $start = $end->subHours(180 * 24 - 1);

        $cities = City::where('is_active', true)->get();

        foreach ($cities as $city) {
            $batch = [];

            $cityOffset = match ($city->name) {
                'Одеса' => 2.5,
                'Харків' => -1.0,
                'Чернігів' => -1.5,
                'Львів' => -0.5,
                'Івано-Франківськ' => -0.5,
                default => 0.0,
            };

            for ($hour = 0; $hour < 180 * 24; $hour++) {
                $time = $start->addHours($hour);

                $dayOfYear = $time->dayOfYear;

                $seasonal = 11.0 + 13.0 * sin(
                    2 * pi() * ($dayOfYear - 105) / 365.25
                );

                $daily = 4.0 * sin(
                    2 * pi() * ($time->hour - 8) / 24
                );

                $weatherWave = 3.0 * sin($hour / 61.0)
                    + 1.5 * cos($hour / 113.0);

                $temperature = $seasonal
                    + $daily
                    + $weatherWave
                    + $cityOffset;

                $pressure = 1013.0
                    + 10.0 * sin($hour / 85.0)
                    + 4.0 * cos($hour / 170.0);

                $humidity = (int) round(max(
                    25,
                    min(
                        100,
                        68 - $daily * 2
                        + 15 * sin($hour / 43.0)
                    )
                ));

                $windSpeed = max(
                    0,
                    4.0
                    + 2.0 * sin($hour / 29.0)
                    + 1.5 * cos($hour / 67.0)
                );

                $windDirection = (
                    (int) round(
                        180
                        + 100 * sin($hour / 35.0)
                        + 60 * cos($hour / 91.0)
                    ) + 360
                ) % 360;

                $rainFactor = sin($hour / 57.0)
                    + cos($hour / 23.0);

                $precipitation = $rainFactor > 1.15
                    ? ($rainFactor - 1.15) * 3.0
                    : 0.0;

                $weatherCode = $precipitation > 0
                    ? 61
                    : ($humidity > 80 ? 3 : 1);

                $timestamp = $time->format('Y-m-d H:i:s');

                $batch[] = [
                    'city_id' => $city->id,
                    'recorded_at' => $timestamp,
                    'temperature' => round($temperature, 2),
                    'surface_pressure' => round($pressure, 2),
                    'humidity' => $humidity,
                    'wind_speed' => round($windSpeed, 2),
                    'wind_direction' => $windDirection,
                    'precipitation' => round($precipitation, 2),
                    'weather_code' => $weatherCode,
                    'data_source' => 'synthetic',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                if (count($batch) >= 500) {
                    DB::table('weather_logs')->insertOrIgnore($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                DB::table('weather_logs')->insertOrIgnore($batch);
            }

            $this->command?->info(
                "Generated weather history for {$city->name}"
            );
        }
    }
}
