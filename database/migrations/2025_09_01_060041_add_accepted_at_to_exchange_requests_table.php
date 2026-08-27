<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exchange_requests', function (Blueprint $table) {
            $table->timestamp('accepted_at')->nullable()->after('status');
            $table->index('accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('exchange_requests', function (Blueprint $table) {
            $table->dropIndex(['accepted_at']);
            $table->dropColumn('accepted_at');
        });
    }
};
