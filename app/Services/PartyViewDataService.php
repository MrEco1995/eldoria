<?php

namespace App\Services;

use App\Models\Party;
use App\Models\PartyInvite;
use App\Models\PartyNpcTradeOffer;
use App\Models\PartyNpcTradeSellOffer;
use App\Models\PartyTradeSession;
use App\Models\PartyTalentRequest;
use App\Models\CharacterClass;
use App\Models\PointOfInterest;
use App\Models\Race;
use App\Models\Talent;
use Illuminate\Support\Collection;

class PartyViewDataService
{
    public function buildShowPayload(Party $party, int $userId): array
    {
        $characters = $this->loadCharacterPayloads($party);
        $ownCharacter = $characters->firstWhere('user_id', $userId);
        $invite = $this->loadActiveInvite($party->id);

        return [
            'party' => $this->mapParty($party),
            'members' => $this->loadMembers($party),
            'character' => $ownCharacter ? $this->mapShowCharacter($ownCharacter) : null,
            'characters' => $characters->values(),
            'invite' => $invite ? [
                'url' => route('parties.invites.join', $invite->token),
                'expiresAt' => $invite->expires_at->toIso8601String(),
            ] : null,
            'races' => $this->loadRaces(),
            'classes' => $this->loadClasses(),
            'talents' => $this->loadTalents(),
            'mapLocations' => $this->loadMapLocations(),
            'talentPointPool' => config('game.character_talent_point_pool', 140),
            'isOwner' => (int) $party->owner_id === $userId,
        ];
    }

    public function buildStartedPayload(Party $party, int $userId): array
    {
        return [
            'party' => [
                'id' => $party->id,
                'name' => $party->name,
                'startedAt' => $party->started_at?->toIso8601String(),
            ],
            'members' => $this->loadMembers($party),
            'characters' => $this->loadCharacterPayloads($party)->values(),
            'talentDefinitions' => $this->loadTalentDefinitions(),
            'talentRequests' => $this->loadTalentRequests($party, $userId),
            'tradeSessions' => $this->loadTradeSessions($party, $userId),
            'npcTradeOffers' => $this->loadNpcTradeOffers($party),
            'mapLocations' => $this->loadMapLocations(),
        ];
    }

    private function loadMapLocations(): Collection
    {
        try {
            $locations = PointOfInterest::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get([
                    'slug',
                    'name',
                    'type',
                    'x_percent',
                    'y_percent',
                    'min_zoom',
                    'description',
                ])
                ->map(fn ($location) => [
                    'id' => $location->slug,
                    'name' => $location->name,
                    'type' => $location->type,
                    'x' => (float) $location->x_percent,
                    'y' => (float) $location->y_percent,
                    'minZoom' => (float) $location->min_zoom,
                    'description' => $location->description,
                ])
                ->values();

            if ($locations->isNotEmpty()) {
                return $locations;
            }
        } catch (\Throwable $exception) {
            // Fallback to defaults if migration/seed is missing.
        }

        return collect($this->defaultMapLocations());
    }

    private function defaultMapLocations(): array
    {
        return [
            [
                'id' => 'capital-eldoria',
                'name' => 'Hauptstadt Eldoria',
                'type' => 'landmark',
                'x' => 49.0,
                'y' => 44.0,
                'minZoom' => 1.0,
                'description' => 'Politisches Zentrum des Reiches und haeufigster Treffpunkt fuer neue Auftraege.',
            ],
            [
                'id' => 'northwatch',
                'name' => 'Nordwacht',
                'type' => 'landmark',
                'x' => 58.0,
                'y' => 20.0,
                'minZoom' => 1.0,
                'description' => 'Festung an der noerdlichen Grenze. Hohe Praesenz von Wachen und Patrouillen.',
            ],
            [
                'id' => 'silverwald',
                'name' => 'Silberwald',
                'type' => 'landmark',
                'x' => 36.0,
                'y' => 51.0,
                'minZoom' => 1.0,
                'description' => 'Dichter Wald mit alten Ruinen und seltenen Ressourcen.',
            ],
            [
                'id' => 'ashen-coast',
                'name' => 'Aschenkueste',
                'type' => 'landmark',
                'x' => 23.0,
                'y' => 69.0,
                'minZoom' => 1.0,
                'description' => 'Gefaehrliche Kuestenregion, bekannt fuer Piraten und verlorene Schaetze.',
            ],
            [
                'id' => 'falkengrund',
                'name' => 'Falkengrund',
                'type' => 'village',
                'x' => 53.0,
                'y' => 48.0,
                'minZoom' => 1.7,
                'description' => 'Kleines Dorf suedoestlich der Hauptstadt, bekannt fuer Pferde und Kurierdienste.',
            ],
            [
                'id' => 'moorwinkel',
                'name' => 'Moorwinkel',
                'type' => 'village',
                'x' => 41.0,
                'y' => 58.0,
                'minZoom' => 1.8,
                'description' => 'Abgelegenes Siedlungsgebiet am Rand eines Nebelmoors.',
            ],
            [
                'id' => 'sonnbruch',
                'name' => 'Sonnbruch',
                'type' => 'village',
                'x' => 29.0,
                'y' => 63.0,
                'minZoom' => 2.0,
                'description' => 'Fischerdorf mit kleinem Hafen und reger Kuestenfahrt.',
            ],
            [
                'id' => 'steinkamm',
                'name' => 'Steinkamm',
                'type' => 'village',
                'x' => 61.0,
                'y' => 31.0,
                'minZoom' => 2.1,
                'description' => 'Bergweiler mit Erzschaechten und rauem Klima.',
            ],
        ];
    }

    private function loadActiveInvite(int $partyId): ?PartyInvite
    {
        return PartyInvite::query()
            ->where('party_id', $partyId)
            ->where('expires_at', '>', now())
            ->latest('expires_at')
            ->first();
    }

    private function loadCharacterPayloads(Party $party): Collection
    {
        return $party->characters()
            ->with('user:id,name')
            ->with('inventoryItems:id,party_character_id,name,quantity,category,notes,sort_order')
            ->with([
                'wallet' => fn ($query) => $query
                    ->select('id', 'party_character_id', 'copper_balance')
                    ->with([
                        'transactions' => fn ($transactionsQuery) => $transactionsQuery
                            ->with('actor:id,name')
                            ->orderByDesc('id'),
                    ]),
            ])
            ->with([
                'mediafiles' => fn ($query) => $query
                    ->wherePivot('role', 'character')
                    ->latest('mediafiles.id'),
            ])
            ->select('id', 'party_id', 'user_id', 'name', 'race', 'class_name', 'gender', 'age', 'height_cm', 'weight_kg', 'traits', 'talents')
            ->get()
            ->map(function ($entry) {
                $image = $entry->mediafiles->first();

                return [
                    'id' => $entry->id,
                    'party_id' => $entry->party_id,
                    'user_id' => $entry->user_id,
                    'name' => $entry->name,
                    'race' => $entry->race,
                    'class_name' => $entry->class_name,
                    'gender' => $entry->gender,
                    'age' => $entry->age,
                    'height_cm' => $entry->height_cm,
                    'weight_kg' => $entry->weight_kg,
                    'traits' => $entry->traits ?? [],
                    'talents' => $entry->talents ?? [],
                    'user' => [
                        'id' => $entry->user->id,
                        'name' => $entry->user->name,
                    ],
                    'inventoryItems' => $entry->inventoryItems->map(fn ($item) => [
                        'id' => $item->id,
                        'partyCharacterId' => $item->party_character_id,
                        'name' => $item->name,
                        'quantity' => (int) $item->quantity,
                        'category' => $item->category,
                        'notes' => $item->notes,
                        'sortOrder' => (int) $item->sort_order,
                    ])->values()->all(),
                    'wallet' => $this->mapWallet($entry->wallet),
                    'image_url' => $image ? route('media.public', ['path' => $image->path]) : null,
                ];
            });
    }

    private function mapShowCharacter(array $character): array
    {
        return [
            'id' => $character['id'],
            'name' => $character['name'],
            'race' => $character['race'],
            'class_name' => $character['class_name'],
            'gender' => $character['gender'],
            'age' => $character['age'],
            'height_cm' => $character['height_cm'],
            'weight_kg' => $character['weight_kg'],
            'traits' => $character['traits'],
            'talents' => $character['talents'],
            'image_url' => $character['image_url'],
        ];
    }

    private function loadMembers(Party $party): Collection
    {
        return $party->members()
            ->select('users.id', 'users.name', 'users.email', 'party_user.is_ready')
            ->get();
    }

    private function mapParty(Party $party): array
    {
        return [
            'id' => $party->id,
            'name' => $party->name,
            'owner' => [
                'id' => $party->owner->id,
                'name' => $party->owner->name,
                'email' => $party->owner->email,
            ],
            'startedAt' => $party->started_at?->toIso8601String(),
        ];
    }

    private function loadRaces(): Collection
    {
        return Race::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'name',
                'description',
                'essence',
                'appearance',
                'age_text',
                'height_text',
                'weight_text',
                'good_with',
                'bad_with',
            ])->map(fn ($race) => [
                'name' => $race->name,
                'description' => $race->description,
                'essence' => $race->essence,
                'appearance' => $race->appearance,
                'age' => $race->age_text,
                'height' => $race->height_text,
                'weight' => $race->weight_text,
                'goodWith' => $race->good_with ?? [],
                'badWith' => $race->bad_with ?? [],
            ])->values();
    }

    private function loadClasses(): Collection
    {
        return CharacterClass::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'description'])
            ->map(fn ($entry) => [
                'name' => $entry->name,
                'description' => $entry->description,
            ])
            ->values();
    }

    private function loadTalents(): Collection
    {
        return Talent::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get([
                'key',
                'label',
                'category',
                'description',
                'max_points',
            ])->map(fn ($talent) => [
                'key' => $talent->key,
                'label' => $talent->label,
                'category' => $talent->category,
                'description' => $talent->description,
                'maxPoints' => $talent->max_points,
            ])->values();
    }

    private function loadTalentDefinitions(): Collection
    {
        return Talent::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['key', 'label', 'category'])
            ->map(fn ($talent) => [
                'key' => $talent->key,
                'label' => $talent->label,
                'category' => $talent->category,
            ])
            ->values();
    }

    private function loadTalentRequests(Party $party, int $userId): Collection
    {
        return PartyTalentRequest::query()
            ->where('party_id', $party->id)
            ->when((int) $party->owner_id !== $userId, fn ($query) => $query->where('target_user_id', $userId))
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(function ($request) use ($party) {
                $owner = $party->members()->whereKey($request->owner_user_id)->first();
                $target = $party->members()->whereKey($request->target_user_id)->first();
                return [
                    'id' => $request->id,
                    'partyId' => $request->party_id,
                    'ownerUserId' => $request->owner_user_id,
                    'ownerUserName' => $owner?->name ?? 'Spielleiter',
                    'targetUserId' => $request->target_user_id,
                    'targetUserName' => $target?->name ?? 'Spieler',
                    'talents' => collect($request->talents ?? [])->map(fn ($talent) => [
                        'key' => $talent['key'] ?? '',
                        'label' => $talent['label'] ?? ($talent['key'] ?? ''),
                        'rolledValue' => $talent['rolledValue'] ?? null,
                        'targetValue' => $talent['targetValue'] ?? null,
                        'isSuccess' => $talent['isSuccess'] ?? null,
                        'rolledAt' => $talent['rolledAt'] ?? null,
                    ])->values()->all(),
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
            })
            ->values();
    }

    private function loadTradeSessions(Party $party, int $userId): Collection
    {
        return PartyTradeSession::query()
            ->where('party_id', $party->id)
            ->whereIn('status', [PartyTradeSession::STATUS_PENDING, PartyTradeSession::STATUS_ACTIVE])
            ->where(function ($query) use ($userId) {
                $query->whereHas('initiatorCharacter', fn ($characterQuery) => $characterQuery->where('user_id', $userId))
                    ->orWhereHas('counterpartyCharacter', fn ($characterQuery) => $characterQuery->where('user_id', $userId));
            })
            ->with([
                'initiatorCharacter.user:id,name',
                'counterpartyCharacter.user:id,name',
            ])
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn ($session) => [
                'id' => (int) $session->id,
                'partyId' => (int) $session->party_id,
                'initiatorPartyCharacterId' => (int) $session->initiator_party_character_id,
                'counterpartyPartyCharacterId' => (int) $session->counterparty_party_character_id,
                'initiatorUserId' => (int) $session->initiatorCharacter->user_id,
                'counterpartyUserId' => (int) $session->counterpartyCharacter->user_id,
                'initiatorName' => $session->initiatorCharacter->user?->name ?? $session->initiatorCharacter->name,
                'counterpartyName' => $session->counterpartyCharacter->user?->name ?? $session->counterpartyCharacter->name,
                'status' => $session->status,
                'createdAt' => optional($session->created_at)?->toIso8601String(),
                'acceptedAt' => optional($session->accepted_at)?->toIso8601String(),
            ])
            ->values();
    }

    private function loadNpcTradeOffers(Party $party): array
    {
        return PartyNpcTradeOffer::query()
            ->where('party_id', $party->id)
            ->with([
                'activeCharacter.user:id,name',
                'sellOffers' => fn ($query) => $query
                    ->with(['character.user:id,name', 'createdByUser:id,name', 'resolvedByUser:id,name'])
                    ->whereIn('status', [
                        PartyNpcTradeSellOffer::STATUS_PENDING,
                        PartyNpcTradeSellOffer::STATUS_ACCEPTED,
                        PartyNpcTradeSellOffer::STATUS_REJECTED,
                    ])
                    ->limit(100),
            ])
            ->orderByDesc('id')
            ->get()
            ->map(fn ($offer) => [
            'id' => (int) $offer->id,
            'partyId' => (int) $offer->party_id,
            'name' => $offer->name,
            'isOpen' => (bool) $offer->is_open,
            'items' => collect($offer->inventory_items ?? [])->map(fn ($item, $index) => [
                'id' => (int) ($item['id'] ?? $index + 1),
                'name' => (string) ($item['name'] ?? ''),
                'quantity' => (int) ($item['quantity'] ?? 1),
                'priceCopper' => (int) ($item['price_copper'] ?? 0),
                'priceDisplay' => $this->formatCopper((int) ($item['price_copper'] ?? 0)),
                'category' => $item['category'] ?? null,
                'notes' => $item['notes'] ?? null,
            ])->values()->all(),
            'sellOffers' => $offer->sellOffers->map(function ($sellOffer) {
                return [
                    'id' => (int) $sellOffer->id,
                    'partyCharacterId' => (int) $sellOffer->party_character_id,
                    'partyCharacterName' => $sellOffer->character?->user?->name ?? $sellOffer->character?->name,
                    'inventoryItemId' => (int) $sellOffer->inventory_item_id,
                    'itemName' => $sellOffer->item_snapshot['name'] ?? null,
                    'itemCategory' => $sellOffer->item_snapshot['category'] ?? null,
                    'quantity' => (int) $sellOffer->quantity,
                    'amountCopper' => (int) $sellOffer->amount_copper,
                    'amountDisplay' => $this->formatCopper((int) $sellOffer->amount_copper),
                    'status' => $sellOffer->status,
                    'createdByUserId' => $sellOffer->created_by_user_id ? (int) $sellOffer->created_by_user_id : null,
                    'createdByUserName' => $sellOffer->createdByUser?->name,
                    'resolvedByUserId' => $sellOffer->resolved_by_user_id ? (int) $sellOffer->resolved_by_user_id : null,
                    'resolvedByUserName' => $sellOffer->resolvedByUser?->name,
                    'resolvedAt' => optional($sellOffer->resolved_at)?->toIso8601String(),
                    'createdAt' => optional($sellOffer->created_at)?->toIso8601String(),
                ];
            })->values()->all(),
            'activePartyCharacterId' => $offer->active_party_character_id ? (int) $offer->active_party_character_id : null,
            'activeCharacterUserId' => $offer->activeCharacter?->user_id ? (int) $offer->activeCharacter->user_id : null,
            'activeCharacterName' => $offer->activeCharacter?->user?->name ?? $offer->activeCharacter?->name,
            'openedAt' => optional($offer->opened_at)?->toIso8601String(),
            'closedAt' => optional($offer->closed_at)?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function mapWallet($wallet): array
    {
        $copperBalance = (int) ($wallet?->copper_balance ?? 0);
        $coins = $this->splitCopper($copperBalance);

        return [
            'id' => $wallet?->id ? (int) $wallet->id : null,
            'copperBalance' => $copperBalance,
            'coins' => $coins,
            'display' => sprintf('%dG %dS %dK', $coins['gold'], $coins['silver'], $coins['copper']),
            'transactions' => collect($wallet?->transactions ?? [])->map(function ($transaction) {
                $amountCopper = (int) $transaction->amount_copper;
                $amountCoins = $this->splitCopper($amountCopper);

                return [
                    'id' => (int) $transaction->id,
                    'walletId' => (int) $transaction->wallet_id,
                    'actorUserId' => $transaction->actor_user_id ? (int) $transaction->actor_user_id : null,
                    'actorUserName' => $transaction->actor?->name,
                    'type' => $transaction->type,
                    'amountCopper' => $amountCopper,
                    'amountDisplay' => sprintf('%dG %dS %dK', $amountCoins['gold'], $amountCoins['silver'], $amountCoins['copper']),
                    'note' => $transaction->note,
                    'createdAt' => optional($transaction->created_at)?->toIso8601String(),
                ];
            })->values()->all(),
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
