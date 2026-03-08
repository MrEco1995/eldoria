<?php

namespace App\Services;

use App\Events\PartyEnded;
use App\Models\Party;

class PartyLifecycleService
{
    public function endParty(Party $party, ?string $reason = null): bool
    {
        if (! $party->started_at) {
            return false;
        }

        // Reset ready-state so the next session starts cleanly.
        $party->members()->updateExistingPivot(
            $party->members()->pluck('users.id')->all(),
            ['is_ready' => false],
        );

        $party->update(['started_at' => null]);

        if (config('realtime.enabled')) {
            try {
                event(new PartyEnded(
                    partyId: (int) $party->id,
                    reason: $reason,
                ));
            } catch (\Throwable) {
                // Keep end action functional even if broadcast fails.
            }
        }

        return true;
    }

    public function endStartedPartiesForOwner(int $ownerUserId, ?string $reason = null): int
    {
        $parties = Party::query()
            ->where('owner_id', $ownerUserId)
            ->whereNotNull('started_at')
            ->get();

        $ended = 0;
        foreach ($parties as $party) {
            if ($this->endParty($party, $reason)) {
                $ended++;
            }
        }

        return $ended;
    }
}
