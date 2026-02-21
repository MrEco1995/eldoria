<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
{
    use HasFactory;

    protected $casts = [
        'started_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'owner_id',
        'started_at',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('is_ready')->withTimestamps();
    }

    public function invites(): HasMany
    {
        return $this->hasMany(PartyInvite::class);
    }

    public function characters(): HasMany
    {
        return $this->hasMany(PartyCharacter::class);
    }

    public function tradeSessions(): HasMany
    {
        return $this->hasMany(PartyTradeSession::class);
    }
}
