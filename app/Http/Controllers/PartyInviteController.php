<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\PartyInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PartyInviteController extends Controller
{
    public function join(Request $request, string $token): RedirectResponse
    {
        $hasStartedParty = $request->user()->parties()->whereNotNull('parties.started_at')->exists();
        if ($hasStartedParty) {
            return redirect()->route('lobby')->with('error', 'Du bist bereits in einer gestarteten Party.');
        }

        $invite = PartyInvite::query()->where('token', $token)->first();

        if (! $invite || $invite->expires_at->isPast()) {
            return redirect()->route('lobby')->with('error', 'Einladungslink ist abgelaufen.');
        }

        $party = $invite->party;
        $party->members()->syncWithoutDetaching([$request->user()->id]);

        if ($party->started_at) {
            return redirect()->route('parties.started', $party);
        }

        return redirect()->route('parties.show', $party);
    }

    public function regenerate(Request $request, Party $party): RedirectResponse
    {
        if ($party->owner_id !== $request->user()->id) {
            abort(403);
        }

        PartyInvite::query()->where('party_id', $party->id)->delete();

        PartyInvite::create([
            'party_id' => $party->id,
            'created_by' => $request->user()->id,
            'token' => Str::random(48),
            'expires_at' => now()->addMinutes(30),
        ]);

        return redirect()->route('parties.show', $party);
    }
}
