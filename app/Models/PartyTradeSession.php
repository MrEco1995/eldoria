<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyTradeSession extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'party_id',
        'initiator_party_character_id',
        'counterparty_party_character_id',
        'status',
        'accepted_at',
        'closed_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function initiatorCharacter(): BelongsTo
    {
        return $this->belongsTo(PartyCharacter::class, 'initiator_party_character_id');
    }

    public function counterpartyCharacter(): BelongsTo
    {
        return $this->belongsTo(PartyCharacter::class, 'counterparty_party_character_id');
    }
}
