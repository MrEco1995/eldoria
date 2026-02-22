<x-admin.layout title="Admin - User">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">User</h1>
            <div class="text-muted small">Alle registrierten Benutzer</div>
        </div>
        <div class="text-muted small">
            Gesamt: {{ $users->total() }}
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Partys</th>
                        <th>Erstellt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @php
                                    $ownedPartyIds = $user->ownedParties->pluck('id')->all();
                                    $memberParties = $user->parties->whereNotIn('id', $ownedPartyIds);
                                @endphp

                                @if ($user->ownedParties->isEmpty() && $memberParties->isEmpty())
                                    <span class="text-muted small">Keine</span>
                                @else
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($user->ownedParties as $party)
                                            <span class="badge text-bg-warning">
                                                {{ $party->name }} (Owner)
                                            </span>
                                        @endforeach
                                        @foreach ($memberParties as $party)
                                            <span class="badge text-bg-light border">
                                                {{ $party->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>{{ optional($user->created_at)->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Keine User gefunden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $users->links() }}
    </div>
</x-admin.layout>
