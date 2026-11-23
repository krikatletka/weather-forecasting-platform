<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('weather_predictions', function (Blueprint $table) {
            $table->decimal('model_actual_temp', 5, 2)
                ->nullable()
                ->after('error_margin');

            $table->decimal('model_error_margin', 5, 2)
                ->nullable()
                ->after('model_actual_temp');

            $table->timestamp('model_validated_at')
                ->nullable()
                ->after('model_error_margin');
        });
    }

    public function down(): void
    {
        Schema::table('weather_predictions', function (Blueprint $table) {
            $table->dropColumn([
                'model_actual_temp',
                'model_error_margin',
                'model_validated_at',
            ]);
        });
    }
};
