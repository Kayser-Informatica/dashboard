<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('system_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('received');
            $table->string('original_filename', 255);
            $table->string('stored_path', 255);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->text('log_excerpt')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['system_id', 'received_at']);
            $table->index(['status', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
