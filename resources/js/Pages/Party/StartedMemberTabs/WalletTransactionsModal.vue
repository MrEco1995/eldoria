<script setup>
defineProps({
    isOpen: { type: Boolean, default: false },
    walletTransactions: { type: Array, default: () => [] },
    walletTypeLabels: { type: Object, default: () => ({}) },
    walletTypeBadges: { type: Object, default: () => ({}) },
    normalizeWalletType: { type: Function, required: true },
    onClose: { type: Function, required: true },
});
</script>

<template>
    <div v-if="isOpen" class="wallet-modal-backdrop" @click.self="onClose()">
        <div class="wallet-modal-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="h6 mb-0">Wallet-Transaktionen</h4>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="onClose()">
                    Schließen
                </button>
            </div>
            <div v-if="walletTransactions.length === 0" class="text-muted small">
                Noch keine Wallet-Transaktionen.
            </div>
            <div v-else class="d-flex flex-column gap-2">
                <div
                    v-for="tx in walletTransactions"
                    :key="tx.id"
                    class="wallet-transaction-row d-flex justify-content-between align-items-start gap-2"
                >
                    <div>
                        <div class="small fw-semibold">
                            {{ walletTypeLabels[normalizeWalletType(tx.type)] || tx.type }} · {{ tx.amountDisplay }}
                        </div>
                        <div class="small text-muted">
                            {{ tx.actorUserName || 'System' }}<span v-if="tx.note"> · {{ tx.note }}</span>
                        </div>
                    </div>
                    <span class="badge" :class="walletTypeBadges[normalizeWalletType(tx.type)] || 'text-bg-secondary'">
                        {{ normalizeWalletType(tx.type).toUpperCase() }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
