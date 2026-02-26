<?php

use App\Models\Party;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('party.{partyId}', function ($user, $partyId) {
    return Party::query()
        ->whereKey($partyId)
        ->whereHas('members', fn ($query) => $query->whereKey($user->id))
        ->exists();
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('online', function ($user) {
    return [
        'id' => (int) $user->id,
        'name' => (string) $user->name,
    ];
});
