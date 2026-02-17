<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    partyId: {
        type: Number,
        required: true,
    },
});

const lastRoll = ref(null);
const rollLog = ref([]);
const isRolling = ref(false);
const activeDie = ref(null);
const rollError = ref('');
const partyChannel = ref(null);

const toTime = (iso = null) => {
    const date = iso ? new Date(iso) : new Date();
    return date.toLocaleTimeString('de-DE', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

const appendRoll = (payload) => {
    const entry = {
        userName: payload.userName,
        die: payload.die,
        result: payload.result,
        at: toTime(payload.rolledAt),
    };

    lastRoll.value = {
        label: payload.die,
        value: payload.result,
        at: entry.at,
    };

    rollLog.value.unshift(entry);
    if (rollLog.value.length > 12) {
        rollLog.value.length = 12;
    }
};

const onPartyRollCreated = (event) => {
    if (Number(event.partyId) !== Number(props.partyId)) {
        return;
    }

    appendRoll(event);
};

const roll = (sides, dieLabel) => {
    if (isRolling.value) {
        return;
    }

    rollError.value = '';
    isRolling.value = true;
    activeDie.value = dieLabel;

    const value = Math.floor(Math.random() * sides) + 1;

    setTimeout(async () => {
        try {
            await window.axios.post(`/parties/${props.partyId}/rolls`, {
                die: dieLabel,
                result: value,
            });
        } catch (error) {
            appendRoll({
                userName: 'Du',
                die: dieLabel,
                result: value,
                rolledAt: new Date().toISOString(),
            });
            if (error?.response?.status === 419) {
                rollError.value = error?.response?.data?.message
                    ?? 'Sitzung abgelaufen. Bitte Seite neu laden und erneut versuchen.';
            } else {
                rollError.value = 'Wurf konnte nicht live uebertragen werden.';
            }
        } finally {
            isRolling.value = false;
            activeDie.value = null;
        }
    }, 700);
};

onMounted(() => {
    if (!window.Echo) {
        return;
    }

    partyChannel.value = window.Echo.private(`party.${props.partyId}`)
        .listen('.party.roll.created', onPartyRollCreated);
});

onBeforeUnmount(() => {
    if (partyChannel.value) {
        partyChannel.value.stopListening('.party.roll.created');
    }
});
</script>

<template>
    <div
        class="position-fixed bottom-0 end-0 p-3"
        style="z-index: 1080; width: min(360px, calc(100vw - 1.5rem));"
    >
        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                    Würfeln
                </div>
                <h5 class="card-title mb-3">Schneller Würfelwurf</h5>

                <div class="d-flex gap-2 flex-wrap">
                    <button
                        type="button"
                        class="btn btn-outline-primary d-inline-flex align-items-center gap-2"
                        :class="{ 'dice-rolling': isRolling && activeDie === 'W20' }"
                        @click="roll(20, 'W20')"
                        :disabled="isRolling"
                        title="W20 wuerfeln"
                        aria-label="W20 wuerfeln"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 2 3 8l3 11h12l3-11-9-6Z" stroke="currentColor" stroke-width="1.8" />
                            <path d="M12 2v17M3 8h18M8 19l4-6 4 6" stroke="currentColor" stroke-width="1.4" />
                        </svg>
                        <span>Wurf</span>
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-secondary d-inline-flex align-items-center gap-2"
                        :class="{ 'dice-rolling': isRolling && activeDie === 'W6' }"
                        @click="roll(6, 'W6')"
                        :disabled="isRolling"
                        title="W6 wuerfeln"
                        aria-label="W6 wuerfeln"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="3.5" stroke="currentColor" stroke-width="1.8" />
                            <circle cx="8" cy="8" r="1.3" fill="currentColor" />
                            <circle cx="16" cy="8" r="1.3" fill="currentColor" />
                            <circle cx="8" cy="12" r="1.3" fill="currentColor" />
                            <circle cx="16" cy="12" r="1.3" fill="currentColor" />
                            <circle cx="8" cy="16" r="1.3" fill="currentColor" />
                            <circle cx="16" cy="16" r="1.3" fill="currentColor" />
                        </svg>
                        <span>Wurf</span>
                    </button>
                </div>

                <div
                    v-if="lastRoll"
                    class="alert alert-info border-0 mt-3 mb-2"
                    :class="{ 'result-pop': !isRolling }"
                >
                    <div class="small text-muted mb-1">Letzter Wurf ({{ lastRoll.at }})</div>
                    <div class="fw-semibold">{{ lastRoll.label }}: {{ lastRoll.value }}</div>
                </div>
                <div v-else class="small text-muted mt-3 mb-2">
                    {{ isRolling ? 'Wuerfel rollt...' : 'Noch kein Wurf.' }}
                </div>

                <div v-if="rollError" class="small text-danger mb-2">
                    {{ rollError }}
                </div>

                <div v-if="rollLog.length" class="border rounded p-2 bg-light-subtle" style="max-height: 180px; overflow: auto;">
                    <div class="small text-muted mb-1">Wurf-Log</div>
                    <div
                        v-for="(item, index) in rollLog"
                        :key="`${item.at}-${item.userName}-${index}`"
                        class="small d-flex justify-content-between gap-2"
                    >
                        <span>{{ item.userName }}: {{ item.die }} = {{ item.result }}</span>
                        <span class="text-muted">{{ item.at }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dice-rolling {
    animation: diceShake 0.7s ease-in-out;
}

.result-pop {
    animation: resultPop 0.25s ease-out;
}

@keyframes diceShake {
    0% { transform: translateX(0) rotate(0deg); }
    20% { transform: translateX(-2px) rotate(-8deg); }
    40% { transform: translateX(3px) rotate(10deg); }
    60% { transform: translateX(-3px) rotate(-10deg); }
    80% { transform: translateX(2px) rotate(8deg); }
    100% { transform: translateX(0) rotate(0deg); }
}

@keyframes resultPop {
    0% { transform: scale(0.96); opacity: 0.75; }
    100% { transform: scale(1); opacity: 1; }
}
</style>
