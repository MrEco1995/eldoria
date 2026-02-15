<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('races', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('slug', 140)->unique();
            $table->text('description');
            $table->json('essence');
            $table->json('appearance');
            $table->string('age_text', 180);
            $table->string('height_text', 180);
            $table->string('weight_text', 180);
            $table->json('good_with')->nullable();
            $table->json('bad_with')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('races');
    }
};
