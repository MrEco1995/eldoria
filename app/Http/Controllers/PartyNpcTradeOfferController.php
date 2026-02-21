<?php

namespace App\Http\Controllers;

use App\Events\PartyNpcTradeUpdated;
use App\Models\InventoryItem;
use App\Models\Party;
use App\Models\PartyCharacter;
use App\Models\PartyNpcTradeOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $offer->load('activeCharacter.user:id,name');
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

        if (empty($offer->name) || empty($offer->inventory_items)) {
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

        $offer->load('activeCharacter.user:id,name');
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

        $offer->load('activeCharacter.user:id,name');
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

        $offer->load('activeCharacter.user:id,name');
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

        $offer->load('activeCharacter.user:id,name');
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

        $result = DB::transaction(function () use ($party, $character, $data): array {
            $offer = PartyNpcTradeOffer::query()
                ->where('party_id', $party->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($offer->is_open, 422, 'NPC ist nicht zum Handeln freigegeben.');
            abort_unless((int) $offer->active_party_character_id === (int) $character->id, 403, 'Du handelst gerade nicht mit diesem NPC.');

            $items = $this->normalizeItemsForStorage($offer->inventory_items ?? []);
            $itemIndex = collect($items)->search(fn (array $item) => (int) $item['id'] === (int) $data['item_id']);
            if ($itemIndex === false) {
                abort(422, 'Dieses NPC-Item ist nicht mehr verfügbar.');
            }

            $buyQuantity = (int) $data['quantity'];
            $currentQuantity = (int) ($items[$itemIndex]['quantity'] ?? 0);
            if ($buyQuantity > $currentQuantity) {
                abort(422, 'Nicht genug Menge beim NPC verfügbar.');
            }

            $items[$itemIndex]['quantity'] = $currentQuantity - $buyQuantity;
            $purchasedItem = $items[$itemIndex];
            if ((int) $items[$itemIndex]['quantity'] <= 0) {
                unset($items[$itemIndex]);
            }

            $offer->update([
                'inventory_items' => array_values($items),
            ]);

            $playerInventoryItem = $this->addItemToPlayerInventory($character, $purchasedItem, $buyQuantity);

            return [$offer->fresh(['activeCharacter.user:id,name']), $playerInventoryItem];
        });

        [$offer, $playerInventoryItem] = $result;

        $payload = $this->toPayload($offer);
        $this->broadcastUpdate($party->id, $payload);

        return response()->json([
            'ok' => true,
            'offer' => $payload,
            'inventoryItem' => [
                'id' => $playerInventoryItem->id,
                'partyCharacterId' => $playerInventoryItem->party_character_id,
                'name' => $playerInventoryItem->name,
                'quantity' => (int) $playerInventoryItem->quantity,
                'category' => $playerInventoryItem->category,
                'notes' => $playerInventoryItem->notes,
                'sortOrder' => (int) $playerInventoryItem->sort_order,
            ],
        ]);
    }

    private function toPayload(?PartyNpcTradeOffer $offer): ?array
    {
        if (! $offer) {
            return null;
        }

        return [
            'id' => (int) $offer->id,
            'partyId' => (int) $offer->party_id,
            'name' => $offer->name,
            'isOpen' => (bool) $offer->is_open,
            'items' => collect($this->normalizeItemsForStorage($offer->inventory_items ?? []))->map(fn ($item) => [
                'id' => (int) ($item['id'] ?? 0),
                'name' => (string) ($item['name'] ?? ''),
                'quantity' => (int) ($item['quantity'] ?? 1),
                'category' => $item['category'] ?? null,
                'notes' => $item['notes'] ?? null,
            ])->values()->all(),
            'activePartyCharacterId' => $offer->active_party_character_id ? (int) $offer->active_party_character_id : null,
            'activeCharacterUserId' => $offer->activeCharacter?->user_id ? (int) $offer->activeCharacter->user_id : null,
            'activeCharacterName' => $offer->activeCharacter?->user?->name ?? $offer->activeCharacter?->name,
            'openedAt' => optional($offer->opened_at)?->toIso8601String(),
            'closedAt' => optional($offer->closed_at)?->toIso8601String(),
        ];
    }

    private function assertOwner(Party $party, int $userId): void
    {
        abort_unless((int) $party->owner_id === $userId, 403);
    }

    private function broadcastUpdate(int $partyId, ?array $payload): void
    {
        if (!config('realtime.enabled')) {
            return;
        }

        try {
            event(new PartyNpcTradeUpdated($partyId, $payload ?? []));
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
                'category' => !empty($item['category']) ? (string) $item['category'] : null,
                'notes' => !empty($item['notes']) ? (string) $item['notes'] : null,
            ];
        })->filter(fn (array $item) => $item['name'] !== '' && $item['quantity'] > 0)
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
}
