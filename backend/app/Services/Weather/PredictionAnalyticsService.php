<?php

namespace App\Services\Weather;

use Illuminate\Support\Facades\DB;

class PredictionAnalyticsService
{
    public function getAccuracy(array $filters = []): array
    {
        $query = $this->buildBaseQuery($filters);
        $overall = $query->selectRaw('COUNT(*) as total_checked, AVG(error_margin) as mae')->first();

        $byAlgorithm = $this->buildBaseQuery($filters)
            ->selectRaw('algorithm_type, COUNT(*) as total_checked, AVG(error_margin) as mae')
            ->groupBy('algorithm_type')
            ->get()
            ->map(function ($row) {
                return [
                    'algorithm' => $row->algorithm_type,
                    'total_checked' => (int) $row->total_checked,
                    'mae' => $row->mae !== null ? round((float) $row->mae, 2) : null,
                ];
            });

        return [
            'overall' => [
                'total_checked' => (int) ($overall->total_checked ?? 0),
                'mae' => $overall->mae !== null ? round((float) $overall->mae, 2) : null,
            ],
            'by_algorithm' => $byAlgorithm,
        ];
    }

    public function compareAlgorithms(array $filters = []): array
    {
        $stats = $this->buildBaseQuery($filters)
            ->selectRaw('algorithm_type, COUNT(*) as total_checked, AVG(error_margin) as mae')
            ->groupBy('algorithm_type')
            ->orderBy('mae')
            ->get();

        $best = $stats->first() ? $stats->first()->algorithm_type : null;

        return [
            'best_algorithm' => $best,
            'ranking' => $stats->map(function ($row) {
                return [
                    'algorithm' => $row->algorithm_type,
                    'mae' => $row->mae !== null ? round((float) $row->mae, 2) : null,
                    'total_checked' => (int) $row->total_checked,
                ];
            })->values()->toArray(),
        ];
    }

    private function buildBaseQuery(array $filters)
    {
        return DB::table('weather_predictions')
            ->whereNotNull('actual_temp')
            ->when($filters['city_id'] ?? null, fn($q, $v) => $q->where('city_id', $v))
            ->when($filters['algorithm'] ?? null, fn($q, $v) => $q->where('algorithm_type', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->where('predicted_for_at', '>=', $v . ' 00:00:00'))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->where('predicted_for_at', '<=', $v . ' 23:59:59'));
    }
}