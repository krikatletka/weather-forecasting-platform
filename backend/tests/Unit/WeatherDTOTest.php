<?php

namespace Tests\Unit;

use App\DTO\WeatherDTO;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class WeatherDTOTest extends TestCase
{
    private function validHourlyData(): array
    {
        return [
            'time' => ['2026-11-18T12:00'],
            'temperature_2m' => [5.5],
            'relative_humidity_2m' => [85],
            'precipitation' => [0.1],
            'surface_pressure' => [1000.0],
            'wind_speed_10m' => [3.2],
            'wind_direction_10m' => [180],
        ];
    }

    public function test_converts_hourly_data_to_dto(): void
    {
        $weather = WeatherDTO::fromHourly(
            $this->validHourlyData(),
            0
        );

        $this->assertSame([
            'recorded_at' => '2026-11-18 12:00:00',
            'temperature' => 5.5,
            'humidity' => 85,
            'precipitation' => 0.1,
            'surface_pressure' => 1000.0,
            'wind_speed' => 3.2,
            'wind_direction' => 180,
        ], $weather->toArray());
    }

    public function test_rejects_invalid_humidity(): void
    {
        $data = $this->validHourlyData();
        $data['relative_humidity_2m'][0] = 150;

        $this->expectException(InvalidArgumentException::class);

        WeatherDTO::fromHourly($data, 0);
    }

    public function test_rejects_negative_precipitation(): void
    {
        $data = $this->validHourlyData();
        $data['precipitation'][0] = -1;

        $this->expectException(InvalidArgumentException::class);

        WeatherDTO::fromHourly($data, 0);
    }

    public function test_rejects_invalid_wind_direction(): void
    {
        $data = $this->validHourlyData();
        $data['wind_direction_10m'][0] = 400;

        $this->expectException(InvalidArgumentException::class);

        WeatherDTO::fromHourly($data, 0);
    }

    public function test_rejects_missing_data(): void
    {
        $data = $this->validHourlyData();
        unset($data['temperature_2m']);

        $this->expectException(InvalidArgumentException::class);

        WeatherDTO::fromHourly($data, 0);
    }
}
