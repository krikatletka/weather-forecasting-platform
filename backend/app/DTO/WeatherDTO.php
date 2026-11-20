<?php

namespace App\DTO;

use Carbon\Carbon;
use InvalidArgumentException;

readonly class WeatherDTO
{
    public function __construct(
        public string $recordedAt,
        public float $temperature,
        public int $humidity,
        public float $precipitation,
        public float $surfacePressure,
        public float $windSpeed,
        public int $windDirection,
    ) {
    }

    public static function fromHourly(
        array $hourly,
        int $index
    ): self {
        $fields = [
            'time',
            'temperature_2m',
            'relative_humidity_2m',
            'precipitation',
            'surface_pressure',
            'wind_speed_10m',
            'wind_direction_10m',
        ];

        foreach ($fields as $field) {
            if (
                !isset($hourly[$field]) ||
                !array_key_exists($index, $hourly[$field]) ||
                $hourly[$field][$index] === null
            ) {
                throw new InvalidArgumentException(
                    "Missing weather data: {$field} at index {$index}"
                );
            }
        }

        $humidity = $hourly['relative_humidity_2m'][$index];
        $precipitation = $hourly['precipitation'][$index];
        $windSpeed = $hourly['wind_speed_10m'][$index];
        $windDirection = $hourly['wind_direction_10m'][$index];

        foreach ([
            'humidity' => $humidity,
            'precipitation' => $precipitation,
            'wind_speed' => $windSpeed,
            'wind_direction' => $windDirection,
        ] as $name => $value) {
            if (!is_numeric($value) || !is_finite((float) $value)) {
                throw new InvalidArgumentException(
                    "Invalid weather value: {$name}"
                );
            }
        }

        if ($humidity < 0 || $humidity > 100) {
            throw new InvalidArgumentException(
                'Humidity must be between 0 and 100.'
            );
        }

        if ($precipitation < 0 || $windSpeed < 0) {
            throw new InvalidArgumentException(
                'Precipitation and wind speed cannot be negative.'
            );
        }

        if ($windDirection < 0 || $windDirection >= 360) {
            throw new InvalidArgumentException(
                'Wind direction must be between 0 and 359 degrees.'
            );
        }

        return new self(
            recordedAt: Carbon::parse(
                $hourly['time'][$index],
                'UTC'
            )->format('Y-m-d H:i:s'),

            temperature: (float) $hourly['temperature_2m'][$index],
            humidity: (int) $hourly['relative_humidity_2m'][$index],
            precipitation: (float) $hourly['precipitation'][$index],
            surfacePressure: (float) $hourly['surface_pressure'][$index],
            windSpeed: (float) $hourly['wind_speed_10m'][$index],
            windDirection: (int) $hourly['wind_direction_10m'][$index],
        );
    }

    public function toArray(): array
    {
        return [
            'recorded_at' => $this->recordedAt,
            'temperature' => $this->temperature,
            'humidity' => $this->humidity,
            'precipitation' => $this->precipitation,
            'surface_pressure' => $this->surfacePressure,
            'wind_speed' => $this->windSpeed,
            'wind_direction' => $this->windDirection,
        ];
    }
}
