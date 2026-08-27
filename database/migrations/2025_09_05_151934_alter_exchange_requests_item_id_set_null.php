<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exchange_requests', function (Blueprint $table) {
            // Drop old FK if present
            try { $table->dropForeign(['item_id']); } catch (\Throwable $e) {}

            // Make item_id nullable
            $table->unsignedBigInteger('item_id')->nullable()->change();

            // Re-add FK with ON DELETE SET NULL
            $table->foreign('item_id')
                ->references('id')->on('items')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('exchange_requests', function (Blueprint $table) {
            try { $table->dropForeign(['item_id']); } catch (\Throwable $e) {}

            // Back to NOT NULL + CASCADE (original behavior)
            $table->unsignedBigInteger('item_id')->nullable(false)->change();

            $table->foreign('item_id')
                ->references('id')->on('items')
                ->onDelete('cascade');
        });
    }
};
