<x-admin.layout title="Admin - Schwierigkeit bearbeiten">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Schwierigkeit bearbeiten</h1>
        <a href="{{ route('admin.check-difficulties.index') }}" class="btn btn-sm btn-outline-secondary">Zurueck</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="{{ route('admin.check-difficulties.update', $difficulty) }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-5">
                    <label class="form-label">Name</label>
                    <input name="label" class="form-control" value="{{ old('label', $difficulty->label) }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Key</label>
                    <input name="key" class="form-control" value="{{ old('key', $difficulty->key) }}" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">SG</label>
                    <input name="sg" type="number" min="1" max="30" class="form-control" value="{{ old('sg', $difficulty->sg) }}" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Sort</label>
                    <input name="sort_order" type="number" min="0" class="form-control" value="{{ old('sort_order', $difficulty->sort_order) }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</x-admin.layout>
