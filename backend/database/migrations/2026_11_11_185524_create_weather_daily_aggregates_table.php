<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_daily_aggregates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('city_id')
                ->constrained('cities')
                ->cascadeOnDelete();

            $table->date('date');

            $table->decimal('temp_min', 5, 2);
            $table->decimal('temp_max', 5, 2);
            $table->decimal('temp_avg', 5, 2);
            $table->decimal('avg_pressure', 7, 2);
            $table->unsignedSmallInteger('dominant_wind_dir');
            $table->decimal('total_precipitation', 8, 2)
                ->default(0);

            $table->timestamps();

            $table->unique(
                ['city_id', 'date'],
                'weather_daily_city_date_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_daily_aggregates');
    }
};
