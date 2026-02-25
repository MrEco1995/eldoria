<x-admin.layout title="Admin - Talente">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Talente</h1>
        <span class="text-muted small">Gesamt: {{ $talents->total() }}</span>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Neues Talent anlegen</h2>
            <form method="post" action="{{ route('admin.talents.store') }}" class="row g-2">
                @csrf
                <div class="col-12 col-md-4">
                    <input name="label" class="form-control" placeholder="Name" required>
                </div>
                <div class="col-12 col-md-4">
                    <input name="category" class="form-control" list="talent-groups" placeholder="Gruppe/Kategorie" required>
                    <datalist id="talent-groups">
                        @foreach ($groups as $group)
                            <option value="{{ $group }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div class="col-6 col-md-2">
                    <input name="max_points" type="number" min="1" max="50" class="form-control" placeholder="Max Punkte" value="15" required>
                </div>
                <div class="col-6 col-md-2">
                    <input name="sort_order" type="number" min="0" class="form-control" placeholder="Sort">
                </div>
                <div class="col-12">
                    <input name="key" class="form-control" placeholder="Key (optional, z.B. alchemie_wissen)">
                </div>
                <div class="col-12">
                    <textarea name="description" rows="2" class="form-control" placeholder="Beschreibung"></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Talent anlegen</button>
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
                        <th>Key</th>
                        <th>Gruppe</th>
                        <th>Max</th>
                        <th>Status</th>
                        <th>Sort</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($talents as $talent)
                        <tr>
                            <td>{{ $talent->label }}</td>
                            <td><code>{{ $talent->key }}</code></td>
                            <td>{{ $talent->category }}</td>
                            <td>{{ $talent->max_points }}</td>
                            <td>
                                <span class="badge {{ $talent->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $talent->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </span>
                            </td>
                            <td>{{ $talent->sort_order }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.talents.edit', $talent) }}" class="btn btn-sm btn-outline-primary">Bearbeiten</a>
                                <form method="post" action="{{ route('admin.talents.toggle', $talent) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        {{ $talent->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                    </button>
                                </form>
                                <form method="post" action="{{ route('admin.talents.destroy', $talent) }}" class="d-inline" onsubmit="return confirm('Wirklich loeschen?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Loeschen</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">Keine Talente vorhanden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $talents->links() }}
    </div>
</x-admin.layout>
