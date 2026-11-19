<?php

namespace Tests\Unit;

use App\Services\Weather\PredictionAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PredictionAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_mae_and_comparison_correctly()
    {
        DB::table('weather_predictions')->insert([
            ['city_id' => 1, 'algorithm_type' => 'historical_average', 'actual_temp' => 10.0, 'error_margin' => 2.0, 'predicted_for_at' => '2023-10-01 12:00:00'],
            ['city_id' => 1, 'algorithm_type' => 'historical_average', 'actual_temp' => 10.0, 'error_margin' => 4.0, 'predicted_for_at' => '2023-10-01 13:00:00'],
            ['city_id' => 1, 'algorithm_type' => 'similar_conditions', 'actual_temp' => 10.0, 'error_margin' => 1.0, 'predicted_for_at' => '2023-10-01 12:00:00'],
            ['city_id' => 1, 'algorithm_type' => 'similar_conditions', 'actual_temp' => 10.0, 'error_margin' => 1.0, 'predicted_for_at' => '2023-10-01 13:00:00'],
            ['city_id' => 1, 'algorithm_type' => 'similar_conditions', 'actual_temp' => null, 'error_margin' => null, 'predicted_for_at' => '2023-10-01 14:00:00'], // Ігнорується
        ]);

        $service = new PredictionAnalyticsService();
        
        $accuracy = $service->getAccuracy(['city_id' => 1]);
        $this->assertEquals(4, $accuracy['overall']['total_checked']);
        $this->assertEquals(2.0, $accuracy['overall']['mae']);

        $comparison = $service->compareAlgorithms(['city_id' => 1]);
        $this->assertEquals('similar_conditions', $comparison['best_algorithm']);
        $this->assertEquals(1.0, $comparison['ranking'][0]['mae']);
        $this->assertEquals(3.0, $comparison['ranking'][1]['mae']);
    }
}