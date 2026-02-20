<?php

namespace App\Http\Controllers;

use App\Events\PartyInventoryItemUpdated;
use App\Models\InventoryItem;
use App\Models\Party;
use App\Models\PartyCharacter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartyInventoryItemController extends Controller
{
    public function store(Request $request, Party $party): JsonResponse
    {
        $user = $request->user();
        abort_unless($party->members()->whereKey($user->id)->exists(), 403);
        abort_unless((int) $party->owner_id === (int) $user->id, 403);

        $data = $request->validate([
            'party_character_id' => ['required', 'integer', Rule::exists('party_characters', 'id')->where('party_id', $party->id)],
            'name' => ['required', 'string', 'max:120'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'category' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $character = PartyCharacter::query()->where('party_id', $party->id)->findOrFail($data['party_character_id']);
        $this->assertCanManageInventory($party, $user->id, $character->user_id);

        $requestedQuantity = (int) ($data['quantity'] ?? 1);
        $existingItemQuery = InventoryItem::query()
            ->where('party_character_id', $character->id)
            ->where('name', $data['name']);
        if (!empty($data['category'])) {
            $existingItemQuery->where('category', $data['category']);
        } else {
            $existingItemQuery->whereNull('category');
        }

        $existingItem = $existingItemQuery->first();

        if ($existingItem) {
            $existingItem->quantity = min(999, (int) $existingItem->quantity + $requestedQuantity);
            if (!empty($data['notes']) && empty($existingItem->notes)) {
                $existingItem->notes = $data['notes'];
            }
            $existingItem->save();
            $item = $existingItem;
        } else {
            $item = InventoryItem::create([
                'party_character_id' => $character->id,
                'name' => $data['name'],
                'quantity' => $requestedQuantity,
                'category' => $data['category'] ?? null,
                'notes' => $data['notes'] ?? null,
                'sort_order' => ((int) ($character->inventoryItems()->max('sort_order') ?? 0)) + 1,
            ]);
        }

        $this->broadcastUpdate($party->id, [
            'action' => 'upsert',
            'partyCharacterId' => $character->id,
            'item' => $this->toPayload($item),
        ]);

        return response()->json([
            'ok' => true,
            'item' => $this->toPayload($item),
        ], 201);
    }

    public function update(Request $request, Party $party, InventoryItem $inventoryItem): JsonResponse
    {
        $user = $request->user();
        abort_unless($party->members()->whereKey($user->id)->exists(), 403);

        $character = PartyCharacter::query()
            ->where('party_id', $party->id)
            ->whereKey($inventoryItem->party_character_id)
            ->firstOrFail();
        $this->assertCanManageInventory($party, $user->id, $character->user_id);

        $isOwner = (int) $party->owner_id === (int) $user->id;
        $rules = $isOwner
            ? [
                'name' => ['sometimes', 'string', 'max:120'],
                'quantity' => ['sometimes', 'integer', 'min:1', 'max:999'],
                'category' => ['nullable', 'string', 'max:80'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ]
            : [
                'notes' => ['nullable', 'string', 'max:1000'],
            ];

        $data = $request->validate($rules);

        $inventoryItem->fill($data);
        $inventoryItem->save();

        $this->broadcastUpdate($party->id, [
            'action' => 'upsert',
            'partyCharacterId' => $character->id,
            'item' => $this->toPayload($inventoryItem),
        ]);

        return response()->json([
            'ok' => true,
            'item' => $this->toPayload($inventoryItem),
        ]);
    }

    public function destroy(Request $request, Party $party, InventoryItem $inventoryItem): JsonResponse
    {
        $user = $request->user();
        abort_unless($party->members()->whereKey($user->id)->exists(), 403);
        abort_unless((int) $party->owner_id === (int) $user->id, 403);

        $character = PartyCharacter::query()
            ->where('party_id', $party->id)
            ->whereKey($inventoryItem->party_character_id)
            ->firstOrFail();
        $this->assertCanManageInventory($party, $user->id, $character->user_id);

        $this->broadcastUpdate($party->id, [
            'action' => 'remove',
            'partyCharacterId' => $character->id,
            'itemId' => $inventoryItem->id,
        ]);

        $inventoryItem->delete();

        return response()->json(['ok' => true]);
    }

    public function use(Request $request, Party $party, InventoryItem $inventoryItem): JsonResponse
    {
        $user = $request->user();
        abort_unless($party->members()->whereKey($user->id)->exists(), 403);

        $character = PartyCharacter::query()
            ->where('party_id', $party->id)
            ->whereKey($inventoryItem->party_character_id)
            ->firstOrFail();
        $this->assertCanManageInventory($party, $user->id, $character->user_id);

        if ((int) $inventoryItem->quantity > 1) {
            $inventoryItem->quantity = (int) $inventoryItem->quantity - 1;
            $inventoryItem->save();

            $this->broadcastUpdate($party->id, [
                'action' => 'upsert',
                'partyCharacterId' => $character->id,
                'item' => $this->toPayload($inventoryItem),
            ]);

            return response()->json([
                'ok' => true,
                'removed' => false,
                'item' => $this->toPayload($inventoryItem),
            ]);
        }

        $deletedId = $inventoryItem->id;

        $this->broadcastUpdate($party->id, [
            'action' => 'remove',
            'partyCharacterId' => $character->id,
            'itemId' => $deletedId,
        ]);

        $inventoryItem->delete();

        return response()->json([
            'ok' => true,
            'removed' => true,
            'itemId' => $deletedId,
        ]);
    }

    private function assertCanManageInventory(Party $party, int $actorUserId, int $characterUserId): void
    {
        $isOwner = (int) $party->owner_id === (int) $actorUserId;
        $isOwnCharacter = (int) $actorUserId === (int) $characterUserId;
        abort_unless($isOwner || $isOwnCharacter, 403);
    }

    private function toPayload(InventoryItem $item): array
    {
        return [
            'id' => $item->id,
            'partyCharacterId' => $item->party_character_id,
            'name' => $item->name,
            'quantity' => (int) $item->quantity,
            'category' => $item->category,
            'notes' => $item->notes,
            'sortOrder' => (int) $item->sort_order,
        ];
    }

    private function broadcastUpdate(int $partyId, array $payload): void
    {
        if (!config('realtime.enabled')) {
            return;
        }

        try {
            event(new PartyInventoryItemUpdated($partyId, $payload));
        } catch (\Throwable $exception) {
            // Keep inventory flow functional even if realtime fails.
        }
    }
}
