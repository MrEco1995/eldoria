<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_npc_trade_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->unique()->constrained('parties')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 120)->nullable();
            $table->json('inventory_items')->nullable();
            $table->boolean('is_open')->default(false);
            $table->foreignId('active_party_character_id')->nullable()->constrained('party_characters')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['party_id', 'is_open'], 'pnpc_party_open_idx');
            $table->index(['active_party_character_id', 'is_open'], 'pnpc_active_open_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_npc_trade_offers');
    }
};
