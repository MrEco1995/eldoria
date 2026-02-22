<script setup>
defineProps({
    npcTradeMerchants: { type: Array, default: () => [] },
    selectedNpcTradeOfferId: { type: [Number, String, null], default: null },
    currentCharacterId: { type: Number, required: true },
    npcTradeBusy: { type: Boolean, default: false },
    onSelectMerchant: { type: Function, required: true },
    onClaimMerchant: { type: Function, required: true },
    onOpenMerchant: { type: Function, required: true },
});
</script>

<template>
    <div class="card shadow-sm border-0 eldoria-panel">
        <div class="card-body p-4 p-md-5">
            <div class="text-uppercase small text-muted mb-2 eldoria-kicker">NPC Handel</div>
            <div v-if="!npcTradeMerchants.length" class="text-muted small">Kein NPC konfiguriert.</div>
            <div v-else class="d-flex flex-column gap-2">
                <div
                    v-for="merchant in npcTradeMerchants"
                    :key="`member-merchant-tab-${merchant.id}`"
                    class="alert py-2 px-3 small mb-0"
                    :class="Number(selectedNpcTradeOfferId) === Number(merchant.id) ? 'alert-primary' : (merchant.isOpen ? 'alert-success' : 'alert-secondary')"
                >
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <span>
                            <strong>{{ merchant.name || `Händler ${merchant.id}` }}</strong>
                            <span class="text-muted"> · {{ merchant.isOpen ? 'freigegeben' : 'nicht freigegeben' }}</span>
                            <span v-if="merchant.activePartyCharacterId && Number(merchant.activePartyCharacterId) !== currentCharacterId" class="text-muted">
                                · belegt durch {{ merchant.activeCharacterName || 'anderen Spieler' }}
                            </span>
                        </span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="onSelectMerchant(merchant.id)">
                                Auswählen
                            </button>
                            <button
                                v-if="merchant.isOpen && Number(merchant.activePartyCharacterId || 0) === 0"
                                type="button"
                                class="btn btn-sm btn-primary"
                                :disabled="npcTradeBusy"
                                @click="onClaimMerchant(merchant.id)"
                            >
                                Mit NPC handeln
                            </button>
                            <button
                                v-if="Number(merchant.activePartyCharacterId || 0) === currentCharacterId"
                                type="button"
                                class="btn btn-sm btn-outline-success"
                                @click="onOpenMerchant(merchant.id)"
                            >
                                NPC Handel öffnen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
