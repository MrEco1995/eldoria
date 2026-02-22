<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointOfInterest extends Model
{
    use HasFactory;

    protected $table = 'points_of_interest';

    protected $fillable = [
        'slug',
        'name',
        'type',
        'x_percent',
        'y_percent',
        'min_zoom',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'x_percent' => 'float',
        'y_percent' => 'float',
        'min_zoom' => 'float',
        'is_active' => 'boolean',
    ];
}
