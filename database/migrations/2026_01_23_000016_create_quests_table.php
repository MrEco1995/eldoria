<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quests')) {
            return;
        }

        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('title', 160);
            $table->string('chapter', 120)->nullable();
            $table->string('region', 120)->nullable();
            $table->text('summary')->nullable();
            $table->text('objective')->nullable();
            $table->unsignedTinyInteger('recommended_party_level')->nullable();
            $table->unsignedTinyInteger('difficulty')->default(2);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('quests')) {
            return;
        }

        Schema::dropIfExists('quests');
    }
};
