<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_characters', function (Blueprint $table) {
            $table->json('talents')->nullable()->after('traits');
        });
    }

    public function down(): void
    {
        Schema::table('party_characters', function (Blueprint $table) {
            $table->dropColumn('talents');
        });
    }
};
