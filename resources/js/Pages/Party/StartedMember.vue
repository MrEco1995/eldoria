<script setup>
import DiceRoller from '@/Components/DiceRoller.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    party: { type: Object, required: true },
    character: { type: Object, required: true },
    talentDefinitions: { type: Array, default: () => [] },
    talentRequests: { type: Array, default: () => [] },
});

const requestState = ref([...(props.talentRequests ?? [])]);
const requestInputs = ref({});

watch(() => props.talentRequests, (next) => {
    requestState.value = [...(next ?? [])];
}, { immediate: true });

const getTalentValue = (key) => Number(props.character?.talents?.[key] ?? 0);

const talentGroups = computed(() => {
    const groups = new Map();
    (props.talentDefinitions ?? []).forEach((talent) => {
        const category = talent.category || 'Sonstige Talente';
        if (!groups.has(category)) groups.set(category, []);
        groups.get(category).push(talent);
    });
    return Array.from(groups.entries()).map(([category, items]) => ({ category, items }));
});

const myRequests = computed(() => {
    return requestState.value
        .filter((request) => Number(request.targetUserId) === Number(props.character.user_id))
        .sort((a, b) => Number(b.id) - Number(a.id));
});

const upsertRequest = (payload) => {
    const idx = requestState.value.findIndex((item) => Number(item.id) === Number(payload.id));
    if (idx >= 0) {
        requestState.value[idx] = payload;
    } else {
        requestState.value.unshift(payload);
    }
};

const onRequestCreated = (event) => {
    if (Number(event.partyId) !== Number(props.party.id) || !event.request) return;
    if (Number(event.request.targetUserId) !== Number(props.character.user_id)) return;
    upsertRequest(event.request);
};

const onRequestConfirmed = (event) => {
    if (Number(event.partyId) !== Number(props.party.id) || !event.request) return;
    upsertRequest(event.request);
};

const ensureRequestInput = (request) => {
    if (!requestInputs.value[request.id]) {
        requestInputs.value[request.id] = {
            rolledTalentKey: request.talents?.[0]?.key ?? '',
            rolledValue: '',
        };
    }
    return requestInputs.value[request.id];
};

const modifierLabel = (request) => {
    if (!request || request.modifierType === 'none' || !request.modifierPoints) return 'Normal';
    return request.modifierType === 'easy'
        ? `Erleichtert +${request.modifierPoints}`
        : `Erschwert -${request.modifierPoints}`;
};

const rollForRequest = (request) => {
    const input = ensureRequestInput(request);
    input.rolledValue = Math.floor(Math.random() * 20) + 1;
};

const confirmRequest = async (request) => {
    const input = ensureRequestInput(request);
    if (!input.rolledTalentKey || !input.rolledValue) return;

    try {
        const response = await window.axios.post(route('parties.talent-requests.confirm', {
            party: props.party.id,
            talentRequest: request.id,
        }), {
            rolled_talent_key: input.rolledTalentKey,
            rolled_value: Number(input.rolledValue),
        });
        if (response?.data?.request) {
            upsertRequest(response.data.request);
        }
    } catch {
        // ignore; flash handles errors
    }
};

onMounted(() => {
    if (!window.Echo) return;
    window.Echo.private(`party.${props.party.id}`)
        .listen('.party.talent-request.created', onRequestCreated)
        .listen('.party.talent-request.confirmed', onRequestConfirmed);
});

onBeforeUnmount(() => {
    if (window.Echo) {
        window.Echo.leave(`party.${props.party.id}`);
    }
});
</script>

<template>
    <Head :title="`${party.name} - Charakter`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="h4 m-0">{{ party.name }} - Dein Charakter</h2>
        </template>

        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">Charakterbogen</div>
                        <h3 class="h4 mb-3">{{ character.name }}</h3>

                        <div class="text-muted mb-3">{{ character.race }} · {{ character.class_name }} · {{ character.gender }}</div>
                        <div class="text-muted mb-4">{{ character.age }} Jahre · {{ character.height_cm }} cm · {{ character.weight_kg }} kg</div>

                        <div class="mb-4">
                            <div class="small text-uppercase text-muted mb-2" style="letter-spacing: 1px;">Traits</div>
                            <div class="d-flex flex-wrap gap-2">
                                <span v-for="trait in character.traits" :key="trait" class="badge text-bg-light border">{{ trait }}</span>
                            </div>
                        </div>

                        <div>
                            <div class="small text-uppercase text-muted mb-2" style="letter-spacing: 1px;">Talente</div>
                            <div class="row g-3">
                                <div v-for="group in talentGroups" :key="group.category" class="col-12 col-lg-6">
                                    <div class="border rounded p-3 bg-light-subtle h-100">
                                        <div class="fw-semibold small mb-2">{{ group.category }}</div>
                                        <div
                                            v-for="talent in group.items"
                                            :key="talent.key"
                                            class="d-flex justify-content-between border rounded px-3 py-2 bg-white mb-2"
                                        >
                                            <span>{{ talent.label }}</span>
                                            <strong>{{ getTalentValue(talent.key) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">Anforderungen</div>
                        <div v-if="myRequests.length === 0" class="text-muted small">Keine Talentanforderungen vom Spielleiter.</div>
                        <div v-else class="d-flex flex-column gap-2">
                            <div v-for="request in myRequests" :key="request.id" class="border rounded p-3 bg-light-subtle">
                                <div class="small mb-2">
                                    <strong>{{ request.ownerUserName }}</strong> fordert:
                                    {{ request.talents.map((t) => t.label).join(', ') }}
                                    <span class="text-muted"> · {{ modifierLabel(request) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge" :class="request.status === 'confirmed' ? (request.isSuccess ? 'text-bg-success' : 'text-bg-danger') : 'text-bg-warning'">
                                        {{ request.status === 'confirmed' ? (request.isSuccess ? 'Erfolg' : 'Fehlschlag') : 'Offen' }}
                                    </span>
                                    <span v-if="request.status === 'confirmed'" class="small">
                                        {{ request.rolledValue }} auf {{ request.rolledTalentKey }} (Zielwert: {{ request.targetValue }})
                                    </span>
                                </div>
                                <div v-if="request.status !== 'confirmed'" class="d-flex gap-2 align-items-center flex-wrap">
                                    <select
                                        class="form-select form-select-sm"
                                        style="max-width: 220px;"
                                        :value="ensureRequestInput(request).rolledTalentKey"
                                        @change="ensureRequestInput(request).rolledTalentKey = $event.target.value"
                                    >
                                        <option v-for="talent in request.talents" :key="talent.key" :value="talent.key">
                                            {{ talent.label }}
                                        </option>
                                    </select>
                                    <input
                                        type="number"
                                        min="1"
                                        max="20"
                                        class="form-control form-control-sm"
                                        style="max-width: 90px;"
                                        :value="ensureRequestInput(request).rolledValue"
                                        @input="ensureRequestInput(request).rolledValue = Number($event.target.value || '')"
                                    >
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="rollForRequest(request)">
                                        W20 würfeln
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" @click="confirmRequest(request)">
                                        Wurf abschicken
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-3">
                        <img v-if="character.image_url" :src="character.image_url" :alt="`Charakterbild von ${character.name}`" class="img-fluid rounded border">
                        <div v-else class="text-muted small">Kein Charakterbild verfügbar.</div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <DiceRoller :party-id="party.id" />
</template>
