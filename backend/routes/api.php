<?php

use App\Http\Controllers\Api\AlertRuleController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/alerts', [AlertRuleController::class, 'index']);
    Route::post('/alerts', [AlertRuleController::class, 'store']);
    Route::put('/alerts/{id}', [AlertRuleController::class, 'update']);
    Route::delete('/alerts/{id}', [AlertRuleController::class, 'destroy']);
});