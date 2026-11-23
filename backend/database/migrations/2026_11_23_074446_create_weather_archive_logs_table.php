<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weather_archive_logs', function (Blueprint $table) {
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
            $table->unsignedSmallInteger('weather_code')->nullable();

            $table->string('data_source', 30)->default('open_meteo_era5');

            $table->timestamps();

            $table->unique(
                ['city_id', 'recorded_at'],
                'weather_archive_city_recorded_unique'
            );

            $table->index(
                ['city_id', 'recorded_at'],
                'weather_archive_city_time_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_archive_logs');
    }
};
