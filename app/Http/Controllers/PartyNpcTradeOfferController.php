<?php

namespace App\Http\Controllers;

use App\Events\PartyInventoryItemUpdated;
use App\Events\PartyNpcTradeUpdated;
use App\Events\PartyWalletUpdated;
use App\Models\CharacterWallet;
use App\Models\InventoryItem;
use App\Models\Party;
use App\Models\PartyCharacter;
use App\Models\PartyNpcTradeOffer;
use App\Models\PartyNpcTradeSellOffer;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PartyNpcTradeOfferController extends Controller
{
    public function upsert(Request $request, Party $party): JsonResponse
    {
        $user = $request->user();
        $this->assertOwner($party, (int) $user->id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1', 'max:150'],
            'items.*.name' => ['required', 'string', 'max:120'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.price_copper' => ['required', 'integer', 'min:1', 'max:100000000'],
            'items.*.category' => ['nullable', 'string', 'max:80'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $offer = PartyNpcTradeOffer::query()->firstOrCreate(
            ['party_id' => $party->id],
            ['created_by' => $user->id]
        );

        $offer->update([
            'name' => $data['name'],
            'inventory_items' => $this->normalizeItemsForStorage($data['items']),
        ]);

        $offer->load($this->offerRelations());
        $payload = $this->toPayload($offer);
        $this->broadcastUpdate($party->id, $payload);

        return response()->json([
            'ok' => true,
            'offer' => $payload,
        ]);
    }

    public function open(Request $request, Party $party): JsonResponse
    {
        $user = $request->user();
        $this->assertOwner($party, (int) $user->id);

        $offer = PartyNpcTradeOffer::query()->firstOrCreate(
            ['party_id' => $party->id],
            ['created_by' => $user->id]
        );

        $items = $this->normalizeItemsForStorage((array) ($offer->inventory_items ?? []));
        if (empty($offer->name) || empty($items)) {
            return response()->json([
                'ok' => false,
                'message' => 'Bitte zuerst NPC-Name und Items konfigurieren.',
            ], 422);
        }

        $offer->update([
            'is_open' => true,
            'opened_at' => now(),
            'closed_at' => null,
        ]);

        $offer->load($this->offerRelations());
        $payload = $this->toPayload($offer);
        $this->broadcastUpdate($party->id, $payload);

        return response()->json([
            'ok' => true,
            'offer' => $payload,
        ]);
    }

    public function close(Request $request, Party $party): JsonResponse
    {
        $user = $request->user();
        $this->assertOwner($party, (int) $user->id);

        $offer = PartyNpcTradeOffer::query()->where('party_id', $party->id)->first();
        if (! $offer) {
            return response()->json(['ok' => true, 'offer' => null]);
        }

        $offer->update([
            'is_open' => false,
            'active_party_character_id' => null,
            'closed_at' => now(),
        ]);

        PartyNpcTradeSellOffer::query()
            ->where('party_npc_trade_offer_id', $offer->id)
            ->where('status', PartyNpcTradeSellOffer::STATUS_PENDING)
            ->update([
                'status' => PartyNpcTradeSellOffer::STATUS_REJECTED,
                'resolved_by_user_id' => $user->id,
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);

        $offer->load($this->offerRelations());
        $payload = $this->toPayload($offer);
        $this->broadcastUpdate($party->id, $payload);

        return response()->json([
            'ok' => true,
            'offer' => $payload,
        ]);
    }

    public function claim(Request $request, Party $party): JsonResponse
    {
        $user = $request->user();
        abort_unless($party->members()->whereKey($user->id)->exists(), 403);

        $character = PartyCharacter::query()
            ->where('party_id', $party->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $offer = DB::transaction(function () use ($party, $character) {
            $entry = PartyNpcTradeOffer::query()
                ->where('party_id', $party->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($entry->is_open, 422, 'NPC ist nicht zum Handeln freigegeben.');

            if ($entry->active_party_character_id && (int) $entry->active_party_character_id !== (int) $character->id) {
                abort(422, 'NPC handelt bereits mit einem anderen Spieler.');
            }

            $entry->update([
                'active_party_character_id' => $character->id,
            ]);

            return $entry;
        });

        $offer->load($this->offerRelations());
        $payload = $this->toPayload($offer);
        $this->broadcastUpdate($party->id, $payload);

        return response()->json([
            'ok' => true,
            'offer' => $payload,
        ]);
    }

    public function release(Request $request, Party $party): JsonResponse
    {
        $user = $request->user();
        abort_unless($party->members()->whereKey($user->id)->exists(), 403);

        $character = PartyCharacter::query()
            ->where('party_id', $party->id)
            ->where('user_id', $user->id)
            ->first();

        $offer = PartyNpcTradeOffer::query()->where('party_id', $party->id)->firstOrFail();

        $isOwner = (int) $party->owner_id === (int) $user->id;
        $isActiveCharacter = $character && (int) $offer->active_party_character_id === (int) $character->id;
        abort_unless($isOwner || $isActiveCharacter, 403);

        $offer->update([
            'active_party_character_id' => null,
        ]);

        if ($character) {
            PartyNpcTradeSellOffer::query()
                ->where('party_npc_trade_offer_id', $offer->id)
                ->where('party_character_id', $character->id)
                ->where('status', PartyNpcTradeSellOffer::STATUS_PENDING)
                ->update([
                    'status' => PartyNpcTradeSellOffer::STATUS_REJECTED,
                    'resolved_by_user_id' => $isOwner ? $user->id : null,
                    'resolved_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $offer->load($this->offerRelations());
        $payload = $this->toPayload($offer);
        $this->broadcastUpdate($party->id, $payload);

        return response()->json([
            'ok' => true,
            'offer' => $payload,
        ]);
    }
    public function buy(Request $request, Party $party): JsonResponse
    {
        $user = $request->user();
        abort_unless($party->members()->whereKey($user->id)->exists(), 403);

        $character = PartyCharacter::query()
            ->where('party_id', $party->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $data = $request->validate([
            'item_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $result = DB::transaction(function () use ($party, $character, $data, $user): array {
            $offer = PartyNpcTradeOffer::query()
                ->where('party_id', $party->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($offer->is_open, 422, 'NPC ist nicht zum Handeln freigegeben.');
            abort_unless((int) $offer->active_party_character_id === (int) $character->id, 403, 'Du handelst gerade nicht mit diesem NPC.');

            $items = $this->normalizeItemsForStorage($offer->inventory_items ?? []);
            $itemIndex = collect($items)->search(fn (array $item) => (int) $item['id'] === (int) $data['item_id']);
            if ($itemIndex === false) {
                abort(422, 'Dieses NPC-Item ist nicht mehr verfuegbar.');
            }

            $buyQuantity = (int) $data['quantity'];
            $currentQuantity = (int) ($items[$itemIndex]['quantity'] ?? 0);
            if ($buyQuantity > $currentQuantity) {
                abort(422, 'Nicht genug Menge beim NPC verfuegbar.');
            }

            $unitPriceCopper = (int) ($items[$itemIndex]['price_copper'] ?? 0);
            if ($unitPriceCopper <= 0) {
                abort(422, 'Dieses NPC-Item hat keinen gueltigen Preis.');
            }

            $totalPriceCopper = $buyQuantity * $unitPriceCopper;
            $wallet = $this->resolveWalletForCharacter($character->id, true);
            $nextWalletBalance = (int) $wallet->copper_balance - $totalPriceCopper;
            if ($nextWalletBalance < 0) {
                throw ValidationException::withMessages([
                    'amount_copper' => ['Nicht genug Kupfer im Beutel.'],
                ]);
            }

            $wallet->update(['copper_balance' => $nextWalletBalance]);

            $walletTransaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'actor_user_id' => $user->id,
                'type' => WalletTransaction::TYPE_OUT,
                'amount_copper' => $totalPriceCopper,
                'note' => sprintf('Kauf bei NPC %s: %s x%d', $offer->name, (string) ($items[$itemIndex]['name'] ?? 'Item'), $buyQuantity),
            ]);
            $walletTransaction->load('actor:id,name');

            $items[$itemIndex]['quantity'] = $currentQuantity - $buyQuantity;
            $purchasedItem = $items[$itemIndex];
            if ((int) $items[$itemIndex]['quantity'] <= 0) {
                unset($items[$itemIndex]);
            }

            $offer->update([
                'inventory_items' => array_values($items),
            ]);

            $playerInventoryItem = $this->addItemToPlayerInventory($character, $purchasedItem, $buyQuantity);

            return [
                $offer->fresh($this->offerRelations()),
                $playerInventoryItem,
                $wallet->fresh(),
                $walletTransaction,
            ];
        });

        [$offer, $playerInventoryItem, $wallet, $walletTransaction] = $result;

        $payload = $this->toPayload($offer);
        $this->broadcastUpdate($party->id, $payload);

        $this->broadcastInventoryItemUpsert($party->id, $playerInventoryItem, false);
        $this->broadcastWalletUpdate(
            $party->id,
            (int) $character->id,
            $this->toWalletPayload($wallet),
            $this->toWalletTransactionPayload($walletTransaction)
        );

        return response()->json([
            'ok' => true,
            'offer' => $payload,
            'inventoryItem' => $this->toInventoryItemPayload($playerInventoryItem),
            'wallet' => $this->toWalletPayload($wallet),
            'transaction' => $this->toWalletTransactionPayload($walletTransaction),
        ]);
    }

    public function storeSellOffer(Request $request, Party $party): JsonResponse
    {
        $user = $request->user();
        abort_unless($party->members()->whereKey($user->id)->exists(), 403);

        $character = PartyCharacter::query()
            ->where('party_id', $party->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $data = $request->validate([
            'inventory_item_id' => ['required', 'integer', Rule::exists('inventory_items', 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'amount_copper' => ['required', 'integer', 'min:1', 'max:100000000'],
        ]);

        $offer = DB::transaction(function () use ($party, $character, $data, $user): PartyNpcTradeOffer {
            $npcTradeOffer = PartyNpcTradeOffer::query()
                ->where('party_id', $party->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($npcTradeOffer->is_open, 422, 'NPC ist nicht zum Handeln freigegeben.');
            abort_unless((int) $npcTradeOffer->active_party_character_id === (int) $character->id, 403, 'Du handelst gerade nicht mit diesem NPC.');

            $inventoryItem = InventoryItem::query()
                ->where('party_character_id', $character->id)
                ->lockForUpdate()
                ->findOrFail((int) $data['inventory_item_id']);

            $requestedQuantity = (int) $data['quantity'];
            if ($requestedQuantity > (int) $inventoryItem->quantity) {
                abort(422, 'Nicht genug Menge im Inventar.');
            }

            $existingPending = PartyNpcTradeSellOffer::query()
                ->where('party_npc_trade_offer_id', $npcTradeOffer->id)
                ->where('party_character_id', $character->id)
                ->where('inventory_item_id', (int) $inventoryItem->id)
                ->where('status', PartyNpcTradeSellOffer::STATUS_PENDING)
                ->exists();

            if ($existingPending) {
                abort(422, 'Fuer dieses Item gibt es bereits ein offenes Verkaufsangebot.');
            }

            $lastRejected = PartyNpcTradeSellOffer::query()
                ->where('party_npc_trade_offer_id', $npcTradeOffer->id)
                ->where('party_character_id', $character->id)
                ->where('inventory_item_id', (int) $inventoryItem->id)
                ->where('status', PartyNpcTradeSellOffer::STATUS_REJECTED)
                ->latest('id')
                ->first();

            $amountCopper = (int) $data['amount_copper'];
            if ($lastRejected && $amountCopper >= (int) $lastRejected->amount_copper) {
                throw ValidationException::withMessages([
                    'amount_copper' => ['Nach Ablehnung muss der neue Preis niedriger als der letzte Preis sein.'],
                ]);
            }

            PartyNpcTradeSellOffer::create([
                'party_npc_trade_offer_id' => $npcTradeOffer->id,
                'party_character_id' => $character->id,
                'inventory_item_id' => (int) $inventoryItem->id,
                'created_by_user_id' => $user->id,
                'quantity' => $requestedQuantity,
                'amount_copper' => $amountCopper,
                'status' => PartyNpcTradeSellOffer::STATUS_PENDING,
                'item_snapshot' => [
                    'name' => $inventoryItem->name,
                    'category' => $inventoryItem->category,
                    'notes' => $inventoryItem->notes,
                ],
            ]);

            return $npcTradeOffer->fresh($this->offerRelations());
        });

        $payload = $this->toPayload($offer);
        $this->broadcastUpdate($party->id, $payload);

        return response()->json([
            'ok' => true,
            'offer' => $payload,
        ], 201);
    }
    public function resolveSellOffer(Request $request, Party $party, PartyNpcTradeSellOffer $sellOffer): JsonResponse
    {
        $user = $request->user();
        $this->assertOwner($party, (int) $user->id);

        $data = $request->validate([
            'action' => ['required', Rule::in(['accept', 'reject'])],
        ]);

        $result = DB::transaction(function () use ($party, $sellOffer, $data, $user): array {
            $npcTradeOffer = PartyNpcTradeOffer::query()
                ->where('party_id', $party->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSellOffer = PartyNpcTradeSellOffer::query()
                ->where('party_npc_trade_offer_id', $npcTradeOffer->id)
                ->lockForUpdate()
                ->findOrFail($sellOffer->id);

            if ($lockedSellOffer->status !== PartyNpcTradeSellOffer::STATUS_PENDING) {
                abort(422, 'Dieses Angebot wurde bereits bearbeitet.');
            }

            if ($data['action'] === 'reject') {
                $lockedSellOffer->update([
                    'status' => PartyNpcTradeSellOffer::STATUS_REJECTED,
                    'resolved_by_user_id' => $user->id,
                    'resolved_at' => now(),
                ]);

                return [
                    $npcTradeOffer->fresh($this->offerRelations()),
                    null,
                    null,
                    null,
                    null,
                ];
            }

            abort_unless((int) $npcTradeOffer->active_party_character_id === (int) $lockedSellOffer->party_character_id, 422, 'Kein aktiver Handel mit diesem Spieler.');

            $character = PartyCharacter::query()->lockForUpdate()->findOrFail($lockedSellOffer->party_character_id);
            $inventoryItem = InventoryItem::query()
                ->where('party_character_id', $character->id)
                ->lockForUpdate()
                ->findOrFail($lockedSellOffer->inventory_item_id);

            if ((int) $inventoryItem->quantity < (int) $lockedSellOffer->quantity) {
                abort(422, 'Der Spieler besitzt nicht mehr genug Menge fuer dieses Angebot.');
            }

            $newQuantity = (int) $inventoryItem->quantity - (int) $lockedSellOffer->quantity;
            if ($newQuantity <= 0) {
                $removedItemId = (int) $inventoryItem->id;
                $inventoryItem->delete();
                $inventoryUpdatePayload = [
                    'action' => 'remove',
                    'itemId' => $removedItemId,
                ];
            } else {
                $inventoryItem->quantity = $newQuantity;
                $inventoryItem->save();
                $inventoryUpdatePayload = [
                    'action' => 'upsert',
                    'item' => $inventoryItem->fresh(),
                ];
            }

            $items = $this->normalizeItemsForStorage($npcTradeOffer->inventory_items ?? []);
            $snapshot = $lockedSellOffer->item_snapshot ?? [];
            $name = (string) ($snapshot['name'] ?? 'Unbekanntes Item');
            $category = $snapshot['category'] ?? null;
            $notes = $snapshot['notes'] ?? null;
            $quantity = (int) $lockedSellOffer->quantity;
            $amountCopper = (int) $lockedSellOffer->amount_copper;
            $unitPriceCopper = max(1, (int) ceil($amountCopper / max(1, $quantity)));

            $existingNpcIndex = collect($items)->search(fn (array $item) =>
                mb_strtolower((string) ($item['name'] ?? '')) === mb_strtolower($name)
                && (string) ($item['category'] ?? '') === (string) ($category ?? '')
                && (int) ($item['price_copper'] ?? 0) === $unitPriceCopper
            );

            if ($existingNpcIndex === false) {
                $nextNpcId = (int) collect($items)->max(fn ($item) => (int) ($item['id'] ?? 0)) + 1;
                $items[] = [
                    'id' => $nextNpcId,
                    'name' => $name,
                    'quantity' => $quantity,
                    'price_copper' => $unitPriceCopper,
                    'category' => $category,
                    'notes' => $notes,
                ];
            } else {
                $items[$existingNpcIndex]['quantity'] = min(999, (int) ($items[$existingNpcIndex]['quantity'] ?? 0) + $quantity);
            }

            $npcTradeOffer->update([
                'inventory_items' => array_values($items),
            ]);

            $wallet = $this->resolveWalletForCharacter($character->id, true);
            $wallet->update([
                'copper_balance' => (int) $wallet->copper_balance + $amountCopper,
            ]);

            $walletTransaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'actor_user_id' => $user->id,
                'type' => WalletTransaction::TYPE_IN,
                'amount_copper' => $amountCopper,
                'note' => sprintf('NPC-Ankauf durch %s: %s x%d', $npcTradeOffer->name, $name, $quantity),
            ]);
            $walletTransaction->load('actor:id,name');

            $lockedSellOffer->update([
                'status' => PartyNpcTradeSellOffer::STATUS_ACCEPTED,
                'resolved_by_user_id' => $user->id,
                'resolved_at' => now(),
            ]);

            return [
                $npcTradeOffer->fresh($this->offerRelations()),
                $character,
                $wallet->fresh(),
                $walletTransaction,
                $inventoryUpdatePayload,
            ];
        });

        [$offer, $character, $wallet, $walletTransaction, $inventoryUpdatePayload] = $result;

        $payload = $this->toPayload($offer);
        $this->broadcastUpdate($party->id, $payload);

        if ($character && $inventoryUpdatePayload) {
            if ($inventoryUpdatePayload['action'] === 'remove') {
                $this->broadcastInventoryItemRemove($party->id, (int) $character->id, (int) $inventoryUpdatePayload['itemId']);
            }
            if ($inventoryUpdatePayload['action'] === 'upsert' && $inventoryUpdatePayload['item']) {
                $this->broadcastInventoryItemUpsert($party->id, $inventoryUpdatePayload['item'], false);
            }
        }

        if ($character && $wallet && $walletTransaction) {
            $this->broadcastWalletUpdate(
                $party->id,
                (int) $character->id,
                $this->toWalletPayload($wallet),
                $this->toWalletTransactionPayload($walletTransaction)
            );
        }

        return response()->json([
            'ok' => true,
            'offer' => $payload,
        ]);
    }

    private function toPayload(?PartyNpcTradeOffer $offer): ?array
    {
        if (! $offer) {
            return null;
        }

        $offer->loadMissing($this->offerRelations());

        return [
            'id' => (int) $offer->id,
            'partyId' => (int) $offer->party_id,
            'name' => $offer->name,
            'isOpen' => (bool) $offer->is_open,
            'items' => collect($this->normalizeItemsForStorage($offer->inventory_items ?? []))->map(fn ($item) => [
                'id' => (int) ($item['id'] ?? 0),
                'name' => (string) ($item['name'] ?? ''),
                'quantity' => (int) ($item['quantity'] ?? 1),
                'priceCopper' => (int) ($item['price_copper'] ?? 0),
                'priceDisplay' => $this->formatCopper((int) ($item['price_copper'] ?? 0)),
                'category' => $item['category'] ?? null,
                'notes' => $item['notes'] ?? null,
            ])->values()->all(),
            'sellOffers' => $offer->sellOffers
                ->map(fn (PartyNpcTradeSellOffer $entry) => [
                    'id' => (int) $entry->id,
                    'partyCharacterId' => (int) $entry->party_character_id,
                    'partyCharacterName' => $entry->character?->user?->name ?? $entry->character?->name,
                    'inventoryItemId' => (int) $entry->inventory_item_id,
                    'itemName' => $entry->item_snapshot['name'] ?? null,
                    'itemCategory' => $entry->item_snapshot['category'] ?? null,
                    'quantity' => (int) $entry->quantity,
                    'amountCopper' => (int) $entry->amount_copper,
                    'amountDisplay' => $this->formatCopper((int) $entry->amount_copper),
                    'status' => $entry->status,
                    'createdByUserId' => $entry->created_by_user_id ? (int) $entry->created_by_user_id : null,
                    'createdByUserName' => $entry->createdByUser?->name,
                    'resolvedByUserId' => $entry->resolved_by_user_id ? (int) $entry->resolved_by_user_id : null,
                    'resolvedByUserName' => $entry->resolvedByUser?->name,
                    'resolvedAt' => optional($entry->resolved_at)?->toIso8601String(),
                    'createdAt' => optional($entry->created_at)?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'activePartyCharacterId' => $offer->active_party_character_id ? (int) $offer->active_party_character_id : null,
            'activeCharacterUserId' => $offer->activeCharacter?->user_id ? (int) $offer->activeCharacter->user_id : null,
            'activeCharacterName' => $offer->activeCharacter?->user?->name ?? $offer->activeCharacter?->name,
            'openedAt' => optional($offer->opened_at)?->toIso8601String(),
            'closedAt' => optional($offer->closed_at)?->toIso8601String(),
        ];
    }
    private function offerRelations(): array
    {
        return [
            'activeCharacter.user:id,name',
            'sellOffers' => fn ($query) => $query
                ->with(['character.user:id,name', 'createdByUser:id,name', 'resolvedByUser:id,name'])
                ->limit(100),
        ];
    }

    private function assertOwner(Party $party, int $userId): void
    {
        abort_unless((int) $party->owner_id === $userId, 403);
    }

    private function broadcastUpdate(int $partyId, ?array $payload): void
    {
        if (! config('realtime.enabled')) {
            return;
        }

        try {
            event(new PartyNpcTradeUpdated($partyId, $payload ?? []));
        } catch (\Throwable $exception) {
            // Keep NPC trade flow functional even if realtime fails.
        }
    }

    private function broadcastInventoryItemUpsert(int $partyId, InventoryItem $item, bool $notify): void
    {
        if (! config('realtime.enabled')) {
            return;
        }

        try {
            event(new PartyInventoryItemUpdated($partyId, [
                'action' => 'upsert',
                'partyCharacterId' => (int) $item->party_character_id,
                'item' => $this->toInventoryItemPayload($item),
                'notify' => $notify,
            ]));
        } catch (\Throwable $exception) {
            // Keep NPC trade flow functional even if realtime fails.
        }
    }

    private function broadcastInventoryItemRemove(int $partyId, int $partyCharacterId, int $itemId): void
    {
        if (! config('realtime.enabled')) {
            return;
        }

        try {
            event(new PartyInventoryItemUpdated($partyId, [
                'action' => 'remove',
                'partyCharacterId' => $partyCharacterId,
                'itemId' => $itemId,
            ]));
        } catch (\Throwable $exception) {
            // Keep NPC trade flow functional even if realtime fails.
        }
    }

    private function broadcastWalletUpdate(int $partyId, int $partyCharacterId, array $wallet, array $transaction): void
    {
        if (! config('realtime.enabled')) {
            return;
        }

        try {
            event(new PartyWalletUpdated($partyId, [
                'partyCharacterId' => $partyCharacterId,
                'wallet' => $wallet,
                'transaction' => $transaction,
            ]));
        } catch (\Throwable $exception) {
            // Keep NPC trade flow functional even if realtime fails.
        }
    }

    private function normalizeItemsForStorage(array $items): array
    {
        return collect($items)->values()->map(function ($item, int $index) {
            return [
                'id' => (int) ($item['id'] ?? ($index + 1)),
                'name' => (string) ($item['name'] ?? ''),
                'quantity' => max(0, (int) ($item['quantity'] ?? 0)),
                'price_copper' => max(0, (int) ($item['price_copper'] ?? 0)),
                'category' => ! empty($item['category']) ? (string) $item['category'] : null,
                'notes' => ! empty($item['notes']) ? (string) $item['notes'] : null,
            ];
        })->filter(fn (array $item) => $item['name'] !== '' && $item['quantity'] > 0 && $item['price_copper'] > 0)
            ->values()
            ->all();
    }

    private function addItemToPlayerInventory(PartyCharacter $character, array $sourceItem, int $quantity): InventoryItem
    {
        $name = (string) ($sourceItem['name'] ?? '');
        $category = $sourceItem['category'] ?? null;
        $notes = $sourceItem['notes'] ?? null;

        $query = InventoryItem::query()
            ->where('party_character_id', $character->id)
            ->where('name', $name);

        if ($category === null) {
            $query->whereNull('category');
        } else {
            $query->where('category', $category);
        }

        $existing = $query->first();
        if ($existing) {
            $existing->quantity = min(999, (int) $existing->quantity + $quantity);
            if (! $existing->notes && $notes) {
                $existing->notes = $notes;
            }
            $existing->save();

            return $existing;
        }

        return InventoryItem::create([
            'party_character_id' => $character->id,
            'name' => $name,
            'quantity' => min(999, $quantity),
            'category' => $category,
            'notes' => $notes,
            'sort_order' => ((int) ($character->inventoryItems()->max('sort_order') ?? 0)) + 1,
        ]);
    }

    private function resolveWalletForCharacter(int $partyCharacterId, bool $lockForUpdate): CharacterWallet
    {
        $query = CharacterWallet::query()->where('party_character_id', $partyCharacterId);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $wallet = $query->first();
        if ($wallet) {
            return $wallet;
        }

        return CharacterWallet::query()->create([
            'party_character_id' => $partyCharacterId,
            'copper_balance' => 0,
        ]);
    }

    private function toInventoryItemPayload(InventoryItem $item): array
    {
        return [
            'id' => (int) $item->id,
            'partyCharacterId' => (int) $item->party_character_id,
            'name' => $item->name,
            'quantity' => (int) $item->quantity,
            'category' => $item->category,
            'notes' => $item->notes,
            'sortOrder' => (int) $item->sort_order,
        ];
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

    private function toWalletTransactionPayload(WalletTransaction $transaction): array
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

    private function splitCopper(int $copperAmount): array
    {
        return [
            'gold' => intdiv($copperAmount, 100),
            'silver' => intdiv($copperAmount % 100, 10),
            'copper' => $copperAmount % 10,
        ];
    }

    private function formatCopper(int $copperAmount): string
    {
        $coins = $this->splitCopper($copperAmount);

        return sprintf('%dG %dS %dK', $coins['gold'], $coins['silver'], $coins['copper']);
    }
}
