<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Events\PartyReadyUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class PartyMemberController extends Controller
{
    public function toggleReady(Request $request, Party $party): RedirectResponse
    {
        $user = $request->user();

        abort_unless($party->members()->whereKey($user->id)->exists(), 403);

        $current = $party->members()
            ->whereKey($user->id)
            ->first()
            ->pivot
            ->is_ready;

        $newReady = ! $current;
        $party->members()->updateExistingPivot($user->id, [
            'is_ready' => $newReady,
        ]);

        try {
            event(new PartyReadyUpdated($party->id, $user->id, $newReady));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('parties.show', $party);
    }

    public function leave(Request $request, Party $party): RedirectResponse
    {
        $user = $request->user();

        abort_unless($party->members()->whereKey($user->id)->exists(), 403);

        if ($party->owner_id === $user->id) {
            return redirect()
                ->route('parties.show', $party)
                ->with('error', 'Owner kann die Party nicht verlassen.');
        }

        $party->members()->detach($user->id);

        return redirect()->route('lobby')->with('status', 'Du hast die Party verlassen.');
    }

    public function remove(Request $request, Party $party, int $userId): RedirectResponse
    {
        if ($party->owner_id !== $request->user()->id) {
            abort(403);
        }

        if ($party->owner_id === $userId) {
            return redirect()
                ->route('parties.show', $party)
                ->with('error', 'Owner kann nicht entfernt werden.');
        }

        $party->members()->detach($userId);

        return redirect()->route('parties.show', $party)->with('status', 'Mitglied entfernt.');
    }
}
