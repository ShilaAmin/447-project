<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('saved_items');
    }

    public function down(): void
    {
        // If you ever need to rollback, you can recreate minimal structure
        Schema::create('saved_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('item_id');
            $table->timestamps();

            // indexes (optional)
            $table->index(['user_id', 'item_id']);
        });
    }
};

