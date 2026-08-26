<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('systems', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->string('last_health_status', 20)->default('unknown');
            $table->text('last_health_message')->nullable();
            $table->timestamp('last_health_at')->nullable();
            $table->timestamp('last_backup_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('systems');
    }
};
