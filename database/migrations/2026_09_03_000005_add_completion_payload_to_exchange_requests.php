<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchange_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('exchange_requests', 'completion_payload')) {
                $table->longText('completion_payload')->nullable()->after('mac');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exchange_requests', function (Blueprint $table) {
            if (Schema::hasColumn('exchange_requests', 'completion_payload')) {
                $table->dropColumn('completion_payload');
            }
        });
    }
};
