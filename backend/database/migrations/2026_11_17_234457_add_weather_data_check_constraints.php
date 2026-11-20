<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE weather_logs
            ADD CONSTRAINT weather_logs_humidity_check
            CHECK (humidity BETWEEN 0 AND 100)
        ");

        DB::statement("
            ALTER TABLE weather_logs
            ADD CONSTRAINT weather_logs_wind_direction_check
            CHECK (wind_direction BETWEEN 0 AND 359)
        ");

        DB::statement("
            ALTER TABLE weather_logs
            ADD CONSTRAINT weather_logs_wind_speed_check
            CHECK (wind_speed >= 0)
        ");

        DB::statement("
            ALTER TABLE weather_logs
            ADD CONSTRAINT weather_logs_precipitation_check
            CHECK (precipitation >= 0)
        ");

        DB::statement("
            ALTER TABLE weather_predictions
            ADD CONSTRAINT weather_predictions_confidence_check
            CHECK (confidence_score BETWEEN 0 AND 1)
        ");

        DB::statement("
            ALTER TABLE weather_predictions
            ADD CONSTRAINT weather_predictions_precipitation_check
            CHECK (predicted_precipitation >= 0)
        ");

        DB::statement("
            ALTER TABLE weather_daily_aggregates
            ADD CONSTRAINT weather_daily_temp_range_check
            CHECK (temp_min <= temp_avg AND temp_avg <= temp_max)
        ");

        DB::statement("
            ALTER TABLE weather_daily_aggregates
            ADD CONSTRAINT weather_daily_precipitation_check
            CHECK (total_precipitation >= 0)
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE weather_logs
            DROP CONSTRAINT IF EXISTS weather_logs_humidity_check
        ");

        DB::statement("
            ALTER TABLE weather_logs
            DROP CONSTRAINT IF EXISTS weather_logs_wind_direction_check
        ");

        DB::statement("
            ALTER TABLE weather_logs
            DROP CONSTRAINT IF EXISTS weather_logs_wind_speed_check
        ");

        DB::statement("
            ALTER TABLE weather_logs
            DROP CONSTRAINT IF EXISTS weather_logs_precipitation_check
        ");

        DB::statement("
            ALTER TABLE weather_predictions
            DROP CONSTRAINT IF EXISTS weather_predictions_confidence_check
        ");

        DB::statement("
            ALTER TABLE weather_predictions
            DROP CONSTRAINT IF EXISTS weather_predictions_precipitation_check
        ");

        DB::statement("
            ALTER TABLE weather_daily_aggregates
            DROP CONSTRAINT IF EXISTS weather_daily_temp_range_check
        ");

        DB::statement("
            ALTER TABLE weather_daily_aggregates
            DROP CONSTRAINT IF EXISTS weather_daily_precipitation_check
        ");
    }
};
