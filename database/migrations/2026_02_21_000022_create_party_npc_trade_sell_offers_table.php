<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_npc_trade_sell_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_npc_trade_offer_id')->constrained('party_npc_trade_offers')->cascadeOnDelete();
            $table->foreignId('party_character_id')->constrained('party_characters')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('amount_copper');
            $table->string('status', 20)->default('pending');
            $table->json('item_snapshot')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['party_npc_trade_offer_id', 'status'], 'pnpc_sell_offer_status_idx');
            $table->index(['party_character_id', 'inventory_item_id', 'status'], 'pnpc_sell_offer_char_item_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_npc_trade_sell_offers');
    }
};
