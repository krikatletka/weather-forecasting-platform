<?php

namespace App\Http\Controllers;

use App\Services\Weather\PredictionAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly PredictionAnalyticsService $analytics)
    {
    }

    public function accuracy(Request $request): JsonResponse
    {
        $filters = $this->validateFilters($request);
        return response()->json(['data' => $this->analytics->getAccuracy($filters)]);
    }

    public function comparison(Request $request): JsonResponse
    {
        $filters = $this->validateFilters($request);
        return response()->json(['data' => $this->analytics->compareAlgorithms($filters)]);
    }

    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'city_id'   => ['nullable', 'integer'],
            'algorithm' => ['nullable', 'string', 'in:historical_average,similar_conditions'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);
    }
}