<?php

namespace Tests\Feature;

use App\Services\OpenMeteoService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;
use Illuminate\Http\Client\ConnectionException;

class OpenMeteoServiceTest extends TestCase
{
    public function test_fetches_hourly_forecast(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'latitude' => 49.99,
                'longitude' => 36.23,
                'hourly' => [
                    'time' => ['2026-11-18T12:00'],
                    'temperature_2m' => [5.5],
                ],
            ], 200),
        ]);

        $data = app(OpenMeteoService::class)
            ->getHourlyForecast(49.9935, 36.2304);

        $this->assertSame(5.5, $data['hourly']['temperature_2m'][0]);

        Http::assertSentCount(1);
    }

    public function test_rejects_invalid_coordinates(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $this->expectException(RuntimeException::class);

        app(OpenMeteoService::class)
            ->getHourlyForecast(100, 200);
    }

    public function test_rejects_invalid_response_structure(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'unexpected' => 'data',
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);

        app(OpenMeteoService::class)
            ->getHourlyForecast(49.9935, 36.2304);
    }

    public function test_handles_server_error(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'api.open-meteo.com/*' => Http::response(
                ['error' => 'Internal Server Error'],
                500
            ),
        ]);

        $this->expectException(RequestException::class);

        app(OpenMeteoService::class)
            ->getHourlyForecast(49.9935, 36.2304);
    }

    public function test_handles_connection_timeout(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'api.open-meteo.com/*' => function () {
                throw new ConnectionException(
                    'Connection timed out'
                );
            },
        ]);

        $this->expectException(ConnectionException::class);

        app(OpenMeteoService::class)
            ->getHourlyForecast(49.9935, 36.2304);
    }

}
