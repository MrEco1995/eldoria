<x-admin.layout title="Admin - Völker">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Völker</h1>
        <span class="text-muted small">Gesamt: {{ $races->total() }}</span>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Neues Volk anlegen</h2>
            <form method="post" action="{{ route('admin.races.store') }}" class="row g-2">
                @csrf
                <div class="col-12 col-md-4">
                    <input name="name" class="form-control" placeholder="Name" required>
                </div>
                <div class="col-12 col-md-8">
                    <input name="description" class="form-control" placeholder="Beschreibung" required>
                </div>
                <div class="col-12 col-md-4">
                    <input name="age_text" class="form-control" placeholder="Alter" required>
                </div>
                <div class="col-12 col-md-4">
                    <input name="height_text" class="form-control" placeholder="Größe" required>
                </div>
                <div class="col-12 col-md-4">
                    <input name="weight_text" class="form-control" placeholder="Gewicht" required>
                </div>
                <div class="col-12 col-md-6">
                    <textarea name="essence" class="form-control" rows="3" placeholder="Wesen (Zeilen oder Komma getrennt)"></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <textarea name="appearance" class="form-control" rows="3" placeholder="Aussehen (Zeilen oder Komma getrennt)"></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <input name="good_with" class="form-control" placeholder="Gut mit (Komma getrennt)">
                </div>
                <div class="col-12 col-md-4">
                    <input name="bad_with" class="form-control" placeholder="Schlecht mit (Komma getrennt)">
                </div>
                <div class="col-6 col-md-1">
                    <input name="sort_order" type="number" min="0" class="form-control" placeholder="Sort">
                </div>
                <div class="col-6 col-md-1">
                    <button type="submit" class="btn btn-primary w-100">+</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Sort</th>
                        <th>Beschreibung</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($races as $race)
                        <tr>
                            <td>{{ $race->name }}</td>
                            <td>
                                <span class="badge {{ $race->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $race->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </span>
                            </td>
                            <td>{{ $race->sort_order }}</td>
                            <td>{{ $race->description }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.races.edit', $race) }}" class="btn btn-sm btn-outline-primary">Bearbeiten</a>
                                <form method="post" action="{{ route('admin.races.toggle', $race) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        {{ $race->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Keine Völker vorhanden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $races->links() }}
    </div>
</x-admin.layout>
