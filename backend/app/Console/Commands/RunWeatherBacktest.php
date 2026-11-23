<?php

namespace App\Console\Commands;

use App\Services\Weather\BacktestingService;
use Illuminate\Console\Command;

class RunWeatherBacktest extends Command
{
    protected $signature = 'weather:backtest
                            {--city= : ID міста}
                            {--stride=6 : Крок між прогнозами в годинах}';

    protected $description = 'Compare weather forecasting algorithms using ERA5 archive data';

    public function handle(BacktestingService $backtesting): int
    {
        $cityId = $this->option('city')
            ? (int) $this->option('city')
            : null;

        $stride = (int) $this->option('stride');

        if ($cityId !== null && $cityId < 1) {
            $this->error('City ID must be a positive integer.');
            return self::FAILURE;
        }

        if ($stride < 1 || $stride > 24) {
            $this->error('Stride must be between 1 and 24 hours.');
            return self::FAILURE;
        }

        $this->info('Starting weather algorithm backtesting...');
        $this->newLine();

        try {
            $result = $backtesting->run($cityId, $stride);
        } catch (\Throwable $e) {
            $this->error('Backtesting failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info(
            'Tested cities: ' . $result['tested_cities']
        );

        $this->info(
            'Forecast issue interval: every '
            . $result['stride_hours']
            . ' hours'
        );

        $this->newLine();

        $tableRows = [];

        foreach ($result['results'] as $row) {
            $tableRows[] = [
                $row['algorithm'],
                $row['horizon_hours'] . ' h',
                $row['count'],
                $row['mae'] === null ? 'N/A' : $row['mae'],
                $row['rmse'] === null ? 'N/A' : $row['rmse'],
            ];
        }

        $this->table(
            ['Algorithm', 'Horizon', 'Tests', 'MAE (°C)', 'RMSE (°C)'],
            $tableRows
        );

        $this->newLine();
        $this->info('Lower MAE and RMSE indicate better temperature accuracy.');

        return self::SUCCESS;
    }
}
