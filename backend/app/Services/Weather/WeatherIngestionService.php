<?php

namespace App\Services\Weather;

use App\Models\City;
use App\Models\WeatherLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use App\Events\WeatherLogCreated;
use Illuminate\Support\Facades\Log;

/** Synchronize past-hour Open-Meteo model estimates. These are NOT measured observations. */
class WeatherIngestionService
{
    private const FIELDS = 'temperature_2m,relative_humidity_2m,surface_pressure,wind_speed_10m,wind_direction_10m,precipitation,weather_code';
    private const ALERT_MAX_AGE_HOURS = 3;

    public function ingest(City $city, ?CarbonImmutable $now = null): array
    {
        $now = ($now ?? CarbonImmutable::now('UTC'))->utc();
        $response = Http::timeout(30)->retry(2, 1000)->get('https://api.open-meteo.com/v1/forecast', [
            'latitude' => (float) $city->latitude,
            'longitude' => (float) $city->longitude,
            'hourly' => self::FIELDS,
            'temperature_unit' => 'celsius',
            'wind_speed_unit' => 'ms',
            'precipitation_unit' => 'mm',
            'timezone' => 'UTC',
            'past_days' => 2,
            'forecast_days' => 1,
        ]);

        if (!$response->successful()) {
            return ['ok' => false, 'inserted' => 0, 'skipped' => 0, 'message' => 'Open-Meteo HTTP '.$response->status()];
        }
        $hourly = $response->json('hourly');
        $required = ['time', 'temperature_2m', 'relative_humidity_2m', 'surface_pressure', 'wind_speed_10m', 'wind_direction_10m', 'precipitation', 'weather_code'];
        if (!is_array($hourly)) {
            return ['ok' => false, 'inserted' => 0, 'skipped' => 0, 'message' => 'Missing hourly data'];
        }
        foreach ($required as $field) {
            if (!isset($hourly[$field]) || !is_array($hourly[$field])) {
                return ['ok' => false, 'inserted' => 0, 'skipped' => 0, 'message' => 'Missing hourly field: '.$field];
            }
        }

        $inserted = 0;
        $skipped = 0;
        $failed = 0;
        foreach ($hourly['time'] as $index => $time) {
            $timestamp = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', (string) $time, 'UTC');
            if (!$timestamp || $timestamp->greaterThanOrEqualTo($now->startOfHour())) {
                $skipped++;
                continue;
            }
            foreach ($required as $field) {
                if (!array_key_exists($index, $hourly[$field]) || $hourly[$field][$index] === null || ($field !== 'time' && !is_numeric($hourly[$field][$index]))) {
                    $failed++;
                    continue 2;
                }
            }
            if (!is_numeric($hourly['weather_code'][$index])) {
                $failed++;
                continue;
            }
            $values = [
                'temperature' => round((float) $hourly['temperature_2m'][$index], 2),
                'humidity' => (int) round((float) $hourly['relative_humidity_2m'][$index]),
                'surface_pressure' => round((float) $hourly['surface_pressure'][$index], 2),
                'wind_speed' => round((float) $hourly['wind_speed_10m'][$index], 2),
                'wind_direction' => ((int) round((float) $hourly['wind_direction_10m'][$index]) % 360 + 360) % 360,
                'precipitation' => round(max(0, (float) $hourly['precipitation'][$index]), 2),
                'weather_code' => (int) $hourly['weather_code'][$index],
                'data_source' => 'open_meteo_model',
            ];
            if ($values['humidity'] < 0 || $values['humidity'] > 100 || $values['surface_pressure'] <= 0 || $values['wind_speed'] < 0) {
                $failed++;
                continue;
            }
            // Respect the city's unique (city_id, recorded_at) constraint.
            // Existing synthetic history is deliberately not overwritten.
            $log = WeatherLog::query()->firstOrCreate(
                ['city_id' => $city->id, 'recorded_at' => $timestamp->format('Y-m-d H:i:s')],
                $values
            );
            if ($log->wasRecentlyCreated) {
                $inserted++;
                // Лише свіжі записи: при першому запуску (past_days=2) чи після простою
                // не надсилаємо листи про вчорашню погоду.
                if ($timestamp->greaterThanOrEqualTo($now->subHours(self::ALERT_MAX_AGE_HOURS))) {
                    $this->notifyAlerts($log);
                }
            } else {
                $skipped++;
            }
        }
        
        return [
            'ok' => $failed === 0, 
            'inserted' => $inserted, 
            'skipped' => $skipped, 
            'invalid' => $failed,
            'message' => $failed ? 'Some hourly rows were invalid' : 'OK'
        ];
    } 

    private function notifyAlerts(WeatherLog $log): void
    {
        try {
            WeatherLogCreated::dispatch($log);
        } catch (\Throwable $e) {
            // Збій сповіщень не повинен зупиняти збір погоди.
            Log::error('Alert evaluation failed', [
                'weather_log_id' => $log->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
} 