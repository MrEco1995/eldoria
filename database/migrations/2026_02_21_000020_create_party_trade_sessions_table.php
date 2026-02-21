<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_trade_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('initiator_party_character_id')->constrained('party_characters')->cascadeOnDelete();
            $table->foreignId('counterparty_party_character_id')->constrained('party_characters')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['party_id', 'status']);
            $table->index(['initiator_party_character_id', 'status']);
            $table->index(['counterparty_party_character_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_trade_sessions');
    }
};
