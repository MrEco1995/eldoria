<x-admin.layout title="Admin - Klasse bearbeiten">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Klasse bearbeiten</h1>
        <a href="{{ route('admin.classes.index') }}" class="btn btn-sm btn-outline-secondary">Zurück</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="{{ route('admin.classes.update', $class) }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" value="{{ old('name', $class->name) }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Beschreibung</label>
                    <input name="description" class="form-control" value="{{ old('description', $class->description) }}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">Sortierung</label>
                    <input name="sort_order" type="number" min="0" class="form-control" value="{{ old('sort_order', $class->sort_order) }}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">HP Base</label>
                    <input name="hp_base" type="number" min="0" class="form-control" value="{{ old('hp_base', $class->hp_base) }}" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</x-admin.layout>
