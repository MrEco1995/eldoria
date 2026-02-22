<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CharacterClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CharacterClassController extends Controller
{
    public function index(): View
    {
        $classes = CharacterClass::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.classes.index', ['classes' => $classes]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('character_classes', 'name')],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        CharacterClass::create([
            'name' => $data['name'],
            'slug' => $this->makeUniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => true,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->route('admin.classes.index')->with('status', 'Klasse angelegt.');
    }

    public function edit(CharacterClass $characterClass): View
    {
        return view('admin.classes.edit', ['class' => $characterClass]);
    }

    public function update(Request $request, CharacterClass $characterClass): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('character_classes', 'name')->ignore($characterClass->id)],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $characterClass->update([
            'name' => $data['name'],
            'slug' => $this->makeUniqueSlug($data['name'], $characterClass->id),
            'description' => $data['description'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->route('admin.classes.index')->with('status', 'Klasse aktualisiert.');
    }

    public function toggle(CharacterClass $characterClass): RedirectResponse
    {
        $characterClass->update(['is_active' => !$characterClass->is_active]);

        return redirect()->route('admin.classes.index')->with('status', 'Status aktualisiert.');
    }

    private function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (CharacterClass::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
