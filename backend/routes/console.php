<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Open-Meteo past-hour values are model estimates, not independent observations.
Schedule::command('weather:ingest')->hourlyAt(0)->withoutOverlapping();
Schedule::command('weather:forecast-range --from=12 --to=24 --algorithm=historical_average')->hourlyAt(10)->withoutOverlapping();
Schedule::command('weather:forecast-range --from=12 --to=24 --algorithm=similar_conditions')->hourlyAt(15)->withoutOverlapping();
// Refresh UTC yesterday and today so daily aggregates stay current.
Schedule::command('weather:aggregate')->hourlyAt(25)->withoutOverlapping();
Schedule::command('weather:validate')->hourlyAt(35)->withoutOverlapping();
