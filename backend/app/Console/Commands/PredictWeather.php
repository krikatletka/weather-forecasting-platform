<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Services\Weather\PredictionService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class PredictWeather extends Command
{
    protected $signature = 'weather:predict {--city= : City ID, otherwise all active cities} {--at= : UTC issuance time YYYY-MM-DD HH:00:00 (default now)} {--hours=1 : Forecast horizon 1-24 hours} {--algorithm=historical_average : historical_average or similar_conditions}';
    protected $description = 'Generate and persist hourly predictions from stored weather observations';

    public function handle(PredictionService $service): int
    {
        try {
            $at = $this->option('at')
                ? CarbonImmutable::parse($this->option('at'), 'UTC')
                : CarbonImmutable::now('UTC');
            $hours = (int) $this->option('hours');
            $algorithm = $this->option('algorithm');
            $ids = City::query()->where('is_active', true)
                ->when($this->option('city'), fn ($query) => $query->whereKey((int) $this->option('city')))
                ->pluck('id');
            if ($ids->isEmpty()) {
                throw new \RuntimeException('No matching active cities.');
            }
            $success = 0;
            foreach ($ids as $id) {
                try {
                    $result = $service->predict((int) $id, $at, $hours, $algorithm);
                    $this->line("City {$id}: {$result['temperature']}°C, {$result['precipitation']} mm, samples={$result['samples']} (prediction #{$result['id']})");
                    $success++;
                } catch (\RuntimeException $e) {
                    $this->warn("City {$id}: {$e->getMessage()}");
                }
            }
            return $success === count($ids) ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
