<?php

namespace App\Http\Controllers;

use App\Events\PartyWalletUpdated;
use App\Models\CharacterWallet;
use App\Models\Party;
use App\Models\PartyCharacter;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PartyWalletTransactionController extends Controller
{
    public function store(Request $request, Party $party): JsonResponse
    {
        $user = $request->user();
        abort_unless($party->members()->whereKey($user->id)->exists(), 403);

        $data = $request->validate([
            'party_character_id' => ['required', 'integer', Rule::exists('party_characters', 'id')->where('party_id', $party->id)],
            'type' => ['required', Rule::in([
                WalletTransaction::TYPE_IN,
                WalletTransaction::TYPE_OUT,
                'grant',
                'spend',
                'transfer_in',
                'transfer_out',
            ])],
            'amount_copper' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'amount_gold' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'amount_silver' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $amountCopper = $this->resolveAmountCopper($data);
        if ($amountCopper <= 0) {
            throw ValidationException::withMessages([
                'amount_copper' => ['Bitte einen Betrag groesser als 0 angeben.'],
            ]);
        }

        $type = $this->normalizeType((string) $data['type']);

        $character = PartyCharacter::query()->where('party_id', $party->id)->findOrFail($data['party_character_id']);
        $this->assertCanCreateTransaction($party, (int) $user->id);

        $result = DB::transaction(function () use ($character, $user, $data, $amountCopper, $type): array {
            $wallet = CharacterWallet::query()
                ->where('party_character_id', $character->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = CharacterWallet::create([
                    'party_character_id' => $character->id,
                    'copper_balance' => 0,
                ]);
            }

            $isIncoming = $type === WalletTransaction::TYPE_IN;
            $delta = $isIncoming ? $amountCopper : -$amountCopper;
            $nextBalance = (int) $wallet->copper_balance + $delta;

            if ($nextBalance < 0) {
                throw ValidationException::withMessages([
                    'amount_copper' => ['Nicht genug Kupfer im Beutel.'],
                ]);
            }

            $wallet->update([
                'copper_balance' => $nextBalance,
            ]);

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'actor_user_id' => $user->id,
                'type' => $type,
                'amount_copper' => $amountCopper,
                'note' => $data['note'] ?? null,
            ]);

            $transaction->load('actor:id,name');

            return [$wallet->fresh(), $transaction];
        });

        [$wallet, $transaction] = $result;

        $payload = [
            'partyCharacterId' => (int) $character->id,
            'wallet' => $this->toWalletPayload($wallet),
            'transaction' => $this->toTransactionPayload($transaction),
        ];

        if (config('realtime.enabled')) {
            try {
                event(new PartyWalletUpdated($party->id, $payload));
            } catch (\Throwable $exception) {
                // Keep wallet flow functional even if realtime fails.
            }
        }

        return response()->json([
            'ok' => true,
            ...$payload,
        ], 201);
    }

    private function assertCanCreateTransaction(Party $party, int $actorUserId): void
    {
        abort_unless((int) $party->owner_id === $actorUserId, 403, 'Wallet-Transaktionen sind nur im Handel erlaubt.');
    }

    private function toWalletPayload(CharacterWallet $wallet): array
    {
        $coins = $this->splitCopper((int) $wallet->copper_balance);

        return [
            'id' => (int) $wallet->id,
            'copperBalance' => (int) $wallet->copper_balance,
            'coins' => $coins,
            'display' => sprintf('%dG %dS %dK', $coins['gold'], $coins['silver'], $coins['copper']),
        ];
    }

    private function toTransactionPayload(WalletTransaction $transaction): array
    {
        $coins = $this->splitCopper((int) $transaction->amount_copper);

        return [
            'id' => (int) $transaction->id,
            'walletId' => (int) $transaction->wallet_id,
            'actorUserId' => $transaction->actor_user_id ? (int) $transaction->actor_user_id : null,
            'actorUserName' => $transaction->actor?->name,
            'type' => $transaction->type,
            'amountCopper' => (int) $transaction->amount_copper,
            'amountDisplay' => sprintf('%dG %dS %dK', $coins['gold'], $coins['silver'], $coins['copper']),
            'note' => $transaction->note,
            'createdAt' => optional($transaction->created_at)?->toIso8601String(),
        ];
    }

    private function splitCopper(int $copperBalance): array
    {
        $gold = intdiv($copperBalance, 100);
        $silver = intdiv($copperBalance % 100, 10);
        $copper = $copperBalance % 10;

        return [
            'gold' => $gold,
            'silver' => $silver,
            'copper' => $copper,
        ];
    }

    private function resolveAmountCopper(array $data): int
    {
        if (array_key_exists('amount_copper', $data) && $data['amount_copper'] !== null) {
            return (int) $data['amount_copper'];
        }

        $gold = (int) ($data['amount_gold'] ?? 0);
        $silver = (int) ($data['amount_silver'] ?? 0);

        return ($gold * 100) + ($silver * 10);
    }

    private function normalizeType(string $type): string
    {
        if (in_array($type, ['grant', 'transfer_in', WalletTransaction::TYPE_IN], true)) {
            return WalletTransaction::TYPE_IN;
        }

        return WalletTransaction::TYPE_OUT;
    }
}