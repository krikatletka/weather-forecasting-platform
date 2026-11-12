<?php

namespace Tests\Unit;

use App\Services\Weather\ForecastAlgorithms;
use PHPUnit\Framework\TestCase;

class ForecastAlgorithmsTest extends TestCase
{
    public function test_historical_average_requires_at_least_three_observations(): void
    {
        $this->assertNull((new ForecastAlgorithms())->historicalAverage([
            ['temperature' => 1.0, 'precipitation' => 0.0],
        ]));
    }

    public function test_historical_average_calculates_temperature_and_rain(): void
    {
        $result = (new ForecastAlgorithms())->historicalAverage([
            ['temperature' => 8.0, 'precipitation' => 0.0],
            ['temperature' => 10.0, 'precipitation' => 1.5],
            ['temperature' => 12.0, 'precipitation' => 0.0],
        ]);
        $this->assertSame(10.0, $result['temperature']);
        $this->assertSame(0.5, $result['precipitation']);
        $this->assertSame(3, $result['samples']);
    }

    public function test_knn_predicts_using_future_changes_and_nonnegative_rain(): void
    {
        $current = ['temperature' => 12.0, 'surface_pressure' => 1013.0, 'humidity' => 70.0, 'wind_speed' => 4.0, 'wind_direction' => 359.0];
        $pairs = [];
        foreach ([1.0, 2.0, 3.0] as $delta) {
            $pairs[] = [
                'current' => $current,
                'future' => ['temperature' => 12.0 + $delta, 'precipitation' => 0.0],
            ];
        }
        $result = (new ForecastAlgorithms())->similarConditions($current, $pairs);
        $this->assertSame(14.0, $result['temperature']);
        $this->assertSame(0.0, $result['precipitation']);
        $this->assertSame(3, $result['samples']);
    }
}
