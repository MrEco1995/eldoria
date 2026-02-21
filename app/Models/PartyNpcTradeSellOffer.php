<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyNpcTradeSellOffer extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'party_npc_trade_offer_id',
        'party_character_id',
        'inventory_item_id',
        'created_by_user_id',
        'quantity',
        'amount_copper',
        'status',
        'item_snapshot',
        'resolved_by_user_id',
        'resolved_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'amount_copper' => 'integer',
        'item_snapshot' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function npcTradeOffer(): BelongsTo
    {
        return $this->belongsTo(PartyNpcTradeOffer::class, 'party_npc_trade_offer_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(PartyCharacter::class, 'party_character_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
