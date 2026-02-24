<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quest extends Model
{
    use HasFactory;

    protected $table = 'quests';

    protected $fillable = [
        'key',
        'title',
        'location',
        'mood',
        'intro',
        'reward',
        'act_1',
        'act_2',
        'act_3',
        'decision_point',
        'ending_release',
        'ending_capture',
        'next_quest_release_title',
        'next_quest_capture_title',
        'recommended_party_level',
        'difficulty',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'recommended_party_level' => 'integer',
        'difficulty' => 'integer',
        'is_active' => 'boolean',
    ];
}
