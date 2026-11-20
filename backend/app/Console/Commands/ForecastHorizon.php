<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Services\Weather\PredictionService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ForecastHorizon extends Command
{
    protected $signature = 'weather:forecast-range {--city= : City ID} {--at= : Historical issuance time in UTC} {--from=12 : First horizon, 1-24 hours} {--to=24 : Last horizon, 1-24 hours} {--algorithm=historical_average : Forecast method}';
    protected $description = 'Generate a consecutive range of 1-24 hour ahead forecasts';

    public function handle(PredictionService $predictions): int
    {
        $from = filter_var($this->option('from'), FILTER_VALIDATE_INT);
        $to = filter_var($this->option('to'), FILTER_VALIDATE_INT);
        if ($from === false || $to === false || $from < 1 || $from > 24 || $to < 1 || $to > 24 || $from > $to) {
            $this->error('Use an integer horizon range from 1 to 24.');
            return self::FAILURE;
        }
        try {
            $at = $this->option('at')
                ? CarbonImmutable::parse($this->option('at'), 'UTC')
                : CarbonImmutable::now('UTC');
        } catch (\Throwable $e) {
            $this->error('Invalid --at timestamp.');
            return self::FAILURE;
        }
        $cities = City::query()->where('is_active', true)
            ->when($this->option('city'), fn ($q) => $q->whereKey((int) $this->option('city')))
            ->pluck('id');
        if ($cities->isEmpty()) {
            $this->warn('No active cities matched.');
            return self::FAILURE;
        }
        $success = 0;
        $errors = 0;
        foreach ($cities as $cityId) {
            foreach (range($from, $to) as $hours) {
                try {
                    $result = $predictions->predict((int) $cityId, $at, $hours, (string) $this->option('algorithm'));
                    $this->line("City {$cityId} +{$hours}h: {$result['temperature']}°C (#{$result['id']})");
                    $success++;
                } catch (\Throwable $e) {
                    $this->warn("City {$cityId} +{$hours}h: ".$e->getMessage());
                    $errors++;
                }
            }
        }
        $this->info("Forecasts: {$success} successful, {$errors} failed.");
        return $errors === 0 ? self::SUCCESS : self::FAILURE;
    }
}
