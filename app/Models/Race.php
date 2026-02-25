<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Race extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'hp_base',
        'essence',
        'appearance',
        'age_text',
        'height_text',
        'weight_text',
        'good_with',
        'bad_with',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'essence' => 'array',
        'appearance' => 'array',
        'good_with' => 'array',
        'bad_with' => 'array',
        'hp_base' => 'integer',
        'is_active' => 'boolean',
    ];
}
