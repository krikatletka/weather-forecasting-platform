<?php

namespace Tests\Unit;

use App\Models\City;
use App\Services\Weather\PredictionValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PredictionValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $cityId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cityId = City::forceCreate([
            'name' => 'Київ', 'latitude' => 50.45, 'longitude' => 30.52, 'timezone' => 'Europe/Kyiv',
        ])->id;
    }

    private function log(string $time, float $temp): void
    {
        DB::table('weather_logs')->insert([
            'city_id' => $this->cityId, 'recorded_at' => $time, 'temperature' => $temp,
            'surface_pressure' => 1000, 'humidity' => 70, 'wind_speed' => 3,
            'wind_direction' => 180, 'weather_code' => 3,
        ]);
    }

    private function prediction(?float $actual = null, ?float $error = null): void
    {
        DB::table('weather_predictions')->insert([
            'city_id' => $this->cityId,
            'generated_at' => '2023-09-30 12:00:00',
            'predicted_for_at' => '2023-10-01 12:00:00',
            'predicted_temp' => 18.0,
            'algorithm_type' => 'historical_average',
            'confidence_score' => 0.5,
            'actual_temp' => $actual,
            'error_margin' => $error,
        ]);
    }

    public function test_correct_error_margin_and_matching_by_predicted_for_at()
    {
        $this->log('2023-10-01 12:00:00', 15.5);
        $this->log('2023-10-01 18:00:00', 99.0);
        $this->prediction();

        (new PredictionValidationService())->validate();

        $prediction = DB::table('weather_predictions')->first();
        $this->assertEquals(15.5, $prediction->actual_temp);
        $this->assertEquals(2.5, $prediction->error_margin);
    }

    public function test_handles_missing_actual_measurement_gracefully()
    {
        $this->prediction();

        $count = (new PredictionValidationService())->validate();

        $this->assertEquals(0, $count);
        $this->assertNull(DB::table('weather_predictions')->first()->actual_temp);
    }

    public function test_prevents_duplicate_validation()
    {
        $this->log('2023-10-01 12:00:00', 15.5);
        $this->prediction(15.5, 2.5);

        $count = (new PredictionValidationService())->validate();

        $this->assertEquals(0, $count);
    }
}