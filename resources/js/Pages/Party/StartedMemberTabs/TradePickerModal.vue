<script setup>
defineProps({
    isOpen: { type: Boolean, default: false },
    availableTradeTargets: { type: Array, default: () => [] },
    selectedTradeTargetCharacterId: { type: [Number, null], default: null },
    tradeBusy: { type: Boolean, default: false },
    onClose: { type: Function, required: true },
    onSelectTarget: { type: Function, required: true },
    onStartTrade: { type: Function, required: true },
});
</script>

<template>
    <div v-if="isOpen" class="wallet-modal-backdrop" @click.self="onClose()">
        <div class="wallet-modal-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="h6 mb-0">Handel starten</h4>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="onClose()">
                    Schließen
                </button>
            </div>
            <div v-if="!availableTradeTargets.length" class="text-muted small">
                Kein Handelspartner verfügbar.
            </div>
            <div v-else class="d-flex flex-column gap-3">
                <select :value="selectedTradeTargetCharacterId" class="form-select" @change="onSelectTarget($event.target.value)">
                    <option v-for="entry in availableTradeTargets" :key="entry.id" :value="entry.id">
                        {{ entry.user?.name ?? entry.name }}
                    </option>
                </select>
                <button type="button" class="btn btn-primary" :disabled="tradeBusy" @click="onStartTrade()">
                    Anfrage senden
                </button>
            </div>
        </div>
    </div>
</template>
