<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyNpcTradeOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_id',
        'created_by',
        'name',
        'inventory_items',
        'is_open',
        'active_party_character_id',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'inventory_items' => 'array',
        'is_open' => 'boolean',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function activeCharacter(): BelongsTo
    {
        return $this->belongsTo(PartyCharacter::class, 'active_party_character_id');
    }
}
