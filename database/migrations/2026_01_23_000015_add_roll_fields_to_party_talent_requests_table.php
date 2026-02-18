<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_talent_requests', function (Blueprint $table) {
            $table->string('modifier_type', 16)->default('none')->after('talents');
            $table->unsignedTinyInteger('modifier_points')->default(0)->after('modifier_type');
            $table->string('rolled_talent_key', 120)->nullable()->after('status');
            $table->unsignedTinyInteger('rolled_value')->nullable()->after('rolled_talent_key');
            $table->unsignedTinyInteger('target_value')->nullable()->after('rolled_value');
            $table->boolean('is_success')->nullable()->after('target_value');
        });
    }

    public function down(): void
    {
        Schema::table('party_talent_requests', function (Blueprint $table) {
            $table->dropColumn([
                'modifier_type',
                'modifier_points',
                'rolled_talent_key',
                'rolled_value',
                'target_value',
                'is_success',
            ]);
        });
    }
};

