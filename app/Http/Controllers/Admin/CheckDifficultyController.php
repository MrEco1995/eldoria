<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckDifficulty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckDifficultyController extends Controller
{
    public function index(): View
    {
        $difficulties = CheckDifficulty::query()
            ->orderBy('sort_order')
            ->orderBy('sg')
            ->paginate(30);

        return view('admin.check-difficulties.index', [
            'difficulties' => $difficulties,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRequest($request);
        $manualKey = trim((string) ($data['key'] ?? ''));

        CheckDifficulty::create([
            ...$data,
            'key' => $manualKey !== '' ? $manualKey : $this->makeUniqueKey($data['label']),
            'is_active' => true,
        ]);

        return redirect()->route('admin.check-difficulties.index')->with('status', 'Schwierigkeit angelegt.');
    }

    public function edit(CheckDifficulty $checkDifficulty): View
    {
        return view('admin.check-difficulties.edit', [
            'difficulty' => $checkDifficulty,
        ]);
    }

    public function update(Request $request, CheckDifficulty $checkDifficulty): RedirectResponse
    {
        $data = $this->validateRequest($request, $checkDifficulty);
        $checkDifficulty->update($data);

        return redirect()->route('admin.check-difficulties.index')->with('status', 'Schwierigkeit aktualisiert.');
    }

    public function toggle(CheckDifficulty $checkDifficulty): RedirectResponse
    {
        $checkDifficulty->update(['is_active' => !$checkDifficulty->is_active]);

        return redirect()->route('admin.check-difficulties.index')->with('status', 'Status aktualisiert.');
    }

    public function destroy(CheckDifficulty $checkDifficulty): RedirectResponse
    {
        $checkDifficulty->delete();

        return redirect()->route('admin.check-difficulties.index')->with('status', 'Schwierigkeit geloescht.');
    }

    private function validateRequest(Request $request, ?CheckDifficulty $difficulty = null): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'sg' => ['required', 'integer', 'min:1', 'max:30'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'key' => [
                'nullable',
                'string',
                'max:60',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('check_difficulties', 'key')->ignore($difficulty?->id),
            ],
        ]);
    }

    private function makeUniqueKey(string $label): string
    {
        $base = Str::of($label)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();
        if ($base === '') {
            $base = 'difficulty';
        }

        $key = $base;
        $counter = 2;

        while (CheckDifficulty::query()->where('key', $key)->exists()) {
            $key = "{$base}_{$counter}";
            $counter++;
        }

        return $key;
    }
}
