<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 16)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_id', 'status']);
            $table->index(['requester_id', 'status']);
            $table->index(['requester_id', 'recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};
