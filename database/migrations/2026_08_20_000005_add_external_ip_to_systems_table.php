<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('systems', function (Blueprint $table): void {
            $table->string('external_ip', 45)->nullable()->after('last_backup_at');
            $table->timestamp('last_ip_at')->nullable()->after('external_ip');
        });
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table): void {
            $table->dropColumn(['external_ip', 'last_ip_at']);
        });
    }
};
