<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalyticsController;

Route::prefix('predictions')->group(function () {
    Route::get('/accuracy', [AnalyticsController::class, 'accuracy']);
    Route::get('/comparison', [AnalyticsController::class, 'comparison']);
});