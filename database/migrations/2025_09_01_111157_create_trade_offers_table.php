<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trade_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_request_id')->constrained()->onDelete('cascade');

            // who sent and who receives this offer
            $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('to_user_id')->constrained('users')->onDelete('cascade');

            // optional: offer an item back (barter) or just propose terms
            $table->foreignId('offered_item_id')->nullable()->constrained('items')->onDelete('set null');

            // optional cash adjustment (e.g., +$10 with item)
            $table->decimal('cash_adjustment', 10, 2)->nullable();

            $table->text('message')->nullable();

            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');

            // If you want threaded counters later, you can add parent_offer_id (nullable)
            // $table->foreignId('parent_offer_id')->nullable()->constrained('trade_offers')->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_offers');
    }
};
