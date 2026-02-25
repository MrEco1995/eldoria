<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharacterClass extends Model
{
    use HasFactory;

    protected $table = 'character_classes';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'hp_base',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'hp_base' => 'integer',
        'is_active' => 'boolean',
    ];
}
