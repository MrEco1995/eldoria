<?php

namespace App\Http\Controllers;

use App\Events\PartyTalentRequestConfirmed;
use App\Events\PartyTalentRequestCreated;
use App\Models\CheckDifficulty;
use App\Models\Party;
use App\Models\PartyTalentRequest;
use App\Models\PartyCharacter;
use App\Models\Talent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartyTalentRequestController extends Controller
{
    public function store(Request $request, Party $party): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        abort_unless($party->owner_id === $user->id, 403);
        abort_unless((bool) $party->started_at, 409);

        $talentKeys = Talent::query()
            ->where('is_active', true)
            ->pluck('label', 'key');
        $difficultyIds = CheckDifficulty::query()
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $data = $request->validate([
            'target_user_id' => [
                'required',
                'integer',
                Rule::exists('party_user', 'user_id')->where(fn ($query) => $query->where('party_id', $party->id)),
            ],
            'talents' => ['required', 'array', 'min:1'],
            'talents.*' => ['required', 'string', Rule::in($talentKeys->keys()->all())],
            'difficulty_id' => ['required', 'integer', Rule::in($difficultyIds)],
            'modifier_type' => ['required', 'string', Rule::in(['none', 'easy', 'hard'])],
            'modifier_points' => ['required', 'integer', 'min:0', 'max:5'],
        ]);

        if ((int) $data['target_user_id'] === (int) $party->owner_id) {
            return back()->with('error', 'Owner kann keine Talentanforderung an sich selbst senden.');
        }

        $targetUser = $party->members()->whereKey($data['target_user_id'])->firstOrFail();
        $uniqueTalentKeys = array_values(array_unique($data['talents']));

        $talents = array_map(function (string $key) use ($talentKeys) {
            return [
                'key' => $key,
                'label' => $talentKeys[$key] ?? $key,
                'rolledRaw' => null,
                'rolledValue' => null,
                'targetValue' => null,
                'isSuccess' => null,
                'rolledAt' => null,
            ];
        }, $uniqueTalentKeys);

        $difficulty = CheckDifficulty::query()
            ->where('is_active', true)
            ->whereKey($data['difficulty_id'])
            ->firstOrFail();

        $talentRequest = PartyTalentRequest::create([
            'party_id' => $party->id,
            'owner_user_id' => $user->id,
            'target_user_id' => $targetUser->id,
            'difficulty_id' => (int) $difficulty->id,
            'difficulty_label' => $difficulty->label,
            'difficulty_sg' => (int) $difficulty->sg,
            'talents' => $talents,
            'modifier_type' => $data['modifier_type'],
            'modifier_points' => (int) $data['modifier_points'],
            'status' => 'pending',
        ]);

        $payload = $this->toPayload($talentRequest, $user->name, $targetUser->name);
        $this->broadcastCreated($party->id, $payload);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'request' => $payload], 201);
        }

        return back()->with('status', 'Talentanforderung wurde gesendet.');
    }

    public function confirm(Request $request, Party $party, PartyTalentRequest $talentRequest): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        abort_unless($talentRequest->party_id === $party->id, 404);
        abort_unless((int) $talentRequest->target_user_id === (int) $user->id, 403);
        abort_if($talentRequest->status === 'confirmed', 409, 'Diese Anfrage ist bereits abgeschlossen.');

        $data = $request->validate([
            'rolled_talent_key' => ['required', 'string'],
            'rolled_value' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $talents = $this->normalizeTalents($talentRequest->talents ?? []);
        $talentIndex = collect($talents)->search(
            fn (array $talent) => ($talent['key'] ?? null) === $data['rolled_talent_key']
        );
        abort_unless($talentIndex !== false, 422);
        abort_if(!empty($talents[$talentIndex]['rolledAt']), 409, 'Dieses Talent wurde bereits gewürfelt.');

        $character = PartyCharacter::query()
            ->where('party_id', $party->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $baseValue = (int) data_get($character->talents ?? [], $data['rolled_talent_key'], 0);
        $modifier = match ($talentRequest->modifier_type) {
            'easy' => (int) $talentRequest->modifier_points,
            'hard' => -1 * (int) $talentRequest->modifier_points,
            default => 0,
        };
        $difficultySg = max(1, (int) ($talentRequest->difficulty_sg ?? 12));
        $rolledRaw = (int) $data['rolled_value'];
        $rolledTotal = max(0, $rolledRaw + $baseValue + $modifier);
        $isSuccess = $rolledTotal >= $difficultySg;

        $talents[$talentIndex]['rolledRaw'] = $rolledRaw;
        $talents[$talentIndex]['rolledValue'] = $rolledTotal;
        $talents[$talentIndex]['targetValue'] = $difficultySg;
        $talents[$talentIndex]['isSuccess'] = $isSuccess;
        $talents[$talentIndex]['rolledAt'] = now()->toIso8601String();

        $allRolled = collect($talents)->every(fn (array $talent) => !empty($talent['rolledAt']));

        $talentRequest->update([
            'talents' => $talents,
            'status' => $allRolled ? 'confirmed' : 'pending',
            'rolled_talent_key' => $data['rolled_talent_key'],
            'rolled_value' => $rolledRaw,
            'target_value' => $difficultySg,
            'is_success' => $isSuccess,
            'confirmed_at' => $allRolled ? now() : null,
        ]);

        $ownerName = optional($party->members()->whereKey($talentRequest->owner_user_id)->first())->name ?? 'Spielleiter';
        $payload = $this->toPayload($talentRequest->refresh(), $ownerName, $user->name);
        $this->broadcastConfirmed($party->id, $payload);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'request' => $payload], 200);
        }

        return back()->with('status', 'Wurf wurde bestätigt.');
    }

    private function toPayload(PartyTalentRequest $request, string $ownerName, string $targetName): array
    {
        return [
            'id' => $request->id,
            'partyId' => $request->party_id,
            'ownerUserId' => $request->owner_user_id,
            'ownerUserName' => $ownerName,
            'targetUserId' => $request->target_user_id,
            'targetUserName' => $targetName,
            'difficultyId' => $request->difficulty_id,
            'difficultyLabel' => $request->difficulty_label ?: 'Normal',
            'difficultySg' => (int) ($request->difficulty_sg ?? 12),
            'talents' => $this->normalizeTalents($request->talents ?? []),
            'modifierType' => $request->modifier_type,
            'modifierPoints' => (int) $request->modifier_points,
            'status' => $request->status,
            'rolledTalentKey' => $request->rolled_talent_key,
            'rolledValue' => $request->rolled_value,
            'targetValue' => $request->target_value,
            'isSuccess' => $request->is_success,
            'createdAt' => optional($request->created_at)?->toIso8601String(),
            'confirmedAt' => optional($request->confirmed_at)?->toIso8601String(),
        ];
    }

    private function normalizeTalents(array $talents): array
    {
        return array_values(array_map(function (array $talent) {
            return [
                'key' => $talent['key'] ?? '',
                'label' => $talent['label'] ?? ($talent['key'] ?? ''),
                'rolledRaw' => $talent['rolledRaw'] ?? null,
                'rolledValue' => $talent['rolledValue'] ?? null,
                'targetValue' => $talent['targetValue'] ?? null,
                'isSuccess' => $talent['isSuccess'] ?? null,
                'rolledAt' => $talent['rolledAt'] ?? null,
            ];
        }, $talents));
    }

    private function broadcastCreated(int $partyId, array $payload): void
    {
        if (!config('realtime.enabled')) {
            return;
        }

        try {
            event(new PartyTalentRequestCreated($partyId, $payload));
        } catch (\Throwable $exception) {
            // keep request flow functional without realtime
        }
    }

    private function broadcastConfirmed(int $partyId, array $payload): void
    {
        if (!config('realtime.enabled')) {
            return;
        }

        try {
            event(new PartyTalentRequestConfirmed($partyId, $payload));
        } catch (\Throwable $exception) {
            // keep request flow functional without realtime
        }
    }
}
