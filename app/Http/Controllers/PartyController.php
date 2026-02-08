<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\PartyInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PartyController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        $user = request()->user();
        $hasStartedParty = $user->parties()->whereNotNull('parties.started_at')->exists();
        if ($hasStartedParty) {
            return redirect()->route('lobby')->with('error', 'Du bist bereits in einer gestarteten Party.');
        }

        return Inertia::render('Party/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $hasStartedParty = $request->user()->parties()->whereNotNull('parties.started_at')->exists();
        if ($hasStartedParty) {
            return redirect()->route('lobby')->with('error', 'Du bist bereits in einer gestarteten Party.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $party = Party::create([
            'name' => $data['name'],
            'owner_id' => $request->user()->id,
        ]);

        $party->members()->syncWithoutDetaching([$request->user()->id]);
        $this->createInvite($party, $request->user()->id);

        return redirect()->route('parties.show', $party);
    }

    public function show(Request $request, Party $party): Response|RedirectResponse
    {
        $userId = $request->user()->id;

        abort_unless($party->members()->whereKey($userId)->exists(), 403);

        if ($party->started_at) {
            return redirect()->route('parties.started', $party);
        }

        $invite = PartyInvite::query()
            ->where('party_id', $party->id)
            ->where('expires_at', '>', now())
            ->latest('expires_at')
            ->first();

        return Inertia::render('Party/Show', [
            'party' => [
                'id' => $party->id,
                'name' => $party->name,
                'owner' => [
                    'id' => $party->owner->id,
                    'name' => $party->owner->name,
                    'email' => $party->owner->email,
                ],
                'startedAt' => $party->started_at?->toIso8601String(),
            ],
            'members' => $party->members()
                ->select('users.id', 'users.name', 'users.email', 'party_user.is_ready')
                ->get(),
            'character' => $party->characters()
                ->where('user_id', $userId)
                ->select('id', 'name', 'race', 'class_name', 'gender', 'age', 'height_cm', 'weight_kg', 'traits')
                ->first(),
            'characters' => $party->characters()
                ->with('user:id,name')
                ->select('id', 'party_id', 'user_id', 'name', 'race', 'class_name', 'gender', 'age', 'height_cm', 'weight_kg', 'traits')
                ->get(),
            'invite' => $invite ? [
                'url' => route('parties.invites.join', $invite->token),
                'expiresAt' => $invite->expires_at->toIso8601String(),
            ] : null,
            'isOwner' => $party->owner_id === $userId,
        ]);
    }

    public function start(Request $request, Party $party): RedirectResponse
    {
        if ($party->owner_id !== $request->user()->id) {
            abort(403);
        }

        $notReadyCount = $party->members()->wherePivot('is_ready', false)->count();
        if ($notReadyCount > 0) {
            return redirect()
                ->route('parties.show', $party)
                ->with('error', 'Nicht alle Mitglieder sind bereit.');
        }

        $memberIds = $party->members()
            ->where('users.id', '!=', $party->owner_id)
            ->pluck('users.id');
        $characterCount = $party->characters()
            ->whereIn('user_id', $memberIds)
            ->count();
        if ($characterCount !== $memberIds->count()) {
            return redirect()
                ->route('parties.show', $party)
                ->with('error', 'Nicht alle Spieler haben einen Charakter erstellt.');
        }

        $party->update([
            'started_at' => now(),
        ]);

        return redirect()->route('parties.started', $party);
    }

    public function started(Request $request, Party $party): Response|RedirectResponse
    {
        $userId = $request->user()->id;

        abort_unless($party->members()->whereKey($userId)->exists(), 403);

        if (! $party->started_at) {
            return redirect()->route('parties.show', $party);
        }

        $payload = [
            'party' => [
                'id' => $party->id,
                'name' => $party->name,
                'startedAt' => $party->started_at?->toIso8601String(),
            ],
            'members' => $party->members()
                ->select('users.id', 'users.name', 'users.email', 'party_user.is_ready')
                ->get(),
        ];

        if ($party->owner_id === $userId) {
            return Inertia::render('Party/StartedOwner', $payload);
        }

        return Inertia::render('Party/StartedMember', $payload);
    }

    public function close(Request $request, Party $party): RedirectResponse
    {
        if ($party->owner_id !== $request->user()->id) {
            abort(403);
        }

        $memberCount = $party->members()->count();
        if ($memberCount > 1) {
            return redirect()
                ->route('parties.show', $party)
                ->with('error', 'Die Party kann nur geschlossen werden, wenn nur der Owner uebrig ist.');
        }

        $party->delete();

        return redirect()->route('lobby')->with('status', 'Party wurde geschlossen.');
    }

    private function createInvite(Party $party, int $userId): PartyInvite
    {
        return PartyInvite::create([
            'party_id' => $party->id,
            'created_by' => $userId,
            'token' => Str::random(48),
            'expires_at' => now()->addMinutes(30),
        ]);
    }
}
