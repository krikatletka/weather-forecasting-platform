<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Weather\BacktestingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BacktestingController extends Controller
{
    public function index(
        Request $request,
        BacktestingService $backtesting
    ): JsonResponse {
        $filters = $request->validate([
            'city_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $cityId = isset($filters['city_id'])
            ? (int) $filters['city_id']
            : null;

        try {
            $result = $backtesting->run($cityId, 6);

            return response()->json([
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Не вдалося виконати тестування алгоритмів.',
            ], 500);
        }
    }
}
