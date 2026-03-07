<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckDifficulty extends Model
{
    protected $fillable = [
        'key',
        'label',
        'sg',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sg' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
