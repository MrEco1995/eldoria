<x-admin.layout title="Admin - Schwierigkeiten">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Schwierigkeiten (SG)</h1>
        <span class="text-muted small">Gesamt: {{ $difficulties->total() }}</span>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Neue Schwierigkeit anlegen</h2>
            <form method="post" action="{{ route('admin.check-difficulties.store') }}" class="row g-2">
                @csrf
                <div class="col-12 col-md-5">
                    <input name="label" class="form-control" placeholder="Name (z. B. Sehr schwer)" required>
                </div>
                <div class="col-12 col-md-3">
                    <input name="key" class="form-control" placeholder="key (optional, z. B. sehr_schwer)">
                </div>
                <div class="col-6 col-md-2">
                    <input name="sg" type="number" min="1" max="30" class="form-control" placeholder="SG" required>
                </div>
                <div class="col-6 col-md-2">
                    <input name="sort_order" type="number" min="0" class="form-control" placeholder="Sort">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Anlegen</button>
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
                        <th>SG</th>
                        <th>Status</th>
                        <th>Sort</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($difficulties as $difficulty)
                        <tr>
                            <td>{{ $difficulty->label }}</td>
                            <td><code>{{ $difficulty->key }}</code></td>
                            <td>{{ $difficulty->sg }}</td>
                            <td>
                                <span class="badge {{ $difficulty->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $difficulty->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </span>
                            </td>
                            <td>{{ $difficulty->sort_order }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.check-difficulties.edit', $difficulty) }}" class="btn btn-sm btn-outline-primary">Bearbeiten</a>
                                <form method="post" action="{{ route('admin.check-difficulties.toggle', $difficulty) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        {{ $difficulty->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                    </button>
                                </form>
                                <form method="post" action="{{ route('admin.check-difficulties.destroy', $difficulty) }}" class="d-inline" onsubmit="return confirm('Wirklich loeschen?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Loeschen</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Keine Schwierigkeiten vorhanden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $difficulties->links() }}
    </div>
</x-admin.layout>
