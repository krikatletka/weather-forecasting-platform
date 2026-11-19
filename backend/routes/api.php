<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Api\AlertRuleController;

// Prediction analytics
Route::prefix('predictions')->group(function () {
    Route::get('/accuracy', [AnalyticsController::class, 'accuracy']);
    Route::get('/comparison', [AnalyticsController::class, 'comparison']);
});

// Weather alerts
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/alerts', [AlertRuleController::class, 'index']);
    Route::post('/alerts', [AlertRuleController::class, 'store']);
    Route::put('/alerts/{id}', [AlertRuleController::class, 'update']);
    Route::delete('/alerts/{id}', [AlertRuleController::class, 'destroy']);
});