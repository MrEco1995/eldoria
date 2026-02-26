<x-admin.layout title="Admin - Charakterdetail">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Charakter: {{ $character->name }}<br><strong>Geschlecht:</strong> {{ $character->gender }}</h1>
        <a href="{{ route('admin.characters.index') }}" class="btn btn-sm btn-outline-secondary">Zurück</a>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6 mb-3">Basisdaten</h2>
                    @php
                        $hpMax = max(1, (int) ($character->hp_max ?? 1));
                        $hpCurrent = max(0, min($hpMax, (int) ($character->hp_current ?? 0)));
                        $hpPercent = (int) round(($hpCurrent / $hpMax) * 100);
                        $tempHp = max(0, (int) ($character->hp_temp ?? 0));
                        $tempPercent = (int) round((min($tempHp, $hpMax) / $hpMax) * 100);
                    @endphp
                    <div class="row g-2 small">
                        <div class="col-12 col-md-6"><strong>User:</strong> {{ $character->user?->name }} ({{ $character->user?->email }})</div>
                        <div class="col-12 col-md-6"><strong>Party:</strong> {{ $character->party?->name ?? '-' }}</div>
                        <div class="col-12 col-md-6"><strong>Volk:</strong> {{ $character->race }}</div>
                        <div class="col-12 col-md-6"><strong>Klasse:</strong> {{ $character->class_name }}</div>
                        <div class="col-12 col-md-6"><strong>HP:</strong> {{ $hpCurrent }} / {{ $hpMax }}<br>
                            <div class="progress" style="height: 0.65rem;">
                                <div class="progress-bar {{ $hpPercent <= 30 ? 'bg-danger' : ($hpPercent <= 60 ? 'bg-warning' : 'bg-success') }}" role="progressbar" style="width: {{ $hpPercent }}%"></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6"><strong>Temp HP:</strong> +{{ $tempHp }}  <br>
                            <div class="progress mb-1" style="height: 0.45rem;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: {{ $tempPercent }}%"></div>
                            </div>
                        </div>
                        <!--div class="col-12 col-md-4"><strong>Geschlecht:</strong> {{-- $character->gender --}}</div-->
                        <div class="col-12 col-md-4"><strong>Alter:</strong> {{ $character->age }}</div>
                        <div class="col-12 col-md-4"><strong>Groesse / Gewicht:</strong> {{ $character->height_cm }}cm / {{ $character->weight_kg }}kg</div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6 mb-3">Traits</h2>
                    <div class="d-flex flex-wrap gap-1">
                        @forelse (($character->traits ?? []) as $trait)
                            <span class="badge text-bg-light border">{{ $trait }}</span>
                        @empty
                            <span class="text-muted small">Keine Traits.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6 mb-3">Talente</h2>
                    @php
                        $talents = collect($character->talents ?? [])->map(function ($points, $key) use ($talentLabels) {
                            return [
                                'key' => $key,
                                'label' => $talentLabels[$key] ?? $key,
                                'points' => (int) $points,
                            ];
                        })->sortByDesc('points')->values();
                    @endphp
                    <div class="row g-2">
                        @forelse ($talents as $talent)
                            <div class="col-12 col-md-6">
                                <div class="border rounded px-2 py-1 d-flex justify-content-between small bg-white">
                                    <span>{{ $talent['label'] }}</span>
                                    <strong>{{ $talent['points'] }}</strong>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small">Keine Talente vorhanden.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Inventar / Notizen</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Menge</th>
                                    <th>Kategorie</th>
                                    <th>Notizen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($character->inventoryItems as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->category ?? '-' }}</td>
                                        <td>{{ $item->notes ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Inventar leer.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6 mb-3">Charakterbild</h2>
                    @php $image = $character->mediafiles->first(); @endphp
                    @if ($image)
                        <img
                            src="{{ route('media.public', ['path' => $image->path]) }}"
                            alt="Charakterbild {{ $character->name }}"
                            class="img-fluid rounded border"
                        >
                    @else
                        <div class="text-muted small">Kein Bild vorhanden.</div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Wallet / Transaktionen</h2>
                    @php
                        $walletCopper = (int) ($character->wallet?->copper_balance ?? 0);
                        $walletGold = intdiv($walletCopper, 100);
                        $walletSilver = intdiv($walletCopper % 100, 10);
                        $walletKopper = $walletCopper % 10;
                    @endphp
                    <div class="small mb-2">
                        <strong>Saldo:</strong> {{ $walletGold }}G {{ $walletSilver }}S {{ $walletKopper }}K
                    </div>
                    @if (($character->wallet?->transactions?->count() ?? 0) === 0)
                        <div class="text-muted small">Keine Transaktionen.</div>
                    @else
                        <div class="d-flex flex-column gap-2">
                            @foreach ($character->wallet->transactions as $tx)
                                @php
                                    $amountCopper = (int) ($tx->amount_copper ?? 0);
                                    $amountGold = intdiv($amountCopper, 100);
                                    $amountSilver = intdiv($amountCopper % 100, 10);
                                    $amountKopper = $amountCopper % 10;
                                @endphp
                                <div class="border rounded px-2 py-1 bg-white">
                                    <div class="small fw-semibold">{{ $amountGold }}G {{ $amountSilver }}S {{ $amountKopper }}K</div>
                                    <div class="small text-muted">{{ $tx->type }} - {{ $tx->actor?->name ?? 'System' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin.layout>