<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_character_id')->unique()->constrained('party_characters')->cascadeOnDelete();
            $table->integer('copper_balance')->default(0);
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('character_wallets')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20);
            $table->unsignedInteger('amount_copper');
            $table->string('note', 1000)->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'id']);
            $table->index('type');
        });

        $characterIds = DB::table('party_characters')->pluck('id');
        if ($characterIds->isNotEmpty()) {
            $now = now();
            DB::table('character_wallets')->insert(
                $characterIds->map(fn (int $characterId) => [
                    'party_character_id' => $characterId,
                    'copper_balance' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('character_wallets');
    }
};
