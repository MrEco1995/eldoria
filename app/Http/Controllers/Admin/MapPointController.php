<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointOfInterest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MapPointController extends Controller
{
    public function index(): View
    {
        $points = PointOfInterest::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.map.index', [
            'points' => $points,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'type' => ['required', Rule::in(['landmark', 'village'])],
            'x_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'y_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_zoom' => ['required', 'numeric', 'min:1', 'max:4'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        PointOfInterest::create([
            'slug' => $this->makeUniqueSlug($data['name']),
            'name' => $data['name'],
            'type' => $data['type'],
            'x_percent' => round((float) $data['x_percent'], 2),
            'y_percent' => round((float) $data['y_percent'], 2),
            'min_zoom' => round((float) $data['min_zoom'], 2),
            'description' => $data['description'] ?? null,
            'is_active' => true,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->route('admin.map.index')->with('status', 'Point of Interest angelegt.');
    }

    public function update(Request $request, PointOfInterest $point): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'type' => ['required', Rule::in(['landmark', 'village'])],
            'x_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'y_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_zoom' => ['required', 'numeric', 'min:1', 'max:4'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $point->update([
            'slug' => $this->makeUniqueSlug($data['name'], $point->id),
            'name' => $data['name'],
            'type' => $data['type'],
            'x_percent' => round((float) $data['x_percent'], 2),
            'y_percent' => round((float) $data['y_percent'], 2),
            'min_zoom' => round((float) $data['min_zoom'], 2),
            'description' => $data['description'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->route('admin.map.index')->with('status', 'Point of Interest aktualisiert.');
    }

    public function toggle(PointOfInterest $point): RedirectResponse
    {
        $point->update([
            'is_active' => !$point->is_active,
        ]);

        return redirect()->route('admin.map.index')->with('status', 'Status aktualisiert.');
    }

    public function destroy(PointOfInterest $point): RedirectResponse
    {
        $point->delete();

        return redirect()->route('admin.map.index')->with('status', 'Point of Interest gelöscht.');
    }

    private function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'poi';
        }

        $slug = $base;
        $counter = 2;

        while (PointOfInterest::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
