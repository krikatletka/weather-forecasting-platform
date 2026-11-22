<?php

namespace Tests\Feature;

use App\Events\WeatherLogCreated;
use App\Jobs\SendWeatherAlertJob;
use App\Models\AlertRule;
use App\Models\City;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\Weather\WeatherIngestionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WeatherIngestionAlertsTest extends TestCase
{
    use RefreshDatabase;

    private City $city;
    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->city = City::forceCreate([
            'name' => 'Київ',
            'latitude' => 50.45,
            'longitude' => 30.52,
            'timezone' => 'Europe/Kyiv',
        ]);
        $this->now = CarbonImmutable::parse('2026-11-20 12:30:00', 'UTC');
    }

    /** @param array<string, float> $rows час => температура */
    private function fakeApi(array $rows): void
    {
        $n = count($rows);
        Http::fake(['*' => Http::response(['hourly' => [
            'time' => array_keys($rows),
            'temperature_2m' => array_values($rows),
            'relative_humidity_2m' => array_fill(0, $n, 70),
            'surface_pressure' => array_fill(0, $n, 1000),
            'wind_speed_10m' => array_fill(0, $n, 3),
            'wind_direction_10m' => array_fill(0, $n, 180),
            'precipitation' => array_fill(0, $n, 0),
            'weather_code' => array_fill(0, $n, 3),
        ]])]);
    }

    private function ingest(): array
    {
        return app(WeatherIngestionService::class)->ingest($this->city, $this->now);
    }

    public function test_event_is_dispatched_only_for_new_fresh_rows(): void
    {
        Event::fake([WeatherLogCreated::class]);
        $this->fakeApi([
            '2026-11-18T10:00' => 5, // старий: записується, події немає
            '2026-11-20T09:00' => 5, // старший за 3 год: події немає
            '2026-11-20T10:00' => 5, // свіжий
            '2026-11-20T11:00' => 5, // свіжий
            '2026-11-20T12:00' => 5, // поточна година: пропускається
        ]);

        $first = $this->ingest();
        $this->assertSame(4, $first['inserted']);
        Event::assertDispatchedTimes(WeatherLogCreated::class, 2);

        $second = $this->ingest();
        $this->assertSame(0, $second['inserted']);
        Event::assertDispatchedTimes(WeatherLogCreated::class, 2); // повторний запуск не дублює
    }

    public function test_ingestion_creates_one_alert_when_condition_starts(): void
    {
        Queue::fake();
        $rule = AlertRule::create([
            'user_id' => User::factory()->create()->id,
            'city_id' => $this->city->id,
            'parameter' => 'temperature',
            'operator' => '<',
            'threshold_value' => 0,
        ]);
        $this->fakeApi([
            '2026-11-20T09:00' => 3,
            '2026-11-20T10:00' => -2, // перехід: спрацьовує
            '2026-11-20T11:00' => -3, // умова триває: мовчимо
        ]);

        $this->ingest();

        $this->assertSame(1, NotificationLog::where('alert_rule_id', $rule->id)->count());
        Queue::assertPushed(SendWeatherAlertJob::class, 1);
    }
}