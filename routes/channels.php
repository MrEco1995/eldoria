<?php

use App\Models\Party;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('party.{partyId}', function ($user, $partyId) {
    return Party::query()
        ->whereKey($partyId)
        ->whereHas('members', fn ($query) => $query->whereKey($user->id))
        ->exists();
});
