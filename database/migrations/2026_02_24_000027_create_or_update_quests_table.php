<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quests')) {
            Schema::create('quests', function (Blueprint $table) {
                $table->id();
                $table->string('key', 120)->unique();
                $table->string('title', 180);
                $table->string('location', 180)->nullable();
                $table->string('mood', 180)->nullable();
                $table->text('intro')->nullable();
                $table->text('reward')->nullable();
                $table->longText('act_1')->nullable();
                $table->longText('act_2')->nullable();
                $table->longText('act_3')->nullable();
                $table->text('decision_point')->nullable();
                $table->string('ending_release_label', 180)->nullable();
                $table->longText('ending_release')->nullable();
                $table->string('ending_capture_label', 180)->nullable();
                $table->longText('ending_capture')->nullable();
                $table->string('next_quest_release_title', 180)->nullable();
                $table->string('next_quest_capture_title', 180)->nullable();
                $table->unsignedTinyInteger('recommended_party_level')->nullable();
                $table->unsignedTinyInteger('difficulty')->default(2);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });

            return;
        }

        Schema::table('quests', function (Blueprint $table) {
            if (!Schema::hasColumn('quests', 'location')) {
                $table->string('location', 180)->nullable()->after('title');
            }
            if (!Schema::hasColumn('quests', 'mood')) {
                $table->string('mood', 180)->nullable()->after('location');
            }
            if (!Schema::hasColumn('quests', 'intro')) {
                $table->text('intro')->nullable()->after('mood');
            }
            if (!Schema::hasColumn('quests', 'reward')) {
                $table->text('reward')->nullable()->after('intro');
            }
            if (!Schema::hasColumn('quests', 'act_1')) {
                $table->longText('act_1')->nullable()->after('reward');
            }
            if (!Schema::hasColumn('quests', 'act_2')) {
                $table->longText('act_2')->nullable()->after('act_1');
            }
            if (!Schema::hasColumn('quests', 'act_3')) {
                $table->longText('act_3')->nullable()->after('act_2');
            }
            if (!Schema::hasColumn('quests', 'decision_point')) {
                $table->text('decision_point')->nullable()->after('act_3');
            }
            if (!Schema::hasColumn('quests', 'ending_release_label')) {
                $table->string('ending_release_label', 180)->nullable()->after('decision_point');
            }
            if (!Schema::hasColumn('quests', 'ending_release')) {
                $table->longText('ending_release')->nullable()->after('ending_release_label');
            }
            if (!Schema::hasColumn('quests', 'ending_capture_label')) {
                $table->string('ending_capture_label', 180)->nullable()->after('ending_release');
            }
            if (!Schema::hasColumn('quests', 'ending_capture')) {
                $table->longText('ending_capture')->nullable()->after('ending_capture_label');
            }
            if (!Schema::hasColumn('quests', 'next_quest_release_title')) {
                $table->string('next_quest_release_title', 180)->nullable()->after('ending_capture');
            }
            if (!Schema::hasColumn('quests', 'next_quest_capture_title')) {
                $table->string('next_quest_capture_title', 180)->nullable()->after('next_quest_release_title');
            }
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
