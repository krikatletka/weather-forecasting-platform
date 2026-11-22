<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('city_id')
                ->constrained('cities')
                ->cascadeOnDelete();

            $table->string('parameter', 50);
            $table->string('operator', 10);
            $table->decimal('threshold_value', 8, 2);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(
                ['city_id', 'is_active'],
                'alert_rules_city_active_index'
            );

            $table->index(
                ['user_id', 'is_active'],
                'alert_rules_user_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
