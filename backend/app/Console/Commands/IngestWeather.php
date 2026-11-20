<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Services\Weather\WeatherIngestionService;
use Illuminate\Console\Command;

class IngestWeather extends Command
{
    protected $signature = 'weather:ingest {--city= : Optional city ID}';
    protected $description = 'Fetch the latest available hourly Open-Meteo observations for active cities';

    public function handle(WeatherIngestionService $service): int
    {
        $cities = City::query()->where('is_active', true)
            ->when($this->option('city'), fn ($q) => $q->whereKey((int) $this->option('city')))
            ->get();
        if ($cities->isEmpty()) {
            $this->warn('No active cities matched.');
            return self::FAILURE;
        }
        $errors = 0;
        foreach ($cities as $city) {
            try {
                $result = $service->ingest($city);
                $this->line("{$city->name}: inserted={$result['inserted']}, skipped={$result['skipped']} ({$result['message']})");
                if (!$result['ok']) {
                    $errors++;
                }
            } catch (\Throwable $e) {
                $this->error("{$city->name}: ".$e->getMessage());
                $errors++;
            }
        }
        return $errors === 0 ? self::SUCCESS : self::FAILURE;
    }
}
