<script setup>
defineProps({
    isOpen: { type: Boolean, default: false },
    npcTradeState: { type: Object, default: null },
    npcTradeActiveBySelf: { type: Boolean, default: false },
    character: { type: Object, required: true },
    wallet: { type: Object, default: null },
    inventoryItems: { type: Array, default: () => [] },
    npcTradeBusy: { type: Boolean, default: false },
    npcSellableInventoryItems: { type: Array, default: () => [] },
    npcSellForm: { type: Object, required: true },
    npcSellAmountCopper: { type: Number, default: 0 },
    ownLastRejectedNpcSellOfferByInventoryItemId: { type: Object, default: () => ({}) },
    ownPendingNpcSellOffers: { type: Array, default: () => [] },
    formatCopper: { type: Function, required: true },
    npcBuyQuantityFor: { type: Function, required: true },
    hasEnoughForNpcItem: { type: Function, required: true },
    onClose: { type: Function, required: true },
    onReleaseNpcTrade: { type: Function, required: true },
    onSellFormInput: { type: Function, required: true },
    onSubmitNpcSellOffer: { type: Function, required: true },
    onNpcBuyQuantityInput: { type: Function, required: true },
    onBuyNpcItem: { type: Function, required: true },
});
</script>

<template>
    <div v-if="isOpen && npcTradeState && npcTradeActiveBySelf" class="wallet-modal-backdrop" @click.self="onClose()">
        <div class="trade-modal-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="h6 mb-0">NPC Handel: {{ npcTradeState.name }}</h4>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-warning" :disabled="npcTradeBusy" @click="onReleaseNpcTrade()">
                        Verlassen
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="onClose()">
                        Schließen
                    </button>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="trade-column p-3 h-100">
                        <div class="fw-semibold mb-2">Du: {{ character.name }}</div>
                        <div class="small text-muted mb-2">Wallet: {{ wallet?.display ?? '0G 0S 0K' }}</div>
                        <div class="small text-uppercase text-muted mb-2">Dein Inventar</div>
                        <div v-if="inventoryItems.length === 0" class="small text-muted">Leer</div>
                        <ul v-else class="small mb-0 ps-3">
                            <li v-for="item in inventoryItems" :key="`self-npc-trade-${item.id}`">
                                {{ item.name }} x{{ item.quantity }}
                            </li>
                        </ul>
                        <hr class="my-3">
                        <div class="small text-uppercase text-muted mb-2">Item an NPC verkaufen</div>
                        <div v-if="!npcSellableInventoryItems.length" class="small text-muted">Kein Item zum Verkaufen.</div>
                        <div v-else class="row g-2">
                            <div class="col-12">
                                <select :value="npcSellForm.inventoryItemId" class="form-select form-select-sm" @change="onSellFormInput('inventoryItemId', Number($event.target.value))">
                                    <option v-for="entry in npcSellableInventoryItems" :key="`npc-sell-item-${entry.id}`" :value="entry.id">
                                        {{ entry.name }} x{{ entry.quantity }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-4">
                                <input :value="npcSellForm.quantity" type="number" min="1" class="form-control form-control-sm" placeholder="Menge" @input="onSellFormInput('quantity', Number($event.target.value || 1))">
                            </div>
                            <div class="col-8 d-flex gap-1">
                                <input :value="npcSellForm.amountGold" type="number" min="0" class="form-control form-control-sm" placeholder="G" @input="onSellFormInput('amountGold', Number($event.target.value || 0))">
                                <input :value="npcSellForm.amountSilver" type="number" min="0" class="form-control form-control-sm" placeholder="S" @input="onSellFormInput('amountSilver', Number($event.target.value || 0))">
                                <input :value="npcSellForm.amountCopper" type="number" min="0" class="form-control form-control-sm" placeholder="K" @input="onSellFormInput('amountCopper', Number($event.target.value || 0))">
                            </div>
                            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span class="small text-muted">Angebot: {{ formatCopper(npcSellAmountCopper) }}</span>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    :disabled="npcTradeBusy || npcSellAmountCopper <= 0 || !npcSellForm.inventoryItemId"
                                    @click="onSubmitNpcSellOffer()"
                                >
                                    Verkauf anbieten
                                </button>
                            </div>
                            <div
                                v-if="ownLastRejectedNpcSellOfferByInventoryItemId[String(npcSellForm.inventoryItemId)]"
                                class="col-12 small text-warning"
                            >
                                Letztes Angebot wurde abgelehnt:
                                {{ ownLastRejectedNpcSellOfferByInventoryItemId[String(npcSellForm.inventoryItemId)].amountDisplay }}.
                                Neuer Wert muss darunter liegen.
                            </div>
                        </div>
                        <div class="small text-uppercase text-muted mb-2 mt-3">Offene Verkaufsangebote</div>
                        <div v-if="!ownPendingNpcSellOffers.length" class="small text-muted">Keine offenen Angebote.</div>
                        <ul v-else class="small mb-0 ps-3">
                            <li v-for="offer in ownPendingNpcSellOffers" :key="`own-pending-sell-${offer.id}`">
                                {{ offer.itemName }} x{{ offer.quantity }} · {{ offer.amountDisplay }} (wartet auf Spielleiter)
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="trade-column p-3 h-100">
                        <div class="fw-semibold mb-2">{{ npcTradeState.name }}</div>
                        <div class="small text-uppercase text-muted mb-2">NPC Inventar</div>
                        <div v-if="!(npcTradeState.items ?? []).length" class="small text-muted">Leer</div>
                        <div v-else class="d-flex flex-column gap-2">
                            <div
                                v-for="item in (npcTradeState.items ?? [])"
                                :key="`npc-item-${item.id}`"
                                class="d-flex justify-content-between align-items-center gap-2 border rounded p-2 bg-white"
                            >
                                <div class="small">
                                    <div class="fw-semibold">{{ item.name }} x{{ item.quantity }} · {{ item.priceDisplay }}</div>
                                    <div class="text-muted">
                                        {{ item.category || 'Allgemein' }}<span v-if="item.notes"> · {{ item.notes }}</span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <input
                                        :value="npcBuyQuantityFor(item.id)"
                                        type="number"
                                        min="1"
                                        :max="item.quantity"
                                        class="form-control form-control-sm"
                                        style="width: 88px;"
                                        @input="onNpcBuyQuantityInput(item.id, Number($event.target.value || 1))"
                                    >
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-primary"
                                        :disabled="npcTradeBusy || npcBuyQuantityFor(item.id) > item.quantity || !hasEnoughForNpcItem(item)"
                                        @click="onBuyNpcItem(item)"
                                    >
                                        Kaufen
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
