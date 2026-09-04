<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'mac')) {
                $table->string('mac', 64)->nullable()->after('content');
            }
            $table->text('title')->change();
        });

        Schema::table('comments', function (Blueprint $table) {
            if (!Schema::hasColumn('comments', 'mac')) {
                $table->string('mac', 64)->nullable()->after('content');
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'mac')) {
                $table->dropColumn('mac');
            }
        });
        Schema::table('comments', function (Blueprint $table) {
            if (Schema::hasColumn('comments', 'mac')) {
                $table->dropColumn('mac');
            }
        });
    }
};
