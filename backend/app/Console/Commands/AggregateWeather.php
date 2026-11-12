<?php

namespace App\Console\Commands;

use App\Services\Weather\DailyAggregationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class AggregateWeather extends Command
{
    protected $signature = 'weather:aggregate {--from= : UTC start date (YYYY-MM-DD)} {--to= : UTC end date (YYYY-MM-DD)} {--city= : City ID}';
    protected $description = 'Build or update daily weather aggregates from hourly observations';

    public function handle(DailyAggregationService $service): int
    {
        try {
            $from = $this->option('from')
                ? CarbonImmutable::createFromFormat('!Y-m-d', $this->option('from'), 'UTC')
                : CarbonImmutable::now('UTC')->yesterday();
            $to = $this->option('to')
                ? CarbonImmutable::createFromFormat('!Y-m-d', $this->option('to'), 'UTC')
                : $from;
            if (!$from || !$to) {
                throw new \InvalidArgumentException('Invalid date. Use YYYY-MM-DD.');
            }
            $count = $service->aggregate($from, $to, $this->option('city') ? (int) $this->option('city') : null);
            $this->info("Upserted {$count} city-day aggregates.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
