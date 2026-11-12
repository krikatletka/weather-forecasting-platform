<?php

namespace App\Services\Weather;

use Illuminate\Support\Facades\DB;

class PredictionValidationService
{
    /** Fill actual_temp and absolute temperature error when exact-hour measurements arrive. */
    public function validate(): int
    {
        $updated = 0;
        DB::table('weather_predictions')
            ->whereNull('actual_temp')
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('weather_logs')
                    ->whereColumn('weather_logs.city_id', 'weather_predictions.city_id')
                    ->whereColumn('weather_logs.recorded_at', 'weather_predictions.predicted_for_at');
            })
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$updated) {
                foreach ($rows as $prediction) {
                    $actual = DB::table('weather_logs')
                        ->where('city_id', $prediction->city_id)
                        ->where('recorded_at', $prediction->predicted_for_at)
                        ->value('temperature');
                    if ($actual !== null) {
                        $updated += DB::table('weather_predictions')->where('id', $prediction->id)->whereNull('actual_temp')->update([
                            'actual_temp' => $actual,
                            'error_margin' => round(abs((float) $actual - (float) $prediction->predicted_temp), 2),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
        return $updated;
    }
}
