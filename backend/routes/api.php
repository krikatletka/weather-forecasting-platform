<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlertRuleController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\WeatherController;
use App\Http\Controllers\Api\BacktestingController;

// Авторизація
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Публічні маршрути
Route::get('/cities', [CityController::class, 'index']);
Route::get('/weather/{cityId}', [WeatherController::class, 'current']);
Route::get('/weather/{cityId}/history', [WeatherController::class, 'history']);

Route::prefix('predictions')->group(function () {
    Route::get('/accuracy', [AnalyticsController::class, 'accuracy']);
    Route::get('/comparison', [AnalyticsController::class, 'comparison']);
    Route::get('/backtesting', [BacktestingController::class, 'index']);
});

// Маршрути для авторизованих користувачів
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/alerts', [AlertRuleController::class, 'index']);
    Route::post('/alerts', [AlertRuleController::class, 'store']);
    Route::put('/alerts/{id}', [AlertRuleController::class, 'update']);
    Route::delete('/alerts/{id}', [AlertRuleController::class, 'destroy']);
});
