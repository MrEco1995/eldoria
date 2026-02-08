<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\PartyCharacter;
use App\Jobs\GenerateCharacterImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PartyCharacterController extends Controller
{
    public function store(Request $request, Party $party): RedirectResponse
    {
        $user = $request->user();

        abort_unless($party->members()->whereKey($user->id)->exists(), 403);

        if ($party->owner_id === $user->id) {
            return redirect()
                ->route('parties.show', $party)
                ->with('error', 'Owner kann keinen Charakter erstellen.');
        }

        $exists = PartyCharacter::query()
            ->where('party_id', $party->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return redirect()
                ->route('parties.show', $party)
                ->with('error', 'Du hast bereits einen Charakter in dieser Party.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'race' => ['required', 'string', 'max:60'],
            'class_name' => ['required', 'string', 'max:60'],
            'gender' => ['required', 'string', 'max:30'],
            'age' => ['required', 'integer', 'min:1', 'max:200'],
            'height_cm' => ['required', 'integer', 'min:50', 'max:250'],
            'weight_kg' => ['required', 'integer', 'min:20', 'max:300'],
            'traits' => ['required', 'array', 'min:1', 'max:4'],
            'traits.*' => ['string', 'max:40'],
        ]);

        $character = PartyCharacter::create([
            'party_id' => $party->id,
            'user_id' => $user->id,
            'name' => $data['name'],
            'race' => $data['race'],
            'class_name' => $data['class_name'],
            'gender' => $data['gender'],
            'age' => $data['age'],
            'height_cm' => $data['height_cm'],
            'weight_kg' => $data['weight_kg'],
            'traits' => $data['traits'],
        ]);

        if ($character) {
            GenerateCharacterImage::dispatch($character->id);
        }

        return redirect()
            ->route('parties.show', $party)
            ->with('status', 'Charakter erstellt.');
    }
}
