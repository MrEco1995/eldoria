<?php

namespace App\Http\Controllers;

use App\Events\PartyRollCreated;
use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PartyRollController extends Controller
{
    public function store(Request $request, Party $party): JsonResponse
    {
        $user = $request->user();

        abort_unless($party->members()->whereKey($user->id)->exists(), 403);

        $data = $request->validate([
            'die' => ['required', 'string', Rule::in(['W20', 'W6'])],
            'result' => ['required', 'integer', 'min:1'],
        ]);

        $max = $data['die'] === 'W20' ? 20 : 6;
        if ($data['result'] > $max) {
            throw ValidationException::withMessages([
                'result' => 'Ungueltiges Wuerfelergebnis.',
            ]);
        }

        if (config('realtime.enabled')) {
            try {
                event(new PartyRollCreated(
                    partyId: $party->id,
                    userId: $user->id,
                    userName: $user->name,
                    die: $data['die'],
                    result: $data['result'],
                ));
            } catch (\Throwable $exception) {
                // Ignore broadcast failures to keep roll endpoint functional.
            }
        }

        return response()->json(['ok' => true], 201);
    }
}
