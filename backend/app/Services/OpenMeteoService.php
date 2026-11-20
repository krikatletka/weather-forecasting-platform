<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenMeteoService
{
    private const BASE_URL = 'https://api.open-meteo.com/v1/forecast';

    public function getHourlyForecast(
        float $latitude,
        float $longitude
    ): array {
        if ($latitude < -90 || $latitude > 90 ||
            $longitude < -180 || $longitude > 180) {
            throw new RuntimeException('Invalid geographic coordinates.');
        }

        $response = Http::acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->retry(3, 500)
            ->get(self::BASE_URL, [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'hourly' => implode(',', [
                    'temperature_2m',
                    'relative_humidity_2m',
                    'precipitation',
                    'surface_pressure',
                    'wind_speed_10m',
                    'wind_direction_10m',
                ]),
                'wind_speed_unit' => 'ms',
                'timezone' => 'GMT',
                'forecast_days' => 2,
            ]);

        $response->throw();

        $data = $response->json();

        if (!is_array($data) ||
            !isset($data['hourly']) ||
            !is_array($data['hourly'])) {
            throw new RuntimeException(
                'Invalid response from Open-Meteo API.'
            );
        }

        return $data;
    }
}
