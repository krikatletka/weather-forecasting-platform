<?php

namespace App\Services\Weather;

/** Pure prediction methods, independent of Laravel/DB for easy unit testing. */
class ForecastAlgorithms
{
    /** @param array<int,array{temperature:float,precipitation:float}> $observations */
    public function historicalAverage(array $observations): ?array
    {
        if (count($observations) < 3) {
            return null;
        }
        $temperatures = array_column($observations, 'temperature');
        $precipitation = array_column($observations, 'precipitation');
        return [
            'temperature' => round(array_sum($temperatures) / count($temperatures), 2),
            'precipitation' => round(array_sum($precipitation) / count($precipitation), 2),
            'confidence' => $this->confidence($temperatures),
            'samples' => count($observations),
        ];
    }

    /**
     * kNN with inverse-distance weights and a delta forecast.
     * @param array<string,float> $current
     * @param array<int,array{current:array<string,float>,future:array<string,float>}> $pairs
     */
    public function similarConditions(array $current, array $pairs, int $k = 5): ?array
    {
        if (count($pairs) < 3) {
            return null;
        }
        $distances = [];
        foreach ($pairs as $pair) {
            $past = $pair['current'];
            $directionDiff = abs($current['wind_direction'] - $past['wind_direction']);
            $directionDiff = min($directionDiff, 360 - $directionDiff);
            $distance = sqrt(
                (($current['temperature'] - $past['temperature']) / 8) ** 2 +
                (($current['surface_pressure'] - $past['surface_pressure']) / 15) ** 2 +
                (($current['humidity'] - $past['humidity']) / 25) ** 2 +
                (($current['wind_speed'] - $past['wind_speed']) / 5) ** 2 +
                ($directionDiff / 90) ** 2
            );
            $distances[] = ['distance' => $distance, 'pair' => $pair];
        }
        usort($distances, fn ($a, $b) => $a['distance'] <=> $b['distance']);
        $neighbors = array_slice($distances, 0, max(1, $k));
        $sumWeights = 0.0;
        $tempDelta = 0.0;
        $rain = 0.0;
        $deltaValues = [];
        foreach ($neighbors as $neighbor) {
            $weight = 1 / max(0.05, $neighbor['distance']);
            $pair = $neighbor['pair'];
            $delta = $pair['future']['temperature'] - $pair['current']['temperature'];
            $sumWeights += $weight;
            $tempDelta += $weight * $delta;
            $rain += $weight * $pair['future']['precipitation'];
            $deltaValues[] = $delta;
        }
        return [
            'temperature' => round($current['temperature'] + $tempDelta / $sumWeights, 2),
            'precipitation' => round(max(0, $rain / $sumWeights), 2),
            'confidence' => $this->confidence($deltaValues),
            'samples' => count($neighbors),
        ];
    }

    private function confidence(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn ($x) => ($x - $mean) ** 2, $values)) / count($values);
        // Heuristic score; not a calibrated probability.
        return round(max(0.05, min(0.95, 1 / (1 + sqrt($variance) / 5))), 4);
    }
}
