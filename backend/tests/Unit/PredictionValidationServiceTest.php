<?php

namespace Tests\Unit;

use App\Services\Weather\PredictionValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PredictionValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_error_margin_and_matching_by_predicted_for_at()
    {
        DB::table('weather_logs')->insert([
            'city_id' => 1, 'recorded_at' => '2023-10-01 12:00:00', 'temperature' => 15.5,
        ]);
        DB::table('weather_logs')->insert([
            'city_id' => 1, 'recorded_at' => '2023-10-01 18:00:00', 'temperature' => 99.0,
        ]);
        DB::table('weather_predictions')->insert([
            'city_id' => 1, 'predicted_temp' => 18.0, 'predicted_for_at' => '2023-10-01 12:00:00',
            'actual_temp' => null, 'error_margin' => null,
        ]);

        $service = new PredictionValidationService();
        $service->validate();

        $prediction = DB::table('weather_predictions')->first();
        $this->assertEquals(15.5, $prediction->actual_temp);
        $this->assertEquals(2.5, $prediction->error_margin);
    }

    public function test_handles_missing_actual_measurement_gracefully()
    {
        DB::table('weather_predictions')->insert([
            'city_id' => 1, 'predicted_temp' => 18.0, 'predicted_for_at' => '2023-10-01 12:00:00',
            'actual_temp' => null, 'error_margin' => null,
        ]);

        $service = new PredictionValidationService();
        $count = $service->validate();

        $this->assertEquals(0, $count);
        $this->assertNull(DB::table('weather_predictions')->first()->actual_temp);
    }

    public function test_prevents_duplicate_validation()
    {
        DB::table('weather_predictions')->insert([
            'city_id' => 1, 'predicted_temp' => 18.0, 'predicted_for_at' => '2023-10-01 12:00:00',
            'actual_temp' => 15.5, 'error_margin' => 2.5,
        ]);

        $service = new PredictionValidationService();
        $count = $service->validate();

        $this->assertEquals(0, $count);
    }
}