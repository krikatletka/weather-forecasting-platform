<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weather_logs', function (Blueprint $table) {
            $table->string('data_source', 30)
                ->default('synthetic');
        });
    }

    public function down(): void
    {
        Schema::table('weather_logs', function (Blueprint $table) {
            $table->dropColumn('data_source');
        });
    }
};
