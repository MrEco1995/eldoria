<?php

namespace App\Services;

use App\Models\Party;
use App\Models\PartyInvite;
use App\Models\PartyTalentRequest;
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
            'talents' => $this->loadTalents(),
            'talentPointPool' => config('game.character_talent_point_pool', 35),
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
}

