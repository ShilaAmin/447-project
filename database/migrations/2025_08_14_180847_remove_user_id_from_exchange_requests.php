<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Drop FK if it exists (whatever its name is)
        $fks = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'exchange_requests'
              AND COLUMN_NAME = 'user_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE `exchange_requests` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // 2) Drop index (if Laravel created one for user_id)
        $indexes = DB::select("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'exchange_requests'
              AND COLUMN_NAME = 'user_id'
        ");

        Schema::table('exchange_requests', function (Blueprint $table) use ($indexes) {
            foreach ($indexes as $idx) {
                // don't try to drop PRIMARY
                if ($idx->INDEX_NAME !== 'PRIMARY') {
                    try { $table->dropIndex($idx->INDEX_NAME); } catch (\Throwable $e) { /* ignore */ }
                }
            }
        });

        // 3) Drop the column if it exists
        if (Schema::hasColumn('exchange_requests', 'user_id')) {
            Schema::table('exchange_requests', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }

    public function down(): void
    {
        // Recreate the column (nullable to be safe), index, and FK (optional)
        if (!Schema::hasColumn('exchange_requests', 'user_id')) {
            Schema::table('exchange_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            });
        }

        // Add FK back to users.id (optional)
        try {
            Schema::table('exchange_requests', function (Blueprint $table) {
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->onDelete('cascade');
            });
        } catch (\Throwable $e) {
            // ignore if FK name conflicts or users table missing in a test env
        }
    }
};
