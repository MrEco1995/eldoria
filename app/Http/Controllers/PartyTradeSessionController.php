<?php

namespace App\Http\Controllers;

use App\Events\PartyTradeAccepted;
use App\Events\PartyTradeRequested;
use App\Models\Party;
use App\Models\PartyCharacter;
use App\Models\PartyTradeSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartyTradeSessionController extends Controller
{
    public function store(Request $request, Party $party): JsonResponse
    {
        $user = $request->user();
        abort_unless($party->members()->whereKey($user->id)->exists(), 403);

        $selfCharacter = PartyCharacter::query()
            ->where('party_id', $party->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $data = $request->validate([
            'counterparty_party_character_id' => [
                'required',
                'integer',
                Rule::exists('party_characters', 'id')->where('party_id', $party->id),
                Rule::notIn([$selfCharacter->id]),
            ],
        ]);

        $counterparty = PartyCharacter::query()
            ->where('party_id', $party->id)
            ->findOrFail($data['counterparty_party_character_id']);

        $existing = PartyTradeSession::query()
            ->where('party_id', $party->id)
            ->whereIn('status', [PartyTradeSession::STATUS_PENDING, PartyTradeSession::STATUS_ACTIVE])
            ->where(function ($query) use ($selfCharacter, $counterparty) {
                $query->where(function ($pair) use ($selfCharacter, $counterparty) {
                    $pair->where('initiator_party_character_id', $selfCharacter->id)
                        ->where('counterparty_party_character_id', $counterparty->id);
                })->orWhere(function ($pair) use ($selfCharacter, $counterparty) {
                    $pair->where('initiator_party_character_id', $counterparty->id)
                        ->where('counterparty_party_character_id', $selfCharacter->id);
                });
            })
            ->first();

        if (! $existing) {
            $existing = PartyTradeSession::create([
                'party_id' => $party->id,
                'initiator_party_character_id' => $selfCharacter->id,
                'counterparty_party_character_id' => $counterparty->id,
                'status' => PartyTradeSession::STATUS_PENDING,
            ]);
        }

        $existing->load([
            'initiatorCharacter.user:id,name',
            'counterpartyCharacter.user:id,name',
        ]);

        $payload = $this->toPayload($existing);

        if (config('realtime.enabled')) {
            try {
                event(new PartyTradeRequested($party->id, $payload));
            } catch (\Throwable $exception) {
                // Keep trade flow functional even if realtime fails.
            }
        }

        return response()->json([
            'ok' => true,
            'trade' => $payload,
        ], 201);
    }

    public function accept(Request $request, Party $party, PartyTradeSession $tradeSession): JsonResponse
    {
        $user = $request->user();
        abort_unless($party->members()->whereKey($user->id)->exists(), 403);
        abort_unless((int) $tradeSession->party_id === (int) $party->id, 404);

        $selfCharacter = PartyCharacter::query()
            ->where('party_id', $party->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        abort_unless((int) $tradeSession->counterparty_party_character_id === (int) $selfCharacter->id, 403);
        abort_unless($tradeSession->status === PartyTradeSession::STATUS_PENDING, 422);

        $tradeSession->update([
            'status' => PartyTradeSession::STATUS_ACTIVE,
            'accepted_at' => now(),
        ]);

        $tradeSession->load([
            'initiatorCharacter.user:id,name',
            'counterpartyCharacter.user:id,name',
        ]);

        $payload = $this->toPayload($tradeSession);

        if (config('realtime.enabled')) {
            try {
                event(new PartyTradeAccepted($party->id, $payload));
            } catch (\Throwable $exception) {
                // Keep trade flow functional even if realtime fails.
            }
        }

        return response()->json([
            'ok' => true,
            'trade' => $payload,
        ]);
    }

    private function toPayload(PartyTradeSession $tradeSession): array
    {
        return [
            'id' => (int) $tradeSession->id,
            'partyId' => (int) $tradeSession->party_id,
            'initiatorPartyCharacterId' => (int) $tradeSession->initiator_party_character_id,
            'counterpartyPartyCharacterId' => (int) $tradeSession->counterparty_party_character_id,
            'initiatorUserId' => (int) $tradeSession->initiatorCharacter->user_id,
            'counterpartyUserId' => (int) $tradeSession->counterpartyCharacter->user_id,
            'initiatorName' => $tradeSession->initiatorCharacter->user?->name ?? $tradeSession->initiatorCharacter->name,
            'counterpartyName' => $tradeSession->counterpartyCharacter->user?->name ?? $tradeSession->counterpartyCharacter->name,
            'status' => $tradeSession->status,
            'createdAt' => optional($tradeSession->created_at)?->toIso8601String(),
            'acceptedAt' => optional($tradeSession->accepted_at)?->toIso8601String(),
        ];
    }
}
