<?php

namespace App\Services\Alerts;

use App\Models\AlertRule;
use App\Models\WeatherLog;
use Illuminate\Support\Collection;

class AlertEvaluationService
{
    /** parameter у правилі => колонка в weather_logs */
    public const PARAMETERS = [
        'temperature'      => 'temperature',
        'humidity'         => 'humidity',
        'surface_pressure' => 'surface_pressure',
        'wind_speed'       => 'wind_speed',
        'precipitation'    => 'precipitation',
    ];

    public const OPERATORS = ['<', '>', '<=', '>='];

    /**
     * Правила, які спрацювали саме на цьому заміру
     * (умова виконана зараз і не була виконана на попередньому).
     */
    public function evaluate(WeatherLog $current, ?WeatherLog $previous = null): Collection
    {
        return AlertRule::query()
            ->where('city_id', $current->city_id)
            ->where('is_active', true)
            ->get()
            ->filter(fn (AlertRule $rule) => $this->conditionMet($rule, $current)
                && !($previous && $this->conditionMet($rule, $previous)))
            ->values();
    }

    public function conditionMet(AlertRule $rule, WeatherLog $log): bool
    {
        $column = self::PARAMETERS[$rule->parameter] ?? null;
        if ($column === null) {
            return false;
        }

        $value = $log->{$column};
        if ($value === null) {
            return false;
        }

        return $this->compare((float) $value, $rule->operator, (float) $rule->threshold_value);
    }

    public function compare(float $value, string $operator, float $threshold): bool
    {
        return match ($operator) {
            '<'  => $value < $threshold,
            '>'  => $value > $threshold,
            '<=' => $value <= $threshold,
            '>=' => $value >= $threshold,
            default => throw new \InvalidArgumentException("Невідомий оператор: {$operator}"),
        };
    }
}