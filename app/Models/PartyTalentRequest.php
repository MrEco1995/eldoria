<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyTalentRequest extends Model
{
    protected $fillable = [
        'party_id',
        'owner_user_id',
        'target_user_id',
        'talents',
        'modifier_type',
        'modifier_points',
        'status',
        'rolled_talent_key',
        'rolled_value',
        'target_value',
        'is_success',
        'confirmed_at',
    ];

    protected $casts = [
        'talents' => 'array',
        'modifier_points' => 'integer',
        'rolled_value' => 'integer',
        'target_value' => 'integer',
        'is_success' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
