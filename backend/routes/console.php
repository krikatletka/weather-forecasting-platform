<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hourly workflow: first ingest, then forecast using observed (not future) data.
Schedule::command('weather:ingest')->hourlyAt(0)->withoutOverlapping();
Schedule::command('weather:forecast-range --from=12 --to=24 --algorithm=historical_average')->hourlyAt(10)->withoutOverlapping();
Schedule::command('weather:forecast-range --from=12 --to=24 --algorithm=similar_conditions')->hourlyAt(15)->withoutOverlapping();
Schedule::command('weather:validate')->hourlyAt(20)->withoutOverlapping();
// Aggregates are grouped by UTC day; refresh yesterday and today.
Schedule::command('weather:aggregate')->dailyAt('00:30')->withoutOverlapping();
