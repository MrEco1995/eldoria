<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_talent_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('difficulty_id')->nullable()->after('target_user_id');
            $table->string('difficulty_label', 120)->nullable()->after('difficulty_id');
            $table->unsignedTinyInteger('difficulty_sg')->default(12)->after('difficulty_label');

            $table->foreign('difficulty_id')
                ->references('id')
                ->on('check_difficulties')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('party_talent_requests', function (Blueprint $table) {
            $table->dropForeign(['difficulty_id']);
            $table->dropColumn([
                'difficulty_id',
                'difficulty_label',
                'difficulty_sg',
            ]);
        });
    }
};
