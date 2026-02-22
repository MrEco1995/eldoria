<x-admin.layout title="Admin - Volk bearbeiten">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Volk bearbeiten</h1>
        <a href="{{ route('admin.races.index') }}" class="btn btn-sm btn-outline-secondary">Zurück</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="{{ route('admin.races.update', $race) }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" value="{{ old('name', $race->name) }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Sortierung</label>
                    <input name="sort_order" type="number" min="0" class="form-control" value="{{ old('sort_order', $race->sort_order) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Beschreibung</label>
                    <textarea name="description" class="form-control" rows="2" required>{{ old('description', $race->description) }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Alter</label>
                    <input name="age_text" class="form-control" value="{{ old('age_text', $race->age_text) }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Größe</label>
                    <input name="height_text" class="form-control" value="{{ old('height_text', $race->height_text) }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Gewicht</label>
                    <input name="weight_text" class="form-control" value="{{ old('weight_text', $race->weight_text) }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Wesen (Zeilen oder Komma getrennt)</label>
                    <textarea name="essence" class="form-control" rows="4">{{ old('essence', implode("\n", $race->essence ?? [])) }}</textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Aussehen (Zeilen oder Komma getrennt)</label>
                    <textarea name="appearance" class="form-control" rows="4">{{ old('appearance', implode("\n", $race->appearance ?? [])) }}</textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Gut mit (Komma getrennt)</label>
                    <input name="good_with" class="form-control" value="{{ old('good_with', implode(', ', $race->good_with ?? [])) }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Schlecht mit (Komma getrennt)</label>
                    <input name="bad_with" class="form-control" value="{{ old('bad_with', implode(', ', $race->bad_with ?? [])) }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</x-admin.layout>
