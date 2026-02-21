<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;

    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';

    protected $fillable = [
        'wallet_id',
        'actor_user_id',
        'type',
        'amount_copper',
        'note',
    ];

    protected $casts = [
        'amount_copper' => 'integer',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(CharacterWallet::class, 'wallet_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
