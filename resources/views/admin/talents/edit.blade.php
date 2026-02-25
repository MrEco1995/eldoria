<x-admin.layout title="Admin - Talent bearbeiten">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Talent bearbeiten</h1>
        <a href="{{ route('admin.talents.index') }}" class="btn btn-sm btn-outline-secondary">Zurueck</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="{{ route('admin.talents.update', $talent) }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">Name</label>
                    <input name="label" class="form-control" value="{{ old('label', $talent->label) }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Gruppe/Kategorie</label>
                    <input name="category" class="form-control" list="talent-groups" value="{{ old('category', $talent->category) }}" required>
                    <datalist id="talent-groups">
                        @foreach ($groups as $group)
                            <option value="{{ $group }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Key</label>
                    <input name="key" class="form-control" value="{{ old('key', $talent->key) }}" required>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label">Max Punkte</label>
                    <input name="max_points" type="number" min="1" max="50" class="form-control" value="{{ old('max_points', $talent->max_points) }}" required>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label">Sortierung</label>
                    <input name="sort_order" type="number" min="0" class="form-control" value="{{ old('sort_order', $talent->sort_order) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Beschreibung</label>
                    <textarea name="description" rows="4" class="form-control">{{ old('description', $talent->description) }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</x-admin.layout>
