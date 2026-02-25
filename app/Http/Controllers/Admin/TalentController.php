<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Talent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TalentController extends Controller
{
    public function index(): View
    {
        $talents = Talent::query()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->paginate(40);

        $groups = Talent::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return view('admin.talents.index', [
            'talents' => $talents,
            'groups' => $groups,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRequest($request);
        $manualKey = trim((string) ($data['key'] ?? ''));

        Talent::create([
            ...$data,
            'key' => $manualKey !== '' ? $manualKey : $this->makeUniqueKey($data['label']),
            'is_active' => true,
        ]);

        return redirect()->route('admin.talents.index')->with('status', 'Talent angelegt.');
    }

    public function edit(Talent $talent): View
    {
        $groups = Talent::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return view('admin.talents.edit', [
            'talent' => $talent,
            'groups' => $groups,
        ]);
    }

    public function update(Request $request, Talent $talent): RedirectResponse
    {
        $data = $this->validateRequest($request, $talent);

        $talent->update($data);

        return redirect()->route('admin.talents.index')->with('status', 'Talent aktualisiert.');
    }

    public function toggle(Talent $talent): RedirectResponse
    {
        $talent->update(['is_active' => !$talent->is_active]);

        return redirect()->route('admin.talents.index')->with('status', 'Status aktualisiert.');
    }

    public function destroy(Talent $talent): RedirectResponse
    {
        $talent->delete();

        return redirect()->route('admin.talents.index')->with('status', 'Talent geloescht.');
    }

    private function validateRequest(Request $request, ?Talent $talent = null): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'max_points' => ['required', 'integer', 'min:1', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'key' => [
                'nullable',
                'string',
                'max:60',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('talents', 'key')->ignore($talent?->id),
            ],
        ]);
    }

    private function makeUniqueKey(string $label): string
    {
        $base = Str::of($label)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();
        if ($base === '') {
            $base = 'talent';
        }

        $key = $base;
        $counter = 2;

        while (Talent::query()->where('key', $key)->exists()) {
            $key = "{$base}_{$counter}";
            $counter++;
        }

        return $key;
    }
}
