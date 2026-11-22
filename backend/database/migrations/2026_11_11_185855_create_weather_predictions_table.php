<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_predictions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('city_id')
                ->constrained('cities')
                ->cascadeOnDelete();

            $table->timestamp('generated_at');

            $table->timestamp('predicted_for_at');

            $table->decimal('predicted_temp', 5, 2);
            $table->decimal('predicted_precipitation', 8, 2)
                ->default(0);

            $table->enum('algorithm_type', [
                'historical_average',
                'similar_conditions',
            ]);

            $table->decimal('confidence_score', 5, 4);
            $table->decimal('actual_temp', 5, 2)->nullable();
            $table->decimal('error_margin', 5, 2)->nullable();

            $table->timestamps();

            $table->index(
                ['city_id', 'predicted_for_at'],
                'weather_predictions_city_time_index'
            );

            $table->index(
                ['algorithm_type', 'predicted_for_at'],
                'weather_predictions_algorithm_time_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_predictions');
    }
};
