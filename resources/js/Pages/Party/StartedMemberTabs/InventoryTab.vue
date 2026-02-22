<script setup>
defineProps({
    character: { type: Object, required: true },
    wallet: { type: Object, default: null },
    tradeBusy: { type: Boolean, default: false },
    incomingPendingTrades: { type: Array, default: () => [] },
    outgoingPendingTrades: { type: Array, default: () => [] },
    activeTrade: { type: Object, default: null },
    tradePartnerCharacter: { type: Object, default: null },
    inventoryActionHint: { type: String, default: '' },
    inventoryItems: { type: Array, default: () => [] },
    inventoryBusy: { type: Boolean, default: false },
    unseenInventoryItemIds: { type: Object, default: () => ({}) },
    isUsableItem: { type: Function, required: true },
    isNoteEditorOpen: { type: Function, required: true },
    noteDraftFor: { type: Function, required: true },
    onOpenTradePicker: { type: Function, required: true },
    onOpenWallet: { type: Function, required: true },
    onAcceptTrade: { type: Function, required: true },
    onOpenActiveTrade: { type: Function, required: true },
    onMarkItemSeen: { type: Function, required: true },
    onToggleNoteEditor: { type: Function, required: true },
    onCloseNoteEditor: { type: Function, required: true },
    onNoteInput: { type: Function, required: true },
    onSaveItemNote: { type: Function, required: true },
    onUseItem: { type: Function, required: true },
});
</script>

<template>
    <div class="card shadow-sm border-0 eldoria-panel">
        <div class="card-body p-4 p-md-5">
            <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Inventar</div>
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                <h3 class="h5 mb-0 eldoria-title">Reiseausrüstung von {{ character.name }}</h3>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="onOpenTradePicker()">
                        Handel starten
                    </button>
                    <div class="wallet-bag-pill" title="Charakterbeutel" role="button" tabindex="0" @click="onOpenWallet()">
                        <span class="wallet-bag-icon" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M10.1 2h3.8l.5 2.2h-4.8L10.1 2zm-3 4.2h9.8c2.7 0 5 2.2 5 5v6.2c0 2.5-2 4.6-4.6 4.6H6.7c-2.5 0-4.6-2-4.6-4.6v-6.2c0-2.8 2.2-5 5-5zm1.2 4.1c0 .7.5 1.2 1.2 1.2h5c.7 0 1.2-.6 1.2-1.2s-.5-1.2-1.2-1.2h-5c-.7 0-1.2.5-1.2 1.2z"/>
                            </svg>
                        </span>
                        <span>{{ wallet?.display ?? '0G 0S 0K' }}</span>
                    </div>
                </div>
            </div>

            <div v-if="incomingPendingTrades.length" class="alert alert-warning py-2 px-3 small mb-3">
                <div
                    v-for="trade in incomingPendingTrades"
                    :key="`incoming-${trade.id}`"
                    class="d-flex justify-content-between align-items-center gap-2 flex-wrap"
                >
                    <span>Handelsanfrage von {{ trade.initiatorName }}</span>
                    <button type="button" class="btn btn-sm btn-primary" :disabled="tradeBusy" @click="onAcceptTrade(trade)">
                        Annehmen
                    </button>
                </div>
            </div>

            <div v-if="outgoingPendingTrades.length" class="alert alert-info py-2 px-3 small mb-3">
                Wartet auf Annahme:
                {{ outgoingPendingTrades.map((trade) => trade.counterpartyName).join(', ') }}
            </div>

            <div v-if="activeTrade" class="alert alert-success py-2 px-3 small mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Aktiver Handel mit {{ tradePartnerCharacter?.user?.name ?? tradePartnerCharacter?.name ?? 'Partner' }}</span>
                <button type="button" class="btn btn-sm btn-outline-success" @click="onOpenActiveTrade()">
                    Handel öffnen
                </button>
            </div>

            <div class="bag-area p-3 p-md-4">
                <div class="bag-mouth mb-3">Jutebeutel</div>
                <div v-if="inventoryActionHint" class="alert alert-warning py-2 px-3 small mb-3">
                    {{ inventoryActionHint }}
                </div>
                <div v-if="inventoryItems.length === 0" class="text-muted small">
                    Dein Beutel ist leer.
                </div>
                <div v-else class="row g-2">
                    <div v-for="item in inventoryItems" :key="item.id" class="col-12 col-md-6">
                        <div class="bag-item d-flex justify-content-between align-items-start gap-2" @mouseenter="onMarkItemSeen(item.id)">
                            <div>
                                <div class="fw-semibold d-flex align-items-center gap-2">
                                    <span>{{ item.name }}</span>
                                    <span v-if="unseenInventoryItemIds[String(item.id)]" class="inventory-unseen-dot" aria-hidden="true"></span>
                                    <span class="note-tooltip-wrap">
                                        <button
                                            type="button"
                                            class="inventory-note-icon"
                                            :class="{ 'has-note': item.notes }"
                                            aria-label="Notiz anzeigen oder bearbeiten"
                                            @click="onToggleNoteEditor(item.id)"
                                        >
                                            i
                                        </button>
                                        <span class="note-tooltip-content">
                                            {{ item.notes || 'Keine Notiz. Klicke auf das Icon zum Bearbeiten.' }}
                                        </span>
                                    </span>
                                </div>
                                <div class="small text-muted">
                                    {{ item.category || 'Allgemein' }}
                                </div>
                                <div v-if="isNoteEditorOpen(item.id)" class="mt-2 d-flex gap-2">
                                    <input
                                        :value="noteDraftFor(item)"
                                        type="text"
                                        class="form-control form-control-sm"
                                        placeholder="Notiz zu diesem Item"
                                        @input="onNoteInput(item.id, $event.target.value)"
                                    >
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="onSaveItemNote(item)">
                                        Notiz speichern
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="onCloseNoteEditor(item.id)">
                                        Schließen
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <span class="px-2 small fw-semibold">{{ item.quantity }}</span>
                                <button
                                    v-if="isUsableItem(item)"
                                    type="button"
                                    class="btn btn-sm btn-outline-success ms-1"
                                    :disabled="inventoryBusy"
                                    @click="onUseItem(item)"
                                >
                                    Nutzen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
