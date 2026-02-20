<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('starter_weapons', function (Blueprint $table) {
            $table->id();
            $table->string('race_name', 120)->nullable();
            $table->string('class_name', 60)->nullable();
            $table->string('race_key', 140);
            $table->string('class_key', 80);
            $table->string('weapon_name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['race_key', 'class_key']);
            $table->index(['is_active', 'race_key', 'class_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('starter_weapons');
    }
};

