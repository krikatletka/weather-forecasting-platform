<?php

namespace App\Services\Weather;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PredictionService
{
    /**
     * Джерело погодинних даних для реального прогнозування.
     * Synthetic та архівні записи не використовуються.
     */
    private const DATA_SOURCE = 'open_meteo_model';

    public function __construct(
        private readonly ForecastAlgorithms $algorithms
    ) {
    }

    /**
     * Створює прогноз на майбутню годину,
     * використовуючи лише дані, доступні на момент прогнозування.
     */
    public function predict(
        int $cityId,
        CarbonImmutable $issuedAt,
        int $horizonHours = 1,
        string $algorithm = 'historical_average'
    ): array {
        if ($horizonHours < 1 || $horizonHours > 24) {
            throw new \InvalidArgumentException(
                'Forecast horizon must be 1–24 hours.'
            );
        }

        if (
            !in_array(
                $algorithm,
                ['historical_average', 'similar_conditions'],
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid forecast algorithm.'
            );
        }

        $issuedAt = $issuedAt->utc()->startOfHour();
        $target = $issuedAt->addHours($horizonHours);

        // Беремо лише модельні погодинні дані,
        // які були доступні до моменту прогнозування.
        $rows = DB::table('weather_logs')
            ->where('city_id', $cityId)
            ->where('data_source', self::DATA_SOURCE)
            ->where(
                'recorded_at',
                '<=',
                $issuedAt->format('Y-m-d H:i:s')
            )
            ->where(
                'recorded_at',
                '>=',
                $issuedAt->subDays(90)->format('Y-m-d H:i:s')
            )
            ->orderBy('recorded_at')
            ->get();

        if ($rows->isEmpty()) {
            throw new \RuntimeException(
                'No Open-Meteo model history for this city.'
            );
        }

        $currentRow = $rows->last();

        $currentAt = CarbonImmutable::parse(
            $currentRow->recorded_at,
            'UTC'
        );

        // Не будуємо прогноз на основі застарілих даних.
        if ($currentAt->diffInHours($issuedAt, false) > 3) {
            throw new \RuntimeException(
                'Latest Open-Meteo model data is stale. Run weather:ingest first.'
            );
        }

        $current = $this->features($currentRow);

        if ($algorithm === 'historical_average') {
            $samples = [];

            foreach ($rows as $row) {
                $when = CarbonImmutable::parse(
                    $row->recorded_at,
                    'UTC'
                );

                // Беремо попередні значення для тієї самої години доби.
                if (
                    $when->hour === $target->hour &&
                    $when->lessThan($issuedAt)
                ) {
                    $samples[] = [
                        'temperature' => (float) $row->temperature,
                        'precipitation' => (float) $row->precipitation,
                    ];
                }
            }

            $samples = array_slice($samples, -30);

            $result = $this->algorithms->historicalAverage(
                $samples
            );
        } else {
            $map = [];

            foreach ($rows as $row) {
                $key = CarbonImmutable::parse(
                    $row->recorded_at,
                    'UTC'
                )->format('Y-m-d H:i:s');

                $map[$key] = $row;
            }

            $pairs = [];

            foreach ($rows as $row) {
                $when = CarbonImmutable::parse(
                    $row->recorded_at,
                    'UTC'
                );

                $futureAt = $when->addHours($horizonHours);

                // Обидва значення пари повинні бути відомі
                // до моменту створення поточного прогнозу.
                if (!$futureAt->lessThan($issuedAt)) {
                    continue;
                }

                $futureKey = $futureAt->format('Y-m-d H:i:s');
                $futureRow = $map[$futureKey] ?? null;

                if ($futureRow === null) {
                    continue;
                }

                $pairs[] = [
                    'current' => $this->features($row),
                    'future' => $this->features($futureRow),
                ];
            }

            $result = $this->algorithms->similarConditions(
                $current,
                $pairs
            );
        }

        if ($result === null) {
            throw new \RuntimeException(
                'Insufficient Open-Meteo model history for this prediction method.'
            );
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

        $identity = [
            'city_id' => $cityId,
            'generated_at' => $data['generated_at'],
            'predicted_for_at' => $data['predicted_for_at'],
            'algorithm_type' => $algorithm,
        ];

        $id = DB::transaction(function () use ($identity, $data): int {
            $lockKey = implode(':', array_values($identity));

            DB::select(
                'SELECT pg_advisory_xact_lock(hashtext(?))',
                [$lockKey]
            );

            $existing = DB::table('weather_predictions')
                ->where($identity)
                ->orderBy('id')
                ->first();

            if ($existing === null) {
                return (int) DB::table('weather_predictions')
                    ->insertGetId($data);
            }

            $changed =
                (float) $existing->predicted_temp !==
                (float) $data['predicted_temp']
                ||
                (float) $existing->predicted_precipitation !==
                (float) $data['predicted_precipitation'];

            $updates = [
                'predicted_temp' => $data['predicted_temp'],
                'predicted_precipitation' => $data['predicted_precipitation'],
                'confidence_score' => $data['confidence_score'],
                'updated_at' => now(),
            ];

            if ($changed) {
                $updates['actual_temp'] = null;
                $updates['error_margin'] = null;
            }

            DB::table('weather_predictions')
                ->where('id', $existing->id)
                ->update($updates);

            return (int) $existing->id;
        });

        return [
            'id' => $id,
            'city_id' => $cityId,
            'issued_at' => $data['generated_at'],
            'target' => $data['predicted_for_at'],
            'algorithm' => $algorithm,
        ] + $result;
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
