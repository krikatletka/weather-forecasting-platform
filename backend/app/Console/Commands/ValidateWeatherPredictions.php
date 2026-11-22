<?php

namespace App\Console\Commands;

use App\Services\Weather\PredictionValidationService;
use Illuminate\Console\Command;

class ValidateWeatherPredictions extends Command
{
    protected $signature = 'weather:validate';
    protected $description = 'Attach observed temperatures and absolute errors to completed forecasts';

    public function handle(PredictionValidationService $service): int
    {
        $this->info('Updated '.$service->validate().' predictions with actual weather observations.');
        return self::SUCCESS;
    }
}
