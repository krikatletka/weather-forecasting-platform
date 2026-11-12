<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Live ingestion is a separate module. Forecast commands refuse stale observations.
Schedule::command('weather:aggregate')->dailyAt('00:15')->withoutOverlapping();
Schedule::command('weather:predict --algorithm=historical_average')->hourlyAt(5)->withoutOverlapping();
Schedule::command('weather:predict --algorithm=similar_conditions')->hourlyAt(10)->withoutOverlapping();
Schedule::command('weather:validate')->hourlyAt(20)->withoutOverlapping();
