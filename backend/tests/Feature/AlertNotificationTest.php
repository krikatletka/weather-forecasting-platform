<?php

namespace Tests\Feature;

use App\Events\WeatherLogCreated;
use App\Jobs\SendWeatherAlertJob;
use App\Listeners\EvaluateWeatherAlerts;
use App\Mail\WeatherAlertMail;
use App\Models\AlertRule;
use App\Models\City;
use App\Models\NotificationLog;
use App\Models\User;
use App\Models\WeatherLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AlertNotificationTest extends TestCase
{
    use RefreshDatabase;

    private City $city;
    private AlertRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->city = $this->makeCity(); 
        $this->rule = AlertRule::create([
            'user_id' => User::factory()->create()->id,
            'city_id' => $this->city->id,
            'parameter' => 'temperature',
            'operator' => '<',
            'threshold_value' => 0,
        ]);
    }

    /** Якщо в таблиці є обов'язкові колонки (data_source тощо), додай їх сюди. */
    private function log(float $temp, string $time): WeatherLog
    {
        return WeatherLog::create([
            'city_id' => $this->city->id,
            'recorded_at' => $time,
            'temperature' => $temp,
            'surface_pressure' => 1000,
            'humidity' => 70,
            'wind_speed' => 2,
            'wind_direction' => 180,
            'precipitation' => 0,
            'weather_code' => 3,
        ]);
    }

    private function handle(WeatherLog $log): void
    {
      app(EvaluateWeatherAlerts::class)->handle(new WeatherLogCreated($log));
    }
    
    public function test_alert_fires_once_when_condition_becomes_true(): void
    {
        Queue::fake();

        $this->handle($this->log(3, '2026-11-12 09:00:00'));   // умова не виконана
        $this->handle($this->log(-2, '2026-11-12 10:00:00'));  // перехід: спрацювало
        $this->handle($this->log(-3, '2026-11-12 11:00:00'));  // умова триває: мовчимо

        $this->assertSame(1, NotificationLog::count());
        Queue::assertPushed(SendWeatherAlertJob::class, 1);
    }

    public function test_alert_fires_again_after_condition_resets(): void
    {
        Queue::fake();

        $this->handle($this->log(-2, '2026-11-12 08:00:00'));
        $this->handle($this->log(1, '2026-11-12 09:00:00'));   // скидання
        $this->handle($this->log(-1, '2026-11-12 10:00:00'));  // знову спрацювало

        $this->assertSame(2, NotificationLog::count());
    }

    public function test_inactive_rule_does_not_fire(): void
    {
        Queue::fake();
        $this->rule->update(['is_active' => false]);

        $this->handle($this->log(-5, '2026-11-12 10:00:00'));

        $this->assertSame(0, NotificationLog::count());
        Queue::assertNothingPushed();
    }

    public function test_rule_of_other_city_does_not_fire(): void
    {
        Queue::fake();
        // Використовуємо makeCity із назвою 'Харків'
        $otherCity = $this->makeCity('Харків');
        $this->rule->update(['city_id' => $otherCity->id]);

        $this->handle($this->log(-5, '2026-11-12 10:00:00'));

        $this->assertSame(0, NotificationLog::count());
    }

    public function test_job_sends_mail_and_marks_log_sent(): void
    {
        Mail::fake();
        $log = NotificationLog::create([
            'alert_rule_id' => $this->rule->id,
            'user_id' => $this->rule->user_id,
            'triggered_value' => -2,
            'status' => 'pending',
        ]);

        (new SendWeatherAlertJob($log->id))->handle();

        Mail::assertSent(WeatherAlertMail::class, 1);
        $log->refresh();
        $this->assertSame('sent', $log->status);
        $this->assertNotNull($log->sent_at);
    }

    public function test_job_does_not_send_twice(): void
    {
        Mail::fake();
        $log = NotificationLog::create([
            'alert_rule_id' => $this->rule->id,
            'user_id' => $this->rule->user_id,
            'triggered_value' => -2,
            'status' => 'pending',
        ]);

        $job = new SendWeatherAlertJob($log->id);
        $job->handle();
        $job->handle();

        Mail::assertSent(WeatherAlertMail::class, 1);
    }

    public function test_failed_job_marks_log_failed(): void
    {
        $log = NotificationLog::create([
            'alert_rule_id' => $this->rule->id,
            'user_id' => $this->rule->user_id,
            'triggered_value' => -2,
            'status' => 'pending',
        ]);

        (new SendWeatherAlertJob($log->id))->failed(new \RuntimeException('SMTP down'));

        $this->assertSame('failed', $log->fresh()->status);
    }

    private function makeCity(string $name = 'Київ'): City
    {
        return City::forceCreate([
            'name' => $name,
            'latitude' => 50.45,
            'longitude' => 30.52,
            'timezone' => 'Europe/Kyiv',
        ]);
    }
}