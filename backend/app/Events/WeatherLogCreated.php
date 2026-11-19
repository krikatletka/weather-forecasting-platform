<?php

namespace App\Events;

use App\Models\WeatherLog;
use Illuminate\Foundation\Events\Dispatchable;

class WeatherLogCreated
{
    use Dispatchable;

    public function __construct(public WeatherLog $weatherLog) {}
}