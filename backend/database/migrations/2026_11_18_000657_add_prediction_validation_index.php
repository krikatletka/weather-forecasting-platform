<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE INDEX weather_predictions_pending_validation_idx
            ON weather_predictions (predicted_for_at)
            WHERE actual_temp IS NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            DROP INDEX IF EXISTS weather_predictions_pending_validation_idx
        ");
    }
};
