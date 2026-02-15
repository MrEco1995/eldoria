<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\PartyCharacter;
use App\Models\Talent;
use App\Jobs\GenerateCharacterImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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

        $activeTalents = Talent::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['key', 'max_points']);

        $talentKeys = $activeTalents->pluck('key')->all();

        $rules = [
            'name' => ['required', 'string', 'max:80'],
            'race' => [
                'required',
                'string',
                'max:120',
                Rule::exists('races', 'name')->where('is_active', true),
            ],
            'class_name' => ['required', 'string', 'max:60'],
            'gender' => ['required', 'string', 'max:30'],
            'age' => ['required', 'integer', 'min:1', 'max:200'],
            'height_cm' => ['required', 'integer', 'min:50', 'max:250'],
            'weight_kg' => ['required', 'integer', 'min:20', 'max:300'],
            'traits' => ['required', 'array', 'min:1', 'max:4'],
            'traits.*' => ['string', 'max:40'],
            'talents' => ['required', 'array'],
        ];

        foreach ($activeTalents as $talent) {
            $rules["talents.{$talent->key}"] = ['required', 'integer', 'min:0', 'max:'.$talent->max_points];
        }

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request, $talentKeys) {
            $talents = (array) $request->input('talents', []);
            $submittedKeys = array_keys($talents);
            sort($submittedKeys);
            $expectedKeys = $talentKeys;
            sort($expectedKeys);

            if ($submittedKeys !== $expectedKeys) {
                $validator->errors()->add('talents', 'Ungültige Talente übermittelt.');
                return;
            }

            $totalPoints = array_sum(array_map('intval', $talents));
            $maxTotal = config('game.character_talent_point_pool', 35);
            if ($totalPoints > $maxTotal) {
                $validator->errors()->add('talents', "Du kannst maximal {$maxTotal} Talentpunkte verteilen.");
            }
        });

        $data = $validator->validate();

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
            'talents' => $data['talents'],
        ]);

        if ($character) {
            GenerateCharacterImage::dispatch($character->id);
        }

        return redirect()
            ->route('parties.show', $party)
            ->with('status', 'Charakter erstellt.');
    }
}
