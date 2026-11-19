<?php

namespace Tests\Unit;

use App\Models\AlertRule;
use App\Models\WeatherLog;
use App\Services\Alerts\AlertEvaluationService;
use PHPUnit\Framework\TestCase;

class AlertEvaluationServiceTest extends TestCase
{
    private function rule(string $param, string $op, float $threshold): AlertRule
    {
        return (new AlertRule)->forceFill([
            'parameter' => $param, 'operator' => $op, 'threshold_value' => $threshold,
        ]);
    }

    private function log(array $attrs): WeatherLog
    {
        return (new WeatherLog)->forceFill($attrs);
    }

    public function test_below_zero_is_met(): void
    {
        $s = new AlertEvaluationService;
        $this->assertTrue($s->conditionMet($this->rule('temperature', '<', 0), $this->log(['temperature' => -5])));
    }

    public function test_above_zero_is_not_met(): void
    {
        $s = new AlertEvaluationService;
        $this->assertFalse($s->conditionMet($this->rule('temperature', '<', 0), $this->log(['temperature' => 3])));
    }

    public function test_boundary_values(): void
    {
        $s = new AlertEvaluationService;
        $this->assertFalse($s->compare(0, '<', 0));
        $this->assertTrue($s->compare(0, '<=', 0));
        $this->assertFalse($s->compare(30, '>', 30));
        $this->assertTrue($s->compare(30, '>=', 30));
    }

    public function test_missing_value_is_not_met(): void
    {
        $s = new AlertEvaluationService;
        $this->assertFalse($s->conditionMet($this->rule('wind_speed', '>', 15), $this->log(['wind_speed' => null])));
    }

    public function test_unknown_parameter_is_not_met(): void
    {
        $s = new AlertEvaluationService;
        $this->assertFalse($s->conditionMet($this->rule('foo', '>', 1), $this->log(['temperature' => 10])));
    }

    public function test_unknown_operator_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new AlertEvaluationService)->compare(1, '!=', 2);
    }
}