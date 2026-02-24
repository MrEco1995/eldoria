<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuestController extends Controller
{
    public function index(): View
    {
        $quests = Quest::query()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate(20);

        return view('admin.quests.index', ['quests' => $quests]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRequest($request);

        Quest::create([
            ...$data,
            'key' => $this->makeUniqueKey($data['title']),
            'is_active' => true,
        ]);

        return redirect()->route('admin.quests.index')->with('status', 'Quest angelegt.');
    }

    public function edit(Quest $quest): View
    {
        return view('admin.quests.edit', ['quest' => $quest]);
    }

    public function update(Request $request, Quest $quest): RedirectResponse
    {
        $data = $this->validateRequest($request);

        $quest->update([
            ...$data,
            'key' => $this->makeUniqueKey($data['title'], $quest->id),
        ]);

        return redirect()->route('admin.quests.index')->with('status', 'Quest aktualisiert.');
    }

    public function toggle(Quest $quest): RedirectResponse
    {
        $quest->update(['is_active' => !$quest->is_active]);

        return redirect()->route('admin.quests.index')->with('status', 'Status aktualisiert.');
    }

    public function destroy(Quest $quest): RedirectResponse
    {
        $quest->delete();

        return redirect()->route('admin.quests.index')->with('status', 'Quest geloescht.');
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'location' => ['nullable', 'string', 'max:180'],
            'mood' => ['nullable', 'string', 'max:180'],
            'intro' => ['nullable', 'string'],
            'reward' => ['nullable', 'string'],
            'act_1' => ['nullable', 'string'],
            'act_2' => ['nullable', 'string'],
            'act_3' => ['nullable', 'string'],
            'decision_point' => ['nullable', 'string'],
            'ending_release_label' => ['nullable', 'string', 'max:180'],
            'ending_release' => ['nullable', 'string'],
            'ending_capture_label' => ['nullable', 'string', 'max:180'],
            'ending_capture' => ['nullable', 'string'],
            'next_quest_release_title' => ['nullable', 'string', 'max:180'],
            'next_quest_capture_title' => ['nullable', 'string', 'max:180'],
            'recommended_party_level' => ['nullable', 'integer', 'min:1', 'max:20'],
            'difficulty' => ['nullable', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function makeUniqueKey(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title, '_');
        if ($base === '') {
            $base = 'quest';
        }

        $key = $base;
        $counter = 2;

        while (Quest::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('key', $key)
            ->exists()) {
            $key = "{$base}_{$counter}";
            $counter++;
        }

        return $key;
    }
}
