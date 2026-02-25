<?php

namespace App\Http\Controllers;

use App\Events\PartyCharacterHpUpdated;
use App\Models\CharacterClass;
use App\Models\Party;
use App\Models\PartyCharacter;
use App\Models\Race;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartyCharacterHpController extends Controller
{
    public function update(Request $request, Party $party, PartyCharacter $partyCharacter): JsonResponse
    {
        abort_unless($party->owner_id === $request->user()->id, 403);
        abort_unless((int) $partyCharacter->party_id === (int) $party->id, 404);
        abort_unless((bool) $party->started_at, 409);

        $data = $request->validate([
            'action' => ['required', 'string', Rule::in(['damage', 'heal', 'set_temp', 'add_temp', 'reset_temp'])],
            'amount' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $amount = (int) ($data['amount'] ?? 0);
        $hpMax = (int) ($partyCharacter->hp_max ?? 0);
        if ($hpMax <= 0) {
            $raceHpBase = (int) (Race::query()->where('name', $partyCharacter->race)->value('hp_base') ?? 0);
            $classHpBase = (int) (CharacterClass::query()->where('name', $partyCharacter->class_name)->value('hp_base') ?? 0);
            $ausdauer = (int) data_get($partyCharacter->talents ?? [], 'ausdauer', 0);
            $hpMax = max(1, $raceHpBase + $classHpBase + intdiv(max(0, $ausdauer), 2));
        }
        $hpCurrent = max(0, min($hpMax, (int) ($partyCharacter->hp_current ?? $hpMax)));
        $hpTemp = max(0, (int) ($partyCharacter->hp_temp ?? 0));

        switch ($data['action']) {
            case 'damage':
                if ($amount > 0) {
                    $damageAfterTemp = max(0, $amount - $hpTemp);
                    $hpTemp = max(0, $hpTemp - $amount);
                    $hpCurrent = max(0, $hpCurrent - $damageAfterTemp);
                }
                break;
            case 'heal':
                if ($amount > 0) {
                    $hpCurrent = min($hpMax, $hpCurrent + $amount);
                }
                break;
            case 'set_temp':
                $hpTemp = max(0, $amount);
                break;
            case 'add_temp':
                if ($amount > 0) {
                    $hpTemp = max(0, $hpTemp + $amount);
                }
                break;
            case 'reset_temp':
                $hpTemp = 0;
                break;
        }

        $partyCharacter->update([
            'hp_max' => $hpMax,
            'hp_current' => $hpCurrent,
            'hp_temp' => $hpTemp,
        ]);

        $payload = [
            'hpMax' => $hpMax,
            'hpCurrent' => $hpCurrent,
            'hpTemp' => $hpTemp,
        ];

        if (config('realtime.enabled')) {
            try {
                event(new PartyCharacterHpUpdated($party->id, $partyCharacter->id, $payload));
            } catch (\Throwable $exception) {
                // Keep HP update functional without realtime.
            }
        }

        return response()->json([
            'ok' => true,
            'partyCharacterId' => (int) $partyCharacter->id,
            'hp' => $payload,
        ]);
    }
}
