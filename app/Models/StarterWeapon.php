<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StarterWeapon extends Model
{
    use HasFactory;

    protected $fillable = [
        'race_name',
        'class_name',
        'race_key',
        'class_key',
        'weapon_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

