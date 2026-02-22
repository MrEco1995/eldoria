<x-admin.layout title="Admin - Map">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Map / Points of Interest</h1>
        <span class="text-muted small">Punkte: {{ $points->count() }}</span>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted mb-2">
                        Klick auf die Karte setzt automatisch Koordinaten (X/Y in Prozent).
                        Klick auf einen vorhandenen Punkt lädt ihn in die Bearbeitung.
                    </div>

                    <div id="poi-map" class="poi-map border rounded position-relative overflow-hidden">
                        <img src="/images/EldoriaMap.png" alt="Eldoria Map" class="w-100 h-100 d-block" style="object-fit: cover;">

                        @foreach ($points as $point)
                            <button
                                type="button"
                                class="poi-marker {{ $point->is_active ? '' : 'inactive' }} {{ $point->type === 'village' ? 'village' : '' }}"
                                style="left: {{ $point->x_percent }}%; top: {{ $point->y_percent }}%;"
                                data-id="{{ $point->id }}"
                                data-name="{{ $point->name }}"
                                data-type="{{ $point->type }}"
                                data-x="{{ $point->x_percent }}"
                                data-y="{{ $point->y_percent }}"
                                data-min-zoom="{{ $point->min_zoom }}"
                                data-description="{{ $point->description }}"
                                data-sort-order="{{ $point->sort_order }}"
                                data-update-url="{{ route('admin.map.update', $point) }}"
                                title="{{ $point->name }}"
                            >
                                <span class="dot"></span>
                                <span class="label">{{ $point->name }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6 mb-3">Neuen Punkt anlegen</h2>
                    <form method="post" action="{{ route('admin.map.store') }}" class="row g-2" id="create-point-form">
                        @csrf
                        <div class="col-12">
                            <input id="create-name" name="name" class="form-control" placeholder="Name" required>
                        </div>
                        <div class="col-6">
                            <select id="create-type" name="type" class="form-select" required>
                                <option value="landmark">Landmarke</option>
                                <option value="village">Dorf</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <input id="create-min-zoom" name="min_zoom" type="number" min="1" max="4" step="0.1" class="form-control" value="1.0" required>
                        </div>
                        <div class="col-6">
                            <input id="create-x" name="x_percent" type="number" min="0" max="100" step="0.01" class="form-control" placeholder="X %" required>
                        </div>
                        <div class="col-6">
                            <input id="create-y" name="y_percent" type="number" min="0" max="100" step="0.01" class="form-control" placeholder="Y %" required>
                        </div>
                        <div class="col-6">
                            <input id="create-sort-order" name="sort_order" type="number" min="0" class="form-control" placeholder="Sortierung">
                        </div>
                        <div class="col-12">
                            <textarea id="create-description" name="description" rows="2" class="form-control" placeholder="Beschreibung (optional)"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">Punkt anlegen</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Bestehenden Punkt bearbeiten</h2>
                    <div id="edit-hint" class="small text-muted mb-2">
                        Wähle einen Punkt auf der Karte oder in der Liste.
                    </div>
                    <form method="post" action="" class="row g-2 d-none" id="edit-point-form">
                        @csrf
                        <div class="col-12">
                            <input id="edit-name" name="name" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <select id="edit-type" name="type" class="form-select" required>
                                <option value="landmark">Landmarke</option>
                                <option value="village">Dorf</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <input id="edit-min-zoom" name="min_zoom" type="number" min="1" max="4" step="0.1" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <input id="edit-x" name="x_percent" type="number" min="0" max="100" step="0.01" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <input id="edit-y" name="y_percent" type="number" min="0" max="100" step="0.01" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <input id="edit-sort-order" name="sort_order" type="number" min="0" class="form-control">
                        </div>
                        <div class="col-12">
                            <textarea id="edit-description" name="description" rows="2" class="form-control"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-outline-primary w-100">Änderungen speichern</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Typ</th>
                        <th>Koordinaten</th>
                        <th>Zoom</th>
                        <th>Status</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($points as $point)
                        <tr>
                            <td>{{ $point->name }}</td>
                            <td>{{ $point->type }}</td>
                            <td>{{ $point->x_percent }} / {{ $point->y_percent }}</td>
                            <td>{{ $point->min_zoom }}</td>
                            <td>
                                <span class="badge {{ $point->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $point->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary me-1 js-edit-point"
                                    data-id="{{ $point->id }}"
                                    data-name="{{ $point->name }}"
                                    data-type="{{ $point->type }}"
                                    data-x="{{ $point->x_percent }}"
                                    data-y="{{ $point->y_percent }}"
                                    data-min-zoom="{{ $point->min_zoom }}"
                                    data-description="{{ $point->description }}"
                                    data-sort-order="{{ $point->sort_order }}"
                                    data-update-url="{{ route('admin.map.update', $point) }}"
                                >
                                    Bearbeiten
                                </button>
                                <form method="post" action="{{ route('admin.map.toggle', $point) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary me-1">
                                        {{ $point->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                    </button>
                                </form>
                                <form method="post" action="{{ route('admin.map.destroy', $point) }}" class="d-inline" onsubmit="return confirm('Wirklich löschen?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Löschen</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Keine Points of Interest vorhanden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .poi-map {
            min-height: 70vh;
            background: #0b1220;
        }
        .poi-marker {
            position: absolute;
            transform: translate(-50%, -100%);
            border: 0;
            background: transparent;
            padding: 0;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 0.15rem;
        }
        .poi-marker .dot {
            width: 0.85rem;
            height: 0.85rem;
            border-radius: 999px;
            background: #ef4444;
            border: 2px solid #fff;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.26);
        }
        .poi-marker.village .dot {
            width: 0.7rem;
            height: 0.7rem;
            background: #22c55e;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.24);
        }
        .poi-marker.inactive .dot {
            background: #9ca3af;
            box-shadow: 0 0 0 2px rgba(156, 163, 175, 0.26);
        }
        .poi-marker .label {
            font-size: 0.68rem;
            color: #fff;
            background: rgba(15, 23, 42, 0.86);
            border-radius: 999px;
            padding: 0.08rem 0.35rem;
            white-space: nowrap;
        }
    </style>

    <script>
        (function () {
            const map = document.getElementById('poi-map');
            const createX = document.getElementById('create-x');
            const createY = document.getElementById('create-y');
            const editForm = document.getElementById('edit-point-form');
            const editHint = document.getElementById('edit-hint');

            const editName = document.getElementById('edit-name');
            const editType = document.getElementById('edit-type');
            const editX = document.getElementById('edit-x');
            const editY = document.getElementById('edit-y');
            const editMinZoom = document.getElementById('edit-min-zoom');
            const editDescription = document.getElementById('edit-description');
            const editSortOrder = document.getElementById('edit-sort-order');

            let hasSelectedEditPoint = false;

            const setCoordsFromClick = (event) => {
                const rect = map.getBoundingClientRect();
                const x = ((event.clientX - rect.left) / rect.width) * 100;
                const y = ((event.clientY - rect.top) / rect.height) * 100;
                const xFixed = Math.max(0, Math.min(100, x)).toFixed(2);
                const yFixed = Math.max(0, Math.min(100, y)).toFixed(2);

                createX.value = xFixed;
                createY.value = yFixed;

                if (hasSelectedEditPoint) {
                    editX.value = xFixed;
                    editY.value = yFixed;
                }
            };

            const applyPointToEditForm = (dataset) => {
                editForm.action = dataset.updateUrl;
                editName.value = dataset.name ?? '';
                editType.value = dataset.type ?? 'landmark';
                editX.value = dataset.x ?? '';
                editY.value = dataset.y ?? '';
                editMinZoom.value = dataset.minZoom ?? '1.0';
                editDescription.value = dataset.description ?? '';
                editSortOrder.value = dataset.sortOrder ?? '';

                hasSelectedEditPoint = true;
                editForm.classList.remove('d-none');
                editHint.textContent = `Bearbeite Punkt #${dataset.id}: ${dataset.name}`;
            };

            map.addEventListener('click', (event) => {
                const marker = event.target.closest('.poi-marker');
                if (marker) {
                    applyPointToEditForm(marker.dataset);
                    return;
                }

                setCoordsFromClick(event);
            });

            document.querySelectorAll('.js-edit-point').forEach((button) => {
                button.addEventListener('click', () => {
                    applyPointToEditForm(button.dataset);
                    map.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            });
        })();
    </script>
</x-admin.layout>
