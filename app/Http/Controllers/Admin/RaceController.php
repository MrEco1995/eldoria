<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Race;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RaceController extends Controller
{
    public function index(): View
    {
        $races = Race::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.races.index', ['races' => $races]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('races', 'name')],
            'description' => ['required', 'string'],
            'hp_base' => ['required', 'integer', 'min:0', 'max:500'],
            'essence' => ['nullable', 'string'],
            'appearance' => ['nullable', 'string'],
            'age_text' => ['required', 'string', 'max:180'],
            'height_text' => ['required', 'string', 'max:180'],
            'weight_text' => ['required', 'string', 'max:180'],
            'good_with' => ['nullable', 'string'],
            'bad_with' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        Race::create([
            'name' => $data['name'],
            'slug' => $this->makeUniqueSlug($data['name']),
            'description' => $data['description'],
            'hp_base' => (int) $data['hp_base'],
            'essence' => $this->parseList($data['essence'] ?? null),
            'appearance' => $this->parseList($data['appearance'] ?? null),
            'age_text' => $data['age_text'],
            'height_text' => $data['height_text'],
            'weight_text' => $data['weight_text'],
            'good_with' => $this->parseList($data['good_with'] ?? null),
            'bad_with' => $this->parseList($data['bad_with'] ?? null),
            'is_active' => true,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->route('admin.races.index')->with('status', 'Volk angelegt.');
    }

    public function edit(Race $race): View
    {
        return view('admin.races.edit', ['race' => $race]);
    }

    public function update(Request $request, Race $race): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('races', 'name')->ignore($race->id)],
            'description' => ['required', 'string'],
            'hp_base' => ['required', 'integer', 'min:0', 'max:500'],
            'essence' => ['nullable', 'string'],
            'appearance' => ['nullable', 'string'],
            'age_text' => ['required', 'string', 'max:180'],
            'height_text' => ['required', 'string', 'max:180'],
            'weight_text' => ['required', 'string', 'max:180'],
            'good_with' => ['nullable', 'string'],
            'bad_with' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $race->update([
            'name' => $data['name'],
            'slug' => $this->makeUniqueSlug($data['name'], $race->id),
            'description' => $data['description'],
            'hp_base' => (int) $data['hp_base'],
            'essence' => $this->parseList($data['essence'] ?? null),
            'appearance' => $this->parseList($data['appearance'] ?? null),
            'age_text' => $data['age_text'],
            'height_text' => $data['height_text'],
            'weight_text' => $data['weight_text'],
            'good_with' => $this->parseList($data['good_with'] ?? null),
            'bad_with' => $this->parseList($data['bad_with'] ?? null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->route('admin.races.index')->with('status', 'Volk aktualisiert.');
    }

    public function toggle(Race $race): RedirectResponse
    {
        $race->update(['is_active' => !$race->is_active]);

        return redirect()->route('admin.races.index')->with('status', 'Status aktualisiert.');
    }

    private function parseList(?string $value): array
    {
        if (!$value) return [];

        return collect(preg_split('/[\r\n,]+/', $value))
            ->map(fn ($entry) => trim((string) $entry))
            ->filter()
            ->values()
            ->all();
    }

    private function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (Race::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
