<?php

namespace App\Console\Commands;

use App\Services\Weather\PredictionValidationService;
use Illuminate\Console\Command;

class ValidateWeatherPredictions extends Command
{
    protected $signature = 'weather:validate';

    protected $description = 'Validate forecasts using ERA5 archive and Open-Meteo model data';

    public function handle(PredictionValidationService $service): int
    {
        $archiveUpdated = $service->validate();

        $modelUpdated = $service->validateModelEstimates();

        $this->info("ERA5 archive: {$archiveUpdated} predictions updated.");

        $this->info("Open-Meteo model: {$modelUpdated} predictions updated.");

        return self::SUCCESS;
    }
}
