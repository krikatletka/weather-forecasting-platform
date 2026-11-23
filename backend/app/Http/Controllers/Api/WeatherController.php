<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\WeatherLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    /**
     * Отримання актуальної погоди з Open-Meteo
     * та збереження результату в базі даних.
     */
    public function current(int $cityId): JsonResponse
    {
        $city = City::where('is_active', true)->find($cityId);

        if (!$city) {
            return response()->json([
                'message' => 'Місто не знайдено',
            ], 404);
        }

        try {
            $response = Http::timeout(15)->get(
                'https://api.open-meteo.com/v1/forecast',
                [
                    'latitude' => $city->latitude,
                    'longitude' => $city->longitude,
                    'current' => implode(',', [
                        'temperature_2m',
                        'relative_humidity_2m',
                        'apparent_temperature',
                        'surface_pressure',
                        'wind_speed_10m',
                        'wind_direction_10m',
                        'precipitation',
                        'weather_code',
                    ]),
                    'timezone' => 'UTC',
                ]
            );

            if (!$response->successful()) {
                return response()->json([
                    'message' => 'Не вдалося отримати погодні дані від Open-Meteo',
                ], 502);
            }

            $current = $response->json('current');

            if (!$current) {
                return response()->json([
                    'message' => 'Open-Meteo не повернув погодні дані',
                ], 502);
            }

            // Зберігаємо отримані показники в PostgreSQL.
            $weather = WeatherLog::query()->firstOrCreate(
                [
                    'city_id' => $city->id,
                    'recorded_at' => $current['time'],
                ],
                [
                    'temperature' => $current['temperature_2m'],
                    'surface_pressure' => $current['surface_pressure'],
                    'humidity' => $current['relative_humidity_2m'],
                    'wind_speed' => $current['wind_speed_10m'],
                    'wind_direction' => $current['wind_direction_10m'],
                    'precipitation' => $current['precipitation'],
                    'weather_code' => $current['weather_code'],
                    'data_source' => 'open-meteo',
                ]
            );

            return response()->json([
                'city' => $city,
                'weather' => $weather,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Помилка під час отримання погодних даних',
            ], 500);
        }
    }

    /**
     * Отримання історії погоди міста.
     */
    public function history(Request $request, int $cityId): JsonResponse
    {
        $city = City::where('is_active', true)->find($cityId);

        if (!$city) {
            return response()->json([
                'message' => 'Місто не знайдено',
            ], 404);
        }

        $validated = $request->validate([
            'hours' => ['sometimes', 'integer', 'min:1', 'max:4320'],
        ]);

        $hours = $validated['hours'] ?? 24;

        $latestRecord = WeatherLog::where('city_id', $cityId)
            ->latest('recorded_at')
            ->first();

        if (!$latestRecord) {
            return response()->json([
                'message' => 'Історія погоди відсутня',
            ], 404);
        }

        $from = $latestRecord->recorded_at->copy()->subHours($hours - 1);

        $weather = WeatherLog::where('city_id', $cityId)
            ->whereBetween('recorded_at', [
                $from,
                $latestRecord->recorded_at,
            ])
            ->orderBy('recorded_at')
            ->get();

        return response()->json([
            'city' => $city->name,
            'hours' => $hours,
            'count' => $weather->count(),
            'data' => $weather,
        ]);
    }
}
