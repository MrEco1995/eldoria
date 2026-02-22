<script setup>
defineProps({
    journalItem: { type: Object, default: null },
    noteDraft: { type: String, default: '' },
    saveBusy: { type: Boolean, default: false },
    onInput: { type: Function, required: true },
    onSave: { type: Function, required: true },
});
</script>

<template>
    <div class="card shadow-sm border-0 eldoria-panel">
        <div class="card-body p-4 p-md-5">
            <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Notizen</div>
            <h3 class="h5 mb-2 eldoria-title">Reisetagebuch</h3>
            <p class="text-muted small mb-3">
                Deine Notizen sind an dein Item <strong>Reisetagebuch</strong> gebunden.
            </p>

            <div v-if="!journalItem" class="text-muted small">
                Kein Reisetagebuch im Inventar.
            </div>
            <div v-else class="d-flex flex-column gap-2">
                <textarea
                    class="form-control"
                    rows="10"
                    :value="noteDraft"
                    placeholder="Schreibe hier deine Abenteuer-Notizen..."
                    @input="onInput($event.target.value)"
                ></textarea>
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-primary" :disabled="saveBusy" @click="onSave()">
                        Notizen speichern
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
