<?php

namespace App\Http\Controllers;

use App\Events\PartyStarted;
use App\Models\Party;
use App\Models\PartyInvite;
use App\Services\PartyViewDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PartyController extends Controller
{
    public function __construct(private readonly PartyViewDataService $partyViewDataService)
    {
    }

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

        return redirect()
            ->route('parties.show', $party)
            ->with('status', 'Party wurde erfolgreich erstellt.');
    }

    public function show(Request $request, Party $party): Response|RedirectResponse
    {
        $userId = $request->user()->id;

        abort_unless($party->members()->whereKey($userId)->exists(), 403);

        if ($party->started_at) {
            return redirect()->route('parties.started', $party);
        }

        return Inertia::render('Party/Show', $this->partyViewDataService->buildShowPayload($party, $userId));
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
        $party->refresh();

        if (config('realtime.enabled')) {
            try {
                event(new PartyStarted(
                    partyId: $party->id,
                    startedAt: $party->started_at->toIso8601String(),
                ));
            } catch (\Throwable $exception) {
                // Keep start action functional even if broadcast fails.
            }
        }

        return redirect()
            ->route('parties.started', $party)
            ->with('status', 'Party wurde gestartet.');
    }

    public function started(Request $request, Party $party): Response|RedirectResponse
    {
        $userId = $request->user()->id;

        abort_unless($party->members()->whereKey($userId)->exists(), 403);

        if (! $party->started_at) {
            return redirect()->route('parties.show', $party);
        }

        $payload = $this->partyViewDataService->buildStartedPayload($party, $userId);

        if ($party->owner_id === $userId) {
            return Inertia::render('Party/StartedOwner', $payload);
        }

        $ownCharacter = collect($payload['characters'])->firstWhere('user_id', $userId);
        if (! $ownCharacter) {
            return redirect()
                ->route('parties.show', $party)
                ->with('error', 'Dein Charakter wurde nicht gefunden.');
        }

        $payload['character'] = $ownCharacter;

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

    public function end(Request $request, Party $party): RedirectResponse
    {
        if ($party->owner_id !== $request->user()->id) {
            abort(403);
        }

        if (! $party->started_at) {
            return redirect()
                ->route('parties.show', $party)
                ->with('status', 'Party ist bereits nicht gestartet.');
        }

        $party->update([
            'started_at' => null,
        ]);

        return redirect()
            ->route('parties.show', $party)
            ->with('status', 'Party wurde beendet.');
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
