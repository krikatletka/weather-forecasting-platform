<?php

namespace App\Listeners;

use App\Events\WeatherLogCreated;
use App\Jobs\SendWeatherAlertJob;
use App\Models\NotificationLog;
use App\Models\WeatherLog;
use App\Services\Alerts\AlertEvaluationService;

class EvaluateWeatherAlerts
{
    public function __construct(private AlertEvaluationService $service) {}

    public function handle(WeatherLogCreated $event): void
    {
        $current = $event->weatherLog;

        $previous = WeatherLog::where('city_id', $current->city_id)
            ->where('recorded_at', '<', $current->recorded_at)
            ->orderByDesc('recorded_at')
            ->first();

        foreach ($this->service->evaluate($current, $previous) as $rule) {
            $column = AlertEvaluationService::PARAMETERS[$rule->parameter];

            $log = NotificationLog::create([
                'alert_rule_id' => $rule->id,
                'user_id' => $rule->user_id,
                'triggered_value' => $current->{$column},
                'status' => 'pending',
            ]);

            SendWeatherAlertJob::dispatch($log->id);
        }
    }
}