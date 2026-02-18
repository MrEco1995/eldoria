<script setup>
import DiceRoller from '@/Components/DiceRoller.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    party: { type: Object, required: true },
    characters: { type: Array, default: () => [] },
    talentDefinitions: { type: Array, default: () => [] },
    talentRequests: { type: Array, default: () => [] },
});

const page = usePage();
const ownerName = computed(() => page.props.auth?.user?.name ?? 'Spielleiter');

const playerCharacters = computed(() => props.characters ?? []);
const activeCharacterId = ref(playerCharacters.value[0]?.id ?? null);
const selectedTalentsByUser = ref({});
const modifierByUser = ref({});
const requestState = ref([...props.talentRequests]);

watch(() => props.talentRequests, (next) => {
    requestState.value = [...(next ?? [])];
}, { immediate: true });

const activeCharacter = computed(() => {
    return playerCharacters.value.find((entry) => entry.id === activeCharacterId.value) ?? null;
});

const getTalentValue = (key) => Number(activeCharacter.value?.talents?.[key] ?? 0);

const talentGroups = computed(() => {
    const groups = new Map();
    (props.talentDefinitions ?? []).forEach((talent) => {
        const category = talent.category || 'Sonstige Talente';
        if (!groups.has(category)) groups.set(category, []);
        groups.get(category).push(talent);
    });
    return Array.from(groups.entries()).map(([category, items]) => ({ category, items }));
});

const activeRequests = computed(() => {
    if (!activeCharacter.value) return [];
    return requestState.value
        .filter((request) => Number(request.targetUserId) === Number(activeCharacter.value.user_id))
        .sort((a, b) => Number(b.id) - Number(a.id));
});

const isTalentSelected = (userId, talentKey) => {
    return (selectedTalentsByUser.value[userId] ?? []).includes(talentKey);
};

const toggleTalentSelection = (userId, talentKey) => {
    const current = [...(selectedTalentsByUser.value[userId] ?? [])];
    const index = current.indexOf(talentKey);
    if (index >= 0) {
        current.splice(index, 1);
    } else {
        current.push(talentKey);
    }
    selectedTalentsByUser.value[userId] = current;
};

const ensureModifierState = (userId) => {
    if (!modifierByUser.value[userId]) {
        modifierByUser.value[userId] = {
            type: 'none',
            points: 0,
        };
    }
    return modifierByUser.value[userId];
};

const sendTalentRequest = async () => {
    if (!activeCharacter.value) return;

    const targetUserId = activeCharacter.value.user_id;
    const talents = selectedTalentsByUser.value[targetUserId] ?? [];
    if (!talents.length) return;
    const modifier = ensureModifierState(targetUserId);

    try {
        await window.axios.post(route('parties.talent-requests.store', props.party.id), {
            target_user_id: targetUserId,
            talents,
            modifier_type: modifier.type,
            modifier_points: modifier.type === 'none' ? 0 : Number(modifier.points || 0),
        });

        const optimistic = {
            id: Date.now(),
            partyId: props.party.id,
            ownerUserId: page.props.auth?.user?.id,
            ownerUserName: ownerName.value,
            targetUserId,
            targetUserName: activeCharacter.value.user?.name ?? 'Spieler',
            talents: talents.map((key) => {
                const definition = (props.talentDefinitions ?? []).find((entry) => entry.key === key);
                return { key, label: definition?.label ?? key };
            }),
            modifierType: modifier.type,
            modifierPoints: modifier.type === 'none' ? 0 : Number(modifier.points || 0),
            status: 'pending',
            rolledTalentKey: null,
            rolledValue: null,
            targetValue: null,
            isSuccess: null,
            createdAt: new Date().toISOString(),
            confirmedAt: null,
        };
        requestState.value.unshift(optimistic);
        selectedTalentsByUser.value[targetUserId] = [];
    } catch {
        // ignore; flash message handled server-side if available
    }
};

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
    upsertRequest(event.request);
};

const onRequestConfirmed = (event) => {
    if (Number(event.partyId) !== Number(props.party.id) || !event.request) return;
    upsertRequest(event.request);
};

const modifierLabel = (request) => {
    if (!request || request.modifierType === 'none' || !request.modifierPoints) {
        return 'Normal';
    }

    if (request.modifierType === 'easy') {
        return `Erleichtert +${request.modifierPoints}`;
    }

    return `Erschwert -${request.modifierPoints}`;
};

const talentResultClass = (talent) => {
    if (!talent?.rolledAt) return 'text-bg-warning';
    return talent.isSuccess ? 'text-bg-success' : 'text-bg-danger';
};
const talentResultText = (talent) => {
    if (!talent?.rolledAt) return 'Offen';
    return talent.isSuccess ? 'Erfolg' : 'Fehlschlag';
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
    <Head :title="`${party.name} - Spielleiter`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="h4 m-0">{{ party.name }} - Spielleiter</h2>
        </template>

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <div class="text-uppercase small text-muted" style="letter-spacing: 2px;">Spielleiter Ansicht</div>
                <div class="text-muted">Talente auswählen und live an Spieler anfordern.</div>
            </div>
            <Link :href="route('parties.end', party.id)" method="post" as="button" class="btn btn-outline-danger">
                Party beenden
            </Link>
        </div>

        <div v-if="playerCharacters.length === 0" class="alert alert-warning border-0">Keine Charaktere gefunden.</div>

        <div v-else class="card shadow-sm border-0">
            <div class="card-body p-3 p-md-4">
                <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
                    <li v-for="entry in playerCharacters" :key="entry.id" class="nav-item" role="presentation">
                        <button type="button" class="nav-link" :class="{ active: activeCharacterId === entry.id }" @click="activeCharacterId = entry.id">
                            {{ entry.user.name }}
                        </button>
                    </li>
                </ul>

                <div v-if="activeCharacter" class="row g-4">
                    <div class="col-12 col-xl-8">
                        <h4 class="h5 mb-1">{{ activeCharacter.name }}</h4>
                        <div class="text-muted mb-2">{{ activeCharacter.race }} · {{ activeCharacter.class_name }} · {{ activeCharacter.gender }}</div>
                        <div class="text-muted mb-3">{{ activeCharacter.age }} Jahre · {{ activeCharacter.height_cm }} cm · {{ activeCharacter.weight_kg }} kg</div>

                        <div>
                            <div class="small text-uppercase text-muted mb-2" style="letter-spacing: 1px;">Talente anfordern</div>
                            <div class="row g-3">
                                <div v-for="group in talentGroups" :key="group.category" class="col-12 col-lg-6">
                                    <div class="border rounded p-3 bg-light-subtle h-100">
                                        <div class="fw-semibold small mb-2">{{ group.category }}</div>
                                        <div
                                            v-for="talent in group.items"
                                            :key="talent.key"
                                            class="d-flex justify-content-between align-items-center border rounded px-3 py-2 bg-white mb-2"
                                        >
                                            <label class="d-flex align-items-center gap-2 m-0">
                                                <input
                                                    type="checkbox"
                                                    :checked="isTalentSelected(activeCharacter.user_id, talent.key)"
                                                    @change="toggleTalentSelection(activeCharacter.user_id, talent.key)"
                                                >
                                                <span>{{ talent.label }}</span>
                                            </label>
                                            <strong>{{ getTalentValue(talent.key) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 d-flex gap-2 align-items-center flex-wrap">
                                <select
                                    class="form-select form-select-sm"
                                    style="max-width: 170px;"
                                    :value="ensureModifierState(activeCharacter.user_id).type"
                                    @change="ensureModifierState(activeCharacter.user_id).type = $event.target.value"
                                >
                                    <option value="none">Normal</option>
                                    <option value="easy">Erleichtert</option>
                                    <option value="hard">Erschwert</option>
                                </select>
                                <input
                                    type="number"
                                    min="0"
                                    max="5"
                                    class="form-control form-control-sm"
                                    style="max-width: 90px;"
                                    :disabled="ensureModifierState(activeCharacter.user_id).type === 'none'"
                                    :value="ensureModifierState(activeCharacter.user_id).points"
                                    @input="ensureModifierState(activeCharacter.user_id).points = Math.min(5, Math.max(0, Number($event.target.value || 0)))"
                                >
                                <button type="button" class="btn btn-primary" @click="sendTalentRequest">
                                    Talentanforderung senden
                                </button>
                                <span class="small text-muted">
                                    Ausgewählt: {{ (selectedTalentsByUser[activeCharacter.user_id] ?? []).length }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="small text-uppercase text-muted mb-2" style="letter-spacing: 1px;">Anfragen für {{ activeCharacter.user.name }}</div>
                            <div v-if="activeRequests.length === 0" class="text-muted small">Keine Anfragen vorhanden.</div>
                            <div v-else class="d-flex flex-column gap-2">
                                <div v-for="request in activeRequests" :key="request.id" class="border rounded p-2 bg-light-subtle">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="small">
                                            {{ request.talents.map((t) => t.label).join(', ') }}
                                            <span class="text-muted"> · {{ modifierLabel(request) }}</span>
                                        </div>
                                        <span class="badge" :class="request.status === 'confirmed' ? 'text-bg-success' : 'text-bg-warning'">
                                            {{ request.status === 'confirmed' ? 'Abgeschlossen' : 'Offen' }}
                                        </span>
                                    </div>
                                    <div class="small mt-2 d-flex flex-column gap-1">
                                        <div
                                            v-for="talent in request.talents"
                                            :key="`${request.id}:${talent.key}`"
                                            class="d-flex justify-content-between align-items-center border rounded px-2 py-1 bg-white"
                                        >
                                            <span>{{ talent.label }}</span>
                                            <span class="d-flex align-items-center gap-2">
                                                <span v-if="talent.rolledAt" class="text-muted">
                                                    {{ talent.rolledValue }} / {{ talent.targetValue }}
                                                </span>
                                                <span class="badge" :class="talentResultClass(talent)">
                                                    {{ talentResultText(talent) }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-4">
                        <img
                            v-if="activeCharacter.image_url"
                            :src="activeCharacter.image_url"
                            :alt="`Charakterbild von ${activeCharacter.name}`"
                            class="img-fluid rounded border"
                        >
                        <div v-else class="text-muted small">Kein Charakterbild verfügbar.</div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <DiceRoller :party-id="party.id" />
</template>
