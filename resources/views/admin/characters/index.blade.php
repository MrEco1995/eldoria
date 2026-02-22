<x-admin.layout title="Admin - Charaktere">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Charaktere</h1>
        <span class="text-muted small">Gesamt: {{ $characters->total() }}</span>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Name</th>
                        <th>Volk</th>
                        <th>Klasse</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($characters as $entry)
                        <tr>
                            <td>{{ $entry->user?->name ?? '-' }}</td>
                            <td>
                                <a href="{{ route('admin.characters.show', $entry) }}" class="text-decoration-none">
                                    {{ $entry->name }}
                                </a>
                            </td>
                            <td>{{ $entry->race }}</td>
                            <td>{{ $entry->class_name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Keine Charaktere gefunden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $characters->links() }}
    </div>
</x-admin.layout>
