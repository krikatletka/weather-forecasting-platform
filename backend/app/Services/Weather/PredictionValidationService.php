<?php

namespace App\Services\Weather;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PredictionValidationService
{
    /**
     * Перевірка прогнозів за архівними спостереженнями ERA5.
     */
    public function validate(): int
    {
        $updated = 0;

        $archiveAvailableUntil = CarbonImmutable::now('UTC')
            ->startOfDay()
            ->subDays(6)
            ->endOfDay()
            ->format('Y-m-d H:i:s');

        DB::table('weather_predictions')
            ->whereNull('actual_temp')
            ->whereNotNull('predicted_temp')
            ->where('predicted_for_at', '<=', $archiveAvailableUntil)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('weather_archive_logs')
                    ->whereColumn(
                        'weather_archive_logs.city_id',
                        'weather_predictions.city_id'
                    )
                    ->whereColumn(
                        'weather_archive_logs.recorded_at',
                        'weather_predictions.predicted_for_at'
                    )
                    ->where('weather_archive_logs.data_source', 'open_meteo_era5')
                    ->whereNotNull('weather_archive_logs.temperature');
            })
            ->orderBy('id')
            ->chunkById(200, function ($predictions) use (&$updated) {
                foreach ($predictions as $prediction) {
                    $actual = DB::table('weather_archive_logs')
                        ->where('city_id', $prediction->city_id)
                        ->where('recorded_at', $prediction->predicted_for_at)
                        ->where('data_source', 'open_meteo_era5')
                        ->value('temperature');

                    if ($actual === null) {
                        continue;
                    }

                    $updated += DB::table('weather_predictions')
                        ->where('id', $prediction->id)
                        ->whereNull('actual_temp')
                        ->update([
                            'actual_temp' => $actual,
                            'error_margin' => round(
                                abs((float) $actual - (float) $prediction->predicted_temp),
                                2
                            ),
                            'updated_at' => now(),
                        ]);
                }
            });

        return $updated;
    }

    /**
     * Окрема перевірка прогнозів за даними Open-Meteo model.
     * Ці значення не є незалежними архівними спостереженнями ERA5.
     */
    public function validateModelEstimates(): int
    {
        $updated = 0;

        // Беремо лише завершені години.
        $completedUntil = CarbonImmutable::now('UTC')
            ->startOfHour()
            ->subHour()
            ->format('Y-m-d H:i:s');

        DB::table('weather_predictions')
            ->whereNull('model_actual_temp')
            ->whereNotNull('predicted_temp')
            ->where('predicted_for_at', '<=', $completedUntil)
            // Прогноз має бути створений раніше за цільовий час.
            ->whereColumn('predicted_for_at', '>', 'generated_at')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('weather_logs')
                    ->whereColumn(
                        'weather_logs.city_id',
                        'weather_predictions.city_id'
                    )
                    ->whereColumn(
                        'weather_logs.recorded_at',
                        'weather_predictions.predicted_for_at'
                    )
                    ->where('weather_logs.data_source', 'open_meteo_model')
                    ->whereNotNull('weather_logs.temperature');
            })
            ->orderBy('id')
            ->chunkById(200, function ($predictions) use (&$updated) {
                foreach ($predictions as $prediction) {
                    $actual = DB::table('weather_logs')
                        ->where('city_id', $prediction->city_id)
                        ->where('recorded_at', $prediction->predicted_for_at)
                        ->where('data_source', 'open_meteo_model')
                        ->value('temperature');

                    if ($actual === null) {
                        continue;
                    }

                    $updated += DB::table('weather_predictions')
                        ->where('id', $prediction->id)
                        ->whereNull('model_actual_temp')
                        ->update([
                            'model_actual_temp' => $actual,
                            'model_error_margin' => round(
                                abs((float) $actual - (float) $prediction->predicted_temp),
                                2
                            ),
                            'model_validated_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            });

        return $updated;
    }
}
