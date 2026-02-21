<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class PartyCharacter extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_id',
        'user_id',
        'name',
        'race',
        'class_name',
        'gender',
        'age',
        'height_cm',
        'weight_kg',
        'traits',
        'talents',
    ];

    protected $casts = [
        'traits' => 'array',
        'talents' => 'array',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mediafiles(): MorphToMany
    {
        return $this->morphToMany(Mediafile::class, 'attachable', 'mediafile_attachments');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'party_character_id')->orderBy('sort_order')->orderBy('id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(CharacterWallet::class, 'party_character_id');
    }

    public function initiatedTradeSessions(): HasMany
    {
        return $this->hasMany(PartyTradeSession::class, 'initiator_party_character_id');
    }

    public function counterpartyTradeSessions(): HasMany
    {
        return $this->hasMany(PartyTradeSession::class, 'counterparty_party_character_id');
    }
}
