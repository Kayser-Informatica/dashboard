<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('monitored_service_id')->constrained('monitored_services')->cascadeOnDelete();
            $table->string('status', 20)->default('received');
            $table->string('original_filename', 255)->nullable();
            $table->string('stored_path', 255)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->text('log_excerpt')->nullable();
            $table->text('message')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('received_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_logs');
    }
};
