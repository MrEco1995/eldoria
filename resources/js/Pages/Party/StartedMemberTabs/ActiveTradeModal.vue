<script setup>
defineProps({
    isOpen: { type: Boolean, default: false },
    activeTrade: { type: Object, default: null },
    character: { type: Object, required: true },
    wallet: { type: Object, default: null },
    inventoryItems: { type: Array, default: () => [] },
    tradePartnerCharacter: { type: Object, default: null },
    onClose: { type: Function, required: true },
});
</script>

<template>
    <div v-if="isOpen && activeTrade" class="wallet-modal-backdrop" @click.self="onClose()">
        <div class="trade-modal-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="h6 mb-0">Aktiver Handel</h4>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="onClose()">
                    Schließen
                </button>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="trade-column p-3 h-100">
                        <div class="fw-semibold mb-2">Du: {{ character.name }}</div>
                        <div class="small text-muted mb-2">Wallet: {{ wallet?.display ?? '0G 0S 0K' }}</div>
                        <div class="small text-uppercase text-muted mb-2">Dein Inventar</div>
                        <div v-if="inventoryItems.length === 0" class="small text-muted">Leer</div>
                        <ul v-else class="small mb-0 ps-3">
                            <li v-for="item in inventoryItems" :key="`self-trade-${item.id}`">
                                {{ item.name }} x{{ item.quantity }}
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="trade-column p-3 h-100">
                        <div class="fw-semibold mb-2">
                            {{ tradePartnerCharacter?.name ?? 'Handelspartner' }}
                        </div>
                        <div class="small text-uppercase text-muted mb-2">Inventar Partner</div>
                        <div v-if="!(tradePartnerCharacter?.inventoryItems ?? []).length" class="small text-muted">Leer</div>
                        <ul v-else class="small mb-0 ps-3">
                            <li v-for="item in (tradePartnerCharacter?.inventoryItems ?? [])" :key="`partner-trade-${item.id}`">
                                {{ item.name }} x{{ item.quantity }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
