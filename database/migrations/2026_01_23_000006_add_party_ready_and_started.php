<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_user', function (Blueprint $table) {
            $table->boolean('is_ready')->default(false)->after('user_id');
        });

        Schema::table('parties', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('party_user', function (Blueprint $table) {
            $table->dropColumn('is_ready');
        });

        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn('started_at');
        });
    }
};
