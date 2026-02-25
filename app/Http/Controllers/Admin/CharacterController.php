<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartyCharacter;
use App\Models\Talent;
use Illuminate\View\View;

class CharacterController extends Controller
{
    public function index(): View
    {
        $characters = PartyCharacter::query()
            ->with('user:id,name,email')
            ->select('id', 'party_id', 'user_id', 'name', 'race', 'class_name', 'hp_max', 'hp_current', 'hp_temp')
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.characters.index', [
            'characters' => $characters,
        ]);
    }

    public function show(PartyCharacter $character): View
    {
        $character->load([
            'user:id,name,email',
            'party:id,name',
            'inventoryItems:id,party_character_id,name,quantity,category,notes,sort_order',
            'wallet.transactions' => fn ($query) => $query->with('actor:id,name')->orderByDesc('id')->limit(100),
            'mediafiles' => fn ($query) => $query
                ->wherePivot('role', 'character')
                ->latest('mediafiles.id'),
        ]);

        $talentLabels = Talent::query()
            ->where('is_active', true)
            ->pluck('label', 'key');

        return view('admin.characters.show', [
            'character' => $character,
            'talentLabels' => $talentLabels,
        ]);
    }
}
