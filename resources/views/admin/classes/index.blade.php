<x-admin.layout title="Admin - Klassen">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Klassen</h1>
        <span class="text-muted small">Gesamt: {{ $classes->total() }}</span>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Neue Klasse anlegen</h2>
            <form method="post" action="{{ route('admin.classes.store') }}" class="row g-2">
                @csrf
                <div class="col-12 col-md-4">
                    <input name="name" class="form-control" placeholder="Name" required>
                </div>
                <div class="col-12 col-md-4">
                    <input name="description" class="form-control" placeholder="Beschreibung (optional)">
                </div>
                <div class="col-6 col-md-1">
                    <input name="hp_base" type="number" min="0" class="form-control" placeholder="HP" required>
                </div>
                <div class="col-6 col-md-2">
                    <input name="sort_order" type="number" min="0" class="form-control" placeholder="Sortierung">
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
                        <th>HP Base</th>
                        <th>Status</th>
                        <th>Sort</th>
                        <th>Beschreibung</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $entry)
                        <tr>
                            <td>{{ $entry->name }}</td>
                            <td>{{ $entry->hp_base }}</td>
                            <td>
                                <span class="badge {{ $entry->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $entry->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </span>
                            </td>
                            <td>{{ $entry->sort_order }}</td>
                            <td>{{ $entry->description }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.classes.edit', $entry) }}" class="btn btn-sm btn-outline-primary">Bearbeiten</a>
                                <form method="post" action="{{ route('admin.classes.toggle', $entry) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        {{ $entry->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Keine Klassen vorhanden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $classes->links() }}
    </div>
</x-admin.layout>
