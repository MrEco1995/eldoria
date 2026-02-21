<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharacterWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_character_id',
        'copper_balance',
    ];

    protected $casts = [
        'copper_balance' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(PartyCharacter::class, 'party_character_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id')->latest('id');
    }
}
