<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('city_id')
                ->constrained('cities')
                ->cascadeOnDelete();

            $table->timestamp('recorded_at');

            $table->decimal('temperature', 5, 2);
            $table->decimal('surface_pressure', 7, 2);
            $table->unsignedSmallInteger('humidity');
            $table->decimal('wind_speed', 6, 2);
            $table->unsignedSmallInteger('wind_direction');
            $table->decimal('precipitation', 8, 2)->default(0);
            $table->unsignedSmallInteger('weather_code');

            $table->timestamps();

            $table->unique(
                ['city_id', 'recorded_at'],
                'weather_logs_city_recorded_unique'
            );

            $table->index(
                ['city_id', 'surface_pressure', 'wind_direction'],
                'weather_logs_conditions_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_logs');
    }
};
