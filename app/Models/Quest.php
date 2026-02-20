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
        'chapter',
        'region',
        'summary',
        'objective',
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

