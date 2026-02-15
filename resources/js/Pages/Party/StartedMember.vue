<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    party: {
        type: Object,
        required: true,
    },
    members: {
        type: Array,
        default: () => [],
    },
});

const membersState = ref([...props.members]);

const onReadyUpdated = (event) => {
    const target = membersState.value.find((member) => member.id === event.userId);
    if (target) {
        target.is_ready = event.isReady;
    }
};

onMounted(() => {
    if (window.Echo) {
        window.Echo.private(`party.${props.party.id}`)
            .listen('.party.ready.updated', onReadyUpdated);
    }
});

onBeforeUnmount(() => {
    if (window.Echo) {
        window.Echo.leave(`party.${props.party.id}`);
    }
});
</script>

<template>
    <Head :title="`${party.name} - Spieler`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="h4 m-0">{{ party.name }} - Spieler</h2>
        </template>

        <div class="magic-stage position-relative overflow-hidden rounded-4 p-4 p-md-5">
            <div class="magic-glow"></div>
            <div class="magic-rings"></div>

            <div class="position-relative">
                <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                    Spielmodus
                </div>
                <h3 class="display-6 fw-semibold mb-2">Bereit für das Abenteuer</h3>
                <p class="text-muted mb-4">
                    Die Party läuft. Warte auf die nächsten Anweisungen.
                </p>

                <div class="card border-0 shadow-sm magic-card">
                    <div class="card-body">
                        <h6 class="text-uppercase small text-muted" style="letter-spacing: 2px;">
                            Teilnehmer
                        </h6>
                        <ul class="list-group list-group-flush">
                            <li
                                v-for="member in membersState"
                                :key="member.id"
                                class="list-group-item px-0 d-flex justify-content-between align-items-center"
                            >
                                <span class="fw-semibold">{{ member.name }}</span>
                                <span
                                    class="badge"
                                    :class="member.is_ready ? 'text-bg-success' : 'text-bg-secondary'"
                                >
                                    {{ member.is_ready ? 'Bereit' : 'Nicht bereit' }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.magic-stage {
    background: radial-gradient(140% 120% at 80% 0%, rgba(107, 92, 255, 0.22), rgba(12, 14, 24, 0.8)),
        radial-gradient(120% 120% at 0% 80%, rgba(77, 208, 225, 0.18), rgba(12, 14, 24, 0));
    border: 1px solid rgba(107, 92, 255, 0.18);
    color: #e9ebff;
}

.magic-glow {
    position: absolute;
    inset: -40% auto auto 65%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(77, 208, 225, 0.35), rgba(77, 208, 225, 0));
    filter: blur(8px);
}

.magic-rings {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(107, 92, 255, 0.12) 1px, transparent 1px);
    background-size: 28px 28px;
    opacity: 0.35;
}

.magic-card {
    background: rgba(16, 19, 33, 0.82);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(107, 92, 255, 0.18);
    color: #e9ebff;
}

.magic-card .list-group-item {
    background: transparent;
    color: inherit;
}

.magic-stage .text-muted {
    color: rgba(219, 224, 255, 0.7) !important;
}
</style>

