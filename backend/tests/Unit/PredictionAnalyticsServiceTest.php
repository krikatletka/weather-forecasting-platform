<?php

namespace Tests\Unit;

use App\Models\City;
use App\Services\Weather\PredictionAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PredictionAnalyticsServiceTest extends TestCase
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

    private function row(string $algorithm, ?float $actual, ?float $error, string $forAt): array
    {
        return [
            'city_id' => $this->cityId,
            'generated_at' => '2023-09-30 12:00:00',
            'predicted_for_at' => $forAt,
            'predicted_temp' => 10.0,
            'algorithm_type' => $algorithm,
            'confidence_score' => 0.5,
            'actual_temp' => $actual,
            'error_margin' => $error,
        ];
    }

    public function test_it_calculates_mae_and_comparison_correctly()
    {
        DB::table('weather_predictions')->insert([
            $this->row('historical_average', 10.0, 2.0, '2023-10-01 12:00:00'),
            $this->row('historical_average', 10.0, 4.0, '2023-10-01 13:00:00'),
            $this->row('similar_conditions', 10.0, 1.0, '2023-10-01 12:00:00'),
            $this->row('similar_conditions', 10.0, 1.0, '2023-10-01 13:00:00'),
            $this->row('similar_conditions', null, null, '2023-10-01 14:00:00'), // Ігнорується
        ]);

        $service = new PredictionAnalyticsService();

        $accuracy = $service->getAccuracy(['city_id' => $this->cityId]);
        $this->assertEquals(4, $accuracy['overall']['total_checked']);
        $this->assertEquals(2.0, $accuracy['overall']['mae']);

        $comparison = $service->compareAlgorithms(['city_id' => $this->cityId]);
        $this->assertEquals('similar_conditions', $comparison['best_algorithm']);
        $this->assertEquals(1.0, $comparison['ranking'][0]['mae']);
        $this->assertEquals(3.0, $comparison['ranking'][1]['mae']);
    }
}