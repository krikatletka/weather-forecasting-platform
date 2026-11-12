<?php

namespace App\Services\Weather;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PredictionService
{
    public function __construct(private readonly ForecastAlgorithms $algorithms) {}

    /** Predict a future hour using only observations available at issuance time. */
    public function predict(int $cityId, CarbonImmutable $issuedAt, int $horizonHours = 1, string $algorithm = 'historical_average'): array
    {
        if ($horizonHours < 1 || $horizonHours > 24) {
            throw new \InvalidArgumentException('Forecast horizon must be 1–24 hours.');
        }
        if (!in_array($algorithm, ['historical_average', 'similar_conditions'], true)) {
            throw new \InvalidArgumentException('Invalid forecast algorithm.');
        }
        $issuedAt = $issuedAt->utc()->startOfHour();
        $target = $issuedAt->addHours($horizonHours);
        $rows = DB::table('weather_logs')
            ->where('city_id', $cityId)
            ->where('recorded_at', '<=', $issuedAt->format('Y-m-d H:i:s'))
            ->where('recorded_at', '>=', $issuedAt->subDays(90)->format('Y-m-d H:i:s'))
            ->orderBy('recorded_at')
            ->get();
        if ($rows->isEmpty()) {
            throw new \RuntimeException('No historical observations for this city.');
        }
        $currentRow = $rows->last();
        $currentAt = CarbonImmutable::parse($currentRow->recorded_at, 'UTC');
        if ($currentAt->diffInHours($issuedAt, false) > 3) {
            throw new \RuntimeException('Latest observation is stale. Ingest current weather before forecasting; use --at for historical demo.');
        }
        $current = $this->features($currentRow);
        if ($algorithm === 'historical_average') {
            // Same target hour on prior days, keeping only data available at issue time.
            $samples = [];
            foreach ($rows as $row) {
                $when = CarbonImmutable::parse($row->recorded_at, 'UTC');
                if ($when->hour === $target->hour && $when->lessThan($issuedAt)) {
                    $samples[] = [
                        'temperature' => (float) $row->temperature,
                        'precipitation' => (float) $row->precipitation,
                    ];
                }
            }
            $samples = array_slice($samples, -30);
            $result = $this->algorithms->historicalAverage($samples);
        } else {
            $map = [];
            foreach ($rows as $row) {
                $map[CarbonImmutable::parse($row->recorded_at, 'UTC')->format('Y-m-d H:i:s')] = $row;
            }
            $pairs = [];
            foreach ($rows as $row) {
                $when = CarbonImmutable::parse($row->recorded_at, 'UTC');
                $futureAt = $when->addHours($horizonHours);
                // Strictly before issue time prevents looking at future outcomes.
                if (!$futureAt->lessThan($issuedAt)) {
                    continue;
                }
                $future = $map[$futureAt->format('Y-m-d H:i:s')] ?? null;
                if ($future === null) {
                    continue;
                }
                $pairs[] = ['current' => $this->features($row), 'future' => $this->features($future)];
            }
            $result = $this->algorithms->similarConditions($current, $pairs);
        }
        if ($result === null) {
            throw new \RuntimeException('Insufficient historical data for this prediction method.');
        }
        $data = [
            'city_id' => $cityId,
            'generated_at' => $issuedAt->format('Y-m-d H:i:s'),
            'predicted_for_at' => $target->format('Y-m-d H:i:s'),
            'predicted_temp' => $result['temperature'],
            'predicted_precipitation' => $result['precipitation'],
            'algorithm_type' => $algorithm,
            'confidence_score' => $result['confidence'],
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $id = DB::table('weather_predictions')->insertGetId($data);
        return ['id' => $id, 'city_id' => $cityId, 'issued_at' => $data['generated_at'], 'target' => $data['predicted_for_at'], 'algorithm' => $algorithm] + $result;
    }

    private function features(object $row): array
    {
        return [
            'temperature' => (float) $row->temperature,
            'surface_pressure' => (float) $row->surface_pressure,
            'humidity' => (float) $row->humidity,
            'wind_speed' => (float) $row->wind_speed,
            'wind_direction' => (float) $row->wind_direction,
            'precipitation' => (float) $row->precipitation,
        ];
    }
}
