<x-admin.layout title="Admin - Quests">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Quests</h1>
        <span class="text-muted small">Gesamt: {{ $quests->total() }}</span>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Neue Quest anlegen</h2>
            <form method="post" action="{{ route('admin.quests.store') }}" class="row g-2">
                @csrf
                <div class="col-12 col-md-8">
                    <input name="title" class="form-control" placeholder="Titel" required>
                </div>
                <div class="col-6 col-md-2">
                    <input name="recommended_party_level" type="number" min="1" max="20" class="form-control" placeholder="Level">
                </div>
                <div class="col-6 col-md-1">
                    <input name="difficulty" type="number" min="1" max="5" class="form-control" placeholder="Diff">
                </div>
                <div class="col-6 col-md-1">
                    <input name="sort_order" type="number" min="0" class="form-control" placeholder="Sort">
                </div>
                <div class="col-12 col-md-6">
                    <input name="location" class="form-control" placeholder="Ort">
                </div>
                <div class="col-12 col-md-6">
                    <input name="mood" class="form-control" placeholder="Stimmung">
                </div>
                <div class="col-12 col-md-6">
                    <textarea name="intro" rows="3" class="form-control" placeholder="Ausgangssituation"></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <textarea name="reward" rows="3" class="form-control" placeholder="Belohnung"></textarea>
                </div>
                <div class="col-12 col-md-4">
                    <textarea name="act_1" rows="4" class="form-control" placeholder="Akt 1"></textarea>
                </div>
                <div class="col-12 col-md-4">
                    <textarea name="act_2" rows="4" class="form-control" placeholder="Akt 2"></textarea>
                </div>
                <div class="col-12 col-md-4">
                    <textarea name="act_3" rows="4" class="form-control" placeholder="Akt 3"></textarea>
                </div>
                <div class="col-12">
                    <textarea name="decision_point" rows="2" class="form-control" placeholder="Entscheidungspunkt"></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <textarea name="ending_release" rows="4" class="form-control" placeholder="Ende 1 (Freilassen)"></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <textarea name="ending_capture" rows="4" class="form-control" placeholder="Ende 2 (Fangen/Toeten)"></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <input name="next_quest_release_title" class="form-control" placeholder="Folgequest bei Ende 1">
                </div>
                <div class="col-12 col-md-6">
                    <input name="next_quest_capture_title" class="form-control" placeholder="Folgequest bei Ende 2">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Quest anlegen</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Titel</th>
                        <th>Ort</th>
                        <th>Level</th>
                        <th>Diff</th>
                        <th>Status</th>
                        <th>Sort</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quests as $quest)
                        <tr>
                            <td>{{ $quest->title }}</td>
                            <td>{{ $quest->location }}</td>
                            <td>{{ $quest->recommended_party_level }}</td>
                            <td>{{ $quest->difficulty }}</td>
                            <td>
                                <span class="badge {{ $quest->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $quest->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </span>
                            </td>
                            <td>{{ $quest->sort_order }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.quests.edit', $quest) }}" class="btn btn-sm btn-outline-primary">Bearbeiten</a>
                                <form method="post" action="{{ route('admin.quests.toggle', $quest) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        {{ $quest->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                    </button>
                                </form>
                                <form method="post" action="{{ route('admin.quests.destroy', $quest) }}" class="d-inline" onsubmit="return confirm('Wirklich loeschen?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Loeschen</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">Keine Quests vorhanden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $quests->links() }}
    </div>
</x-admin.layout>
