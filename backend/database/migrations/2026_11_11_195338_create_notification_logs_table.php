<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('alert_rule_id')
                ->constrained('alert_rules')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->decimal('triggered_value', 8, 2);

            $table->timestamp('sent_at')->nullable();

            $table->string('status', 30)->default('pending');

            $table->timestamps();

            $table->index(
                ['user_id', 'sent_at'],
                'notification_logs_user_sent_index'
            );

            $table->index(
                ['alert_rule_id', 'status'],
                'notification_logs_rule_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
