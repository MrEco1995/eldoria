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
const activeDetailTab = ref('character');
const selectedTalentsByUser = ref({});
const modifierByUser = ref({});
const requestState = ref([...props.talentRequests]);
const usePreviewFallback = ref(false);
const racePreviewIndex = ref(0);

const raceImageBaseMap = {
    Menschen: 'Mensch',
    Elfen: 'Elf',
    Zwerge: 'Zwerg',
    Orks: 'Ork',
    Faelun: 'Faelun',
    Noctyr: 'Noctyr',
    Tharokh: 'Tharokh',
};

const genderImageSuffixMap = {
    Männlich: 'Man',
    Weiblich: 'Woman',
};

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

const latestActiveRequest = computed(() => {
    if (!activeCharacter.value) return null;
    return requestState.value
        .filter((request) => Number(request.targetUserId) === Number(activeCharacter.value.user_id))
        .sort((a, b) => Number(b.id) - Number(a.id))[0] ?? null;
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

const racePreviewSources = computed(() => {
    if (!activeCharacter.value?.race || !activeCharacter.value?.gender) {
        return [];
    }

    const raceKey = Object.keys(raceImageBaseMap).find((key) => activeCharacter.value.race.startsWith(key));
    const genderSuffix = genderImageSuffixMap[activeCharacter.value.gender];

    if (!raceKey || !genderSuffix) {
        return [];
    }

    const base = raceImageBaseMap[raceKey];
    const candidates = [
        `${base}${genderSuffix}.jpeg`,
        `${base}${genderSuffix}.jpg`,
        `${base}${genderSuffix}.png`,
        `${base}.jpeg`,
        `${base}.jpg`,
        `${base}.png`,
    ];

    return candidates.flatMap((path) => [
        `/storage/${path}`,
        route('media.public', { path }),
    ]);
});

watch(
    () => activeCharacter.value?.id,
    () => {
        activeDetailTab.value = 'character';
        racePreviewIndex.value = 0;
        usePreviewFallback.value = false;
    },
    { immediate: true },
);

const currentRacePreviewSrc = computed(() => {
    return racePreviewSources.value[racePreviewIndex.value] ?? null;
});

const displayCharacterImage = computed(() => {
    if (!usePreviewFallback.value && activeCharacter.value?.image_url) {
        return activeCharacter.value.image_url;
    }
    return currentRacePreviewSrc.value || null;
});

const handleCharacterImageError = () => {
    if (!usePreviewFallback.value && activeCharacter.value?.image_url) {
        usePreviewFallback.value = true;
        racePreviewIndex.value = 0;
        return;
    }

    if (racePreviewIndex.value < racePreviewSources.value.length - 1) {
        racePreviewIndex.value += 1;
        return;
    }

    racePreviewIndex.value = racePreviewSources.value.length;
};

const talentResultClass = (talent) => {
    if (!talent?.rolledAt) return 'text-bg-warning';
    return talent.isSuccess ? 'text-bg-success' : 'text-bg-danger';
};
const talentResultText = (talent) => {
    if (!talent?.rolledAt) return 'Offen';
    return talent.isSuccess ? 'Erfolg' : 'Fehlschlag';
};

const requestResultClass = (request) => {
    if (!request || request.status !== 'confirmed') return 'text-bg-warning';
    const allSuccess = (request.talents ?? []).every((talent) => talent?.isSuccess === true);
    return allSuccess ? 'text-bg-success' : 'text-bg-danger';
};

const requestResultText = (request) => {
    if (!request || request.status !== 'confirmed') return 'Offen';
    const allSuccess = (request.talents ?? []).every((talent) => talent?.isSuccess === true);
    return allSuccess ? 'Bestanden' : 'Nicht bestanden';
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

        <div class="eldoria-page">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <div class="text-uppercase small text-muted eldoria-kicker">Spielleiter Ansicht</div>
                    <div class="text-muted">Talente auswählen und live an Spieler anfordern.</div>
                </div>
                <Link :href="route('parties.end', party.id)" method="post" as="button" class="btn btn-outline-danger">
                    Party beenden
                </Link>
            </div>

            <div v-if="playerCharacters.length === 0" class="alert alert-warning border-0">Keine Charaktere gefunden.</div>

            <div v-else class="card shadow-sm border-0 eldoria-panel">
                <div class="card-body p-3 p-md-4">
                    <ul class="nav nav-tabs eldoria-nav-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
                    <li v-for="entry in playerCharacters" :key="entry.id" class="nav-item" role="presentation">
                        <button type="button" class="nav-link" :class="{ active: activeCharacterId === entry.id }" @click="activeCharacterId = entry.id">
                            {{ entry.user.name }}
                        </button>
                    </li>
                    </ul>

                    <div v-if="activeCharacter">
                        <ul class="nav nav-tabs eldoria-nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button
                                type="button"
                                class="nav-link"
                                :class="{ active: activeDetailTab === 'character' }"
                                @click="activeDetailTab = 'character'"
                            >
                                Charakter
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                type="button"
                                class="nav-link"
                                :class="{ active: activeDetailTab === 'inventory' }"
                                @click="activeDetailTab = 'inventory'"
                            >
                                Inventar
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                type="button"
                                class="nav-link"
                                :class="{ active: activeDetailTab === 'notes' }"
                                @click="activeDetailTab = 'notes'"
                            >
                                Notizen
                            </button>
                        </li>
                        </ul>

                        <div v-if="activeDetailTab === 'character'" class="row g-4">
                            <div class="col-12 col-xl-8">
                            <h4 class="h5 mb-1">{{ activeCharacter.name }}</h4>
                            <div class="text-muted mb-2">{{ activeCharacter.race }} · {{ activeCharacter.class_name }} · {{ activeCharacter.gender }}</div>
                            <div class="text-muted mb-3">{{ activeCharacter.age }} Jahre · {{ activeCharacter.height_cm }} cm · {{ activeCharacter.weight_kg }} kg</div>

                            <div class="mb-4">
                                <div class="small text-uppercase text-muted mb-2 eldoria-kicker-soft">Traits</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <span v-for="trait in activeCharacter.traits" :key="trait" class="badge text-bg-light border eldoria-trait">{{ trait }}</span>
                                </div>
                            </div>

                            <div>
                                <div class="small text-uppercase text-muted mb-2 eldoria-kicker-soft">Talente anfordern</div>
                                <div class="row g-3">
                                    <div v-for="group in talentGroups" :key="group.category" class="col-12 col-lg-6">
                                        <div class="border rounded p-3 bg-light-subtle h-100 eldoria-subpanel">
                                            <div class="fw-semibold small mb-2 eldoria-subtitle">{{ group.category }}</div>
                                            <div
                                                v-for="talent in group.items"
                                                :key="talent.key"
                                                class="d-flex justify-content-between align-items-center border rounded px-3 py-2 bg-white mb-2 eldoria-row"
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
                            </div>

                            <div class="col-12 col-xl-4">
                                <div class="card shadow-sm border-0 mb-4 eldoria-panel">
                                <div class="card-body p-3">
                                    <img
                                        v-if="displayCharacterImage"
                                        :src="displayCharacterImage"
                                        :alt="`Charakterbild von ${activeCharacter.name}`"
                                        class="img-fluid rounded border eldoria-portrait"
                                        @error="handleCharacterImageError"
                                    >
                                    <div v-else class="text-muted small">Kein Charakterbild verfügbar.</div>
                                </div>
                            </div>

                                <div class="card shadow-sm border-0 eldoria-panel">
                                <div class="card-body p-3">
                                    <div class="small text-uppercase text-muted mb-2 eldoria-kicker-soft">Anfragen für {{ activeCharacter.user.name }}</div>
                                    <div v-if="!latestActiveRequest" class="text-muted small">Keine Anfragen vorhanden.</div>
                                    <div v-else class="border rounded p-2 bg-light-subtle eldoria-subpanel">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="small">
                                                {{ latestActiveRequest.talents.map((t) => t.label).join(', ') }}
                                                <span class="text-muted"> · {{ modifierLabel(latestActiveRequest) }}</span>
                                            </div>
                                            <span class="badge" :class="requestResultClass(latestActiveRequest)">
                                                {{ requestResultText(latestActiveRequest) }}
                                            </span>
                                        </div>
                                        <div class="small mt-2 d-flex flex-column gap-1">
                                            <div
                                                v-for="talent in latestActiveRequest.talents"
                                                :key="`${latestActiveRequest.id}:${talent.key}`"
                                                class="d-flex justify-content-between align-items-center border rounded px-2 py-1 bg-white eldoria-row"
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
                        </div>

                        <div v-else-if="activeDetailTab === 'inventory'" class="card shadow-sm border-0 eldoria-panel">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Inventar</div>
                            <h3 class="h5 mb-2 eldoria-title">Inventar wird vorbereitet</h3>
                            <p class="text-muted mb-0">Hier siehst du später das Inventar des ausgewählten Spielers.</p>
                        </div>
                    </div>

                        <div v-else class="card shadow-sm border-0 eldoria-panel">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Notizen</div>
                            <h3 class="h5 mb-2 eldoria-title">Notizen werden vorbereitet</h3>
                            <p class="text-muted mb-0">Hier kannst du später Spielleiter-Notizen zum ausgewählten Spieler verwalten.</p>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <DiceRoller :party-id="party.id" />
</template>

<style scoped>
.eldoria-page {
    --eldoria-bg: #f6f1e5;
    --eldoria-paper: #fffdf8;
    --eldoria-ink: #2f2618;
    --eldoria-accent: #7f5a2a;
    --eldoria-border: #d8c7a7;
    background:
        radial-gradient(circle at 15% 0%, rgba(127, 90, 42, 0.14), transparent 32%),
        radial-gradient(circle at 85% 8%, rgba(40, 92, 61, 0.12), transparent 30%),
        var(--eldoria-bg);
    border-radius: 16px;
    padding: 1rem;
}

.eldoria-panel {
    background: linear-gradient(180deg, rgba(255, 253, 248, 0.98), rgba(247, 238, 223, 0.97));
    border: 1px solid var(--eldoria-border) !important;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(64, 43, 19, 0.12) !important;
}

.eldoria-nav-tabs {
    border-bottom: 1px solid var(--eldoria-border);
    gap: 0.25rem;
}

.eldoria-nav-tabs .nav-link {
    color: var(--eldoria-ink);
    border: 1px solid transparent;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
}

.eldoria-nav-tabs .nav-link.active {
    color: var(--eldoria-accent);
    background: var(--eldoria-paper);
    border-color: var(--eldoria-border);
    border-bottom-color: var(--eldoria-paper);
}

.eldoria-kicker {
    letter-spacing: 0.14em;
    color: #6a5233 !important;
}

.eldoria-kicker-soft {
    letter-spacing: 0.08em;
    color: #6a5233 !important;
}

.eldoria-title {
    font-family: Georgia, 'Times New Roman', serif;
    color: var(--eldoria-ink);
}

.eldoria-subtitle {
    color: #6f5432;
}

.eldoria-subpanel {
    border-color: var(--eldoria-border) !important;
    background: rgba(255, 252, 245, 0.86) !important;
}

.eldoria-row {
    border-color: #e4d7bf !important;
}

.eldoria-trait {
    background: #f4ead9 !important;
    border-color: #d9c6a2 !important;
    color: #4f3a21 !important;
}

.eldoria-portrait {
    width: 100%;
    max-height: 460px;
    object-fit: cover;
    border-color: var(--eldoria-border) !important;
}
</style>
