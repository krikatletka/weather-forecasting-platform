<?php

namespace App\Services\Weather;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class BacktestingService
{
    public function __construct(
        private readonly ForecastAlgorithms $algorithms
    ) {
    }

    /**
     * Порівняння алгоритмів на архівних погодних даних.
     *
     * Використовуються лише записи до моменту прогнозування.
     * Результат перевіряється за архівним значенням у майбутньому.
     */
    public function run(?int $cityId = null, int $strideHours = 6): array
    {
        if ($strideHours < 1 || $strideHours > 24) {
            throw new \InvalidArgumentException(
                'Stride must be between 1 and 24 hours.'
            );
        }

        $cityQuery = DB::table('cities')
            ->whereIn('id', function ($query) {
                $query->select('city_id')
                    ->from('weather_archive_logs')
                    ->distinct();
            });

        if ($cityId !== null) {
            $cityQuery->where('id', $cityId);
        }

        $cities = $cityQuery->orderBy('id')->get();

        $horizons = [1, 6, 12, 24];

        $metrics = [];

        foreach (['historical_average', 'similar_conditions'] as $algorithm) {
            foreach ($horizons as $horizon) {
                $metrics[$algorithm][$horizon] = [
                    'count' => 0,
                    'absolute_error_sum' => 0.0,
                    'squared_error_sum' => 0.0,
                ];
            }
        }

        $testedCities = 0;

        foreach ($cities as $city) {
            $archive = DB::table('weather_archive_logs')
                ->where('city_id', $city->id)
                ->orderBy('recorded_at')
                ->get();

            if ($archive->count() < 200) {
                continue;
            }

            $rows = $archive->all();
            $map = [];

            foreach ($rows as $row) {
                $map[$row->recorded_at] = $row;
            }

            $firstAt = CarbonImmutable::parse(
                $rows[0]->recorded_at,
                'UTC'
            );

            $lastAt = CarbonImmutable::parse(
                $rows[count($rows) - 1]->recorded_at,
                'UTC'
            );

            // Перші 7 днів залишаємо для накопичення історії.
            $issuedAt = $firstAt->addDays(7)->startOfHour();

            // Залишаємо запас для прогнозу на 24 години.
            $lastIssueAt = $lastAt->subHours(24);

            $cityTested = false;

            while ($issuedAt->lessThanOrEqualTo($lastIssueAt)) {
                $issuedString = $issuedAt->format('Y-m-d H:i:s');

                // Поточне значення на момент створення прогнозу.
                $currentRow = $map[$issuedString] ?? null;

                if ($currentRow === null) {
                    $issuedAt = $issuedAt->addHours($strideHours);
                    continue;
                }

                $current = $this->features($currentRow);

                foreach ($horizons as $horizon) {
                    $targetAt = $issuedAt->addHours($horizon);
                    $targetString = $targetAt->format('Y-m-d H:i:s');

                    // Фактичне значення використовується лише для оцінки.
                    $actualRow = $map[$targetString] ?? null;

                    if ($actualRow === null) {
                        continue;
                    }

                    // Історія містить тільки записи до issuedAt.
                    $history = [];

                    foreach ($rows as $row) {
                        if ($row->recorded_at < $issuedString) {
                            $history[] = $row;
                        } else {
                            break;
                        }
                    }

                    // 1. Historical Average
                    $samples = [];

                    foreach ($history as $row) {
                        $when = CarbonImmutable::parse(
                            $row->recorded_at,
                            'UTC'
                        );

                        if ($when->hour === $targetAt->hour) {
                            $samples[] = [
                                'temperature' => (float) $row->temperature,
                                'precipitation' => (float) $row->precipitation,
                            ];
                        }
                    }

                    $samples = array_slice($samples, -30);

                    $averageResult = $this->algorithms
                        ->historicalAverage($samples);

                    if ($averageResult !== null) {
                        $this->addResult(
                            $metrics['historical_average'][$horizon],
                            $averageResult['temperature'],
                            (float) $actualRow->temperature
                        );

                        $cityTested = true;
                    }

                    // 2. Similar Conditions
                    $pairs = [];

                    foreach ($history as $row) {
                        $when = CarbonImmutable::parse(
                            $row->recorded_at,
                            'UTC'
                        );

                        $futureAt = $when->addHours($horizon);

                        // Обидві частини пари мають бути відомі
                        // до моменту створення прогнозу.
                        if (!$futureAt->lessThan($issuedAt)) {
                            continue;
                        }

                        $futureString = $futureAt->format(
                            'Y-m-d H:i:s'
                        );

                        $futureRow = $map[$futureString] ?? null;

                        if ($futureRow === null) {
                            continue;
                        }

                        $pairs[] = [
                            'current' => $this->features($row),
                            'future' => $this->features($futureRow),
                        ];
                    }

                    $similarResult = $this->algorithms
                        ->similarConditions($current, $pairs);

                    if ($similarResult !== null) {
                        $this->addResult(
                            $metrics['similar_conditions'][$horizon],
                            $similarResult['temperature'],
                            (float) $actualRow->temperature
                        );

                        $cityTested = true;
                    }
                }

                $issuedAt = $issuedAt->addHours($strideHours);
            }

            if ($cityTested) {
                $testedCities++;
            }
        }

        $results = [];

        foreach ($metrics as $algorithm => $horizonMetrics) {
            foreach ($horizonMetrics as $horizon => $metric) {
                $count = $metric['count'];

                $results[] = [
                    'algorithm' => $algorithm,
                    'horizon_hours' => $horizon,
                    'count' => $count,
                    'mae' => $count > 0
                        ? round(
                            $metric['absolute_error_sum'] / $count,
                            3
                        )
                        : null,
                    'rmse' => $count > 0
                        ? round(
                            sqrt(
                                $metric['squared_error_sum'] / $count
                            ),
                            3
                        )
                        : null,
                ];
            }
        }

        return [
            'tested_cities' => $testedCities,
            'stride_hours' => $strideHours,
            'results' => $results,
        ];
    }

    private function addResult(
        array &$metric,
        float $predicted,
        float $actual
    ): void {
        $error = abs($actual - $predicted);

        $metric['count']++;
        $metric['absolute_error_sum'] += $error;
        $metric['squared_error_sum'] += $error ** 2;
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
