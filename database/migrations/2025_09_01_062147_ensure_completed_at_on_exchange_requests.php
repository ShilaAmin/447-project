<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('exchange_requests', 'completed_at')) {
            Schema::table('exchange_requests', function (Blueprint $table) {
                // 'after' is ignored by SQLite; safe on MySQL
                $table->timestamp('completed_at')->nullable()->after('status');
                $table->index('completed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('exchange_requests', 'completed_at')) {
            Schema::table('exchange_requests', function (Blueprint $table) {
                $table->dropIndex(['completed_at']);
                $table->dropColumn('completed_at');
            });
        }
    }
};
 