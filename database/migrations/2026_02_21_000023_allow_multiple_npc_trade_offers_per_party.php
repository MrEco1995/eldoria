<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_npc_trade_offers', function (Blueprint $table) {
            $table->dropUnique('party_npc_trade_offers_party_id_unique');
            $table->index('party_id', 'pnpc_party_idx');
        });
    }

    public function down(): void
    {
        Schema::table('party_npc_trade_offers', function (Blueprint $table) {
            $table->dropIndex('pnpc_party_idx');
            $table->unique('party_id');
        });
    }
};
