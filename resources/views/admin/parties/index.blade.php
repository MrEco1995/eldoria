<x-admin.layout title="Admin - Partys">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Partys</h1>
        <span class="text-muted small">Gesamt: {{ $parties->total() }}</span>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Owner</th>
                        <th>Status</th>
                        <th>Mitglieder</th>
                        <th>Charaktere</th>
                        <th>Start</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parties as $party)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $party->name }}</div>
                                <div class="text-muted small">#{{ $party->id }}</div>
                            </td>
                            <td>
                                <div>{{ $party->owner?->name ?? 'Unbekannt' }}</div>
                                <div class="text-muted small">{{ $party->owner?->email }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $party->started_at ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $party->started_at ? 'Gestartet' : 'Lobby' }}
                                </span>
                            </td>
                            <td>{{ $party->members_count }}</td>
                            <td>{{ $party->characters_count }}</td>
                            <td class="text-muted small">
                                {{ $party->started_at?->format('d.m.Y H:i') ?? '-' }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.parties.edit', $party) }}" class="btn btn-sm btn-outline-primary">Bearbeiten</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">Keine Partys vorhanden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $parties->links() }}
    </div>
</x-admin.layout>
