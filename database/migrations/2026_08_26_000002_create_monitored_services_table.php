<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->unsignedInteger('expected_interval_minutes')->default(60);
            $table->unsignedInteger('grace_period_minutes')->default(10);
            $table->text('notification_emails')->nullable();
            $table->timestamp('last_ping_at')->nullable();
            $table->string('last_status', 20)->default('unknown'); // ok, failed, warning, unknown
            $table->text('last_message')->nullable();
            $table->unsignedInteger('last_duration_seconds')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->boolean('is_in_alert')->default(false);
            $table->timestamp('last_alert_sent_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['client_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_services');
    }
};
