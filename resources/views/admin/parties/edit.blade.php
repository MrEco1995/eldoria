<x-admin.layout title="Admin - Party bearbeiten">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Party bearbeiten</h1>
        <a href="{{ route('admin.parties.index') }}" class="btn btn-sm btn-outline-secondary">Zurück</a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="post" action="{{ route('admin.parties.update', $party) }}" class="row g-3">
                @csrf
                <div class="col-12 col-lg-6">
                    <label class="form-label">Name</label>
                    <input
                        name="name"
                        class="form-control"
                        value="{{ old('name', $party->name) }}"
                        required
                    >
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">Owner</label>
                    <select name="owner_id" class="form-select" required>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((int) old('owner_id', $party->owner_id) === (int) $user->id)>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="lobby" @selected(old('status', $party->started_at ? 'started' : 'lobby') === 'lobby')>Lobby</option>
                        <option value="started" @selected(old('status', $party->started_at ? 'started' : 'lobby') === 'started')>Gestartet</option>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Started At</label>
                    <input
                        name="started_at"
                        type="datetime-local"
                        class="form-control"
                        value="{{ old('started_at', $party->started_at?->format('Y-m-d\TH:i')) }}"
                    >
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Party-ID</label>
                    <input class="form-control" value="{{ $party->id }}" disabled>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Mitglieder</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>E-Mail</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($party->members as $member)
                                    <tr>
                                        <td>{{ $member->name }}</td>
                                        <td class="text-muted small">{{ $member->email }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-3">Keine Mitglieder.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Charaktere</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Spieler</th>
                                    <th>Volk / Klasse</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($party->characters as $character)
                                    <tr>
                                        <td>{{ $character->name }}</td>
                                        <td>{{ $character->user?->name ?? 'Unbekannt' }}</td>
                                        <td class="text-muted small">{{ $character->race }} / {{ $character->class_name }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Keine Charaktere.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin.layout>
