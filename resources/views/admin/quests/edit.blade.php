<x-admin.layout title="Admin - Quest bearbeiten">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Quest bearbeiten</h1>
        <a href="{{ route('admin.quests.index') }}" class="btn btn-sm btn-outline-secondary">Zurueck</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="{{ route('admin.quests.update', $quest) }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-8">
                    <label class="form-label">Titel</label>
                    <input name="title" class="form-control" value="{{ old('title', $quest->title) }}" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Level</label>
                    <input name="recommended_party_level" type="number" min="1" max="20" class="form-control" value="{{ old('recommended_party_level', $quest->recommended_party_level) }}">
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label">Diff</label>
                    <input name="difficulty" type="number" min="1" max="5" class="form-control" value="{{ old('difficulty', $quest->difficulty) }}">
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label">Sort</label>
                    <input name="sort_order" type="number" min="0" class="form-control" value="{{ old('sort_order', $quest->sort_order) }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Ort</label>
                    <input name="location" class="form-control" value="{{ old('location', $quest->location) }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Stimmung</label>
                    <input name="mood" class="form-control" value="{{ old('mood', $quest->mood) }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Ausgangssituation</label>
                    <textarea name="intro" rows="5" class="form-control">{{ old('intro', $quest->intro) }}</textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Belohnung</label>
                    <textarea name="reward" rows="5" class="form-control">{{ old('reward', $quest->reward) }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Akt 1</label>
                    <textarea name="act_1" rows="8" class="form-control">{{ old('act_1', $quest->act_1) }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Akt 2</label>
                    <textarea name="act_2" rows="8" class="form-control">{{ old('act_2', $quest->act_2) }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Akt 3</label>
                    <textarea name="act_3" rows="8" class="form-control">{{ old('act_3', $quest->act_3) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Entscheidungspunkt</label>
                    <textarea name="decision_point" rows="3" class="form-control">{{ old('decision_point', $quest->decision_point) }}</textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Ende 1 (Freilassen)</label>
                    <textarea name="ending_release" rows="8" class="form-control">{{ old('ending_release', $quest->ending_release) }}</textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Ende 2 (Fangen/Toeten)</label>
                    <textarea name="ending_capture" rows="8" class="form-control">{{ old('ending_capture', $quest->ending_capture) }}</textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Folgequest bei Ende 1</label>
                    <input name="next_quest_release_title" class="form-control" value="{{ old('next_quest_release_title', $quest->next_quest_release_title) }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Folgequest bei Ende 2</label>
                    <input name="next_quest_capture_title" class="form-control" value="{{ old('next_quest_capture_title', $quest->next_quest_capture_title) }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</x-admin.layout>
