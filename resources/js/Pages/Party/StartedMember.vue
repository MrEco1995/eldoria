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
const rollingKeys = ref({});
const activeTab = ref('character');
const usePreviewFallback = ref(false);

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

const latestMyRequest = computed(() => myRequests.value[0] ?? null);

const racePreviewSources = computed(() => {
    if (!props.character?.race || !props.character?.gender) {
        return [];
    }

    const raceKey = Object.keys(raceImageBaseMap).find((key) => props.character.race.startsWith(key));
    const genderSuffix = genderImageSuffixMap[props.character.gender];

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

const racePreviewIndex = ref(0);

watch(
    racePreviewSources,
    () => {
        racePreviewIndex.value = 0;
        usePreviewFallback.value = false;
    },
    { immediate: true },
);

const currentRacePreviewSrc = computed(() => {
    return racePreviewSources.value[racePreviewIndex.value] ?? null;
});

const displayCharacterImage = computed(() => {
    if (!usePreviewFallback.value && props.character?.image_url) {
        return props.character.image_url;
    }

    return currentRacePreviewSrc.value || null;
});

const handleCharacterImageError = () => {
    if (!usePreviewFallback.value && props.character?.image_url) {
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

const modifierLabel = (request) => {
    if (!request || request.modifierType === 'none' || !request.modifierPoints) return 'Normal';
    return request.modifierType === 'easy'
        ? `Erleichtert +${request.modifierPoints}`
        : `Erschwert -${request.modifierPoints}`;
};

const isRolled = (talent) => talent?.rolledAt != null;
const resultClass = (talent) => {
    if (!isRolled(talent)) return 'text-bg-warning';
    return talent.isSuccess ? 'text-bg-success' : 'text-bg-danger';
};
const resultText = (talent) => {
    if (!isRolled(talent)) return 'Offen';
    return talent.isSuccess ? 'Erfolg' : 'Fehlschlag';
};

const makeRollKey = (requestId, talentKey) => `${requestId}:${talentKey}`;

const rollTalent = async (request, talent) => {
    if (!talent?.key || isRolled(talent)) return;
    const rollKey = makeRollKey(request.id, talent.key);
    if (rollingKeys.value[rollKey]) return;

    rollingKeys.value[rollKey] = true;
    const rolledValue = Math.floor(Math.random() * 20) + 1;

    try {
        const response = await window.axios.post(route('parties.talent-requests.confirm', {
            party: props.party.id,
            talentRequest: request.id,
        }), {
            rolled_talent_key: talent.key,
            rolled_value: rolledValue,
        });
        if (response?.data?.request) {
            upsertRequest(response.data.request);
        }
    } catch {
        // ignore; flash handles errors
    } finally {
        rollingKeys.value[rollKey] = false;
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

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-3 p-md-4">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button
                            type="button"
                            class="nav-link"
                            :class="{ active: activeTab === 'character' }"
                            @click="activeTab = 'character'"
                        >
                            Charakter
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button
                            type="button"
                            class="nav-link"
                            :class="{ active: activeTab === 'inventory' }"
                            @click="activeTab = 'inventory'"
                        >
                            Inventar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button
                            type="button"
                            class="nav-link"
                            :class="{ active: activeTab === 'notes' }"
                            @click="activeTab = 'notes'"
                        >
                            Notizen
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <div v-if="activeTab === 'character'" class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card shadow-sm border-0">
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
            </div>

            <div class="col-12 col-xl-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-3">
                        <img
                            v-if="displayCharacterImage"
                            :src="displayCharacterImage"
                            :alt="`Charakterbild von ${character.name}`"
                            class="img-fluid rounded border"
                            @error="handleCharacterImageError"
                        >
                        <div v-else class="text-muted small">Kein Charakterbild verfügbar.</div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">Anforderungen</div>
                        <div v-if="!latestMyRequest" class="text-muted small">Keine Talentanforderungen vom Spielleiter.</div>
                        <div v-else class="d-flex flex-column gap-2">
                            <div class="border rounded p-3 bg-light-subtle">
                                <div class="small mb-2">
                                    <strong>{{ latestMyRequest.ownerUserName }}</strong> fordert:
                                    {{ latestMyRequest.talents.map((t) => t.label).join(', ') }}
                                    <span class="text-muted"> · {{ modifierLabel(latestMyRequest) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge" :class="latestMyRequest.status === 'confirmed' ? 'text-bg-success' : 'text-bg-warning'">
                                        {{ latestMyRequest.status === 'confirmed' ? 'Abgeschlossen' : 'Offen' }}
                                    </span>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div
                                        v-for="talent in latestMyRequest.talents"
                                        :key="`${latestMyRequest.id}:${talent.key}`"
                                        class="d-flex justify-content-between align-items-center gap-2 border rounded px-2 py-2 bg-white"
                                    >
                                        <div class="small">
                                            <div class="fw-semibold">{{ talent.label }}</div>
                                            <div v-if="isRolled(talent)" class="text-muted">
                                                Wurf: {{ talent.rolledValue }} · Zielwert: {{ talent.targetValue }}
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge" :class="resultClass(talent)">
                                                {{ resultText(talent) }}
                                            </span>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary"
                                                :disabled="isRolled(talent) || rollingKeys[`${latestMyRequest.id}:${talent.key}`]"
                                                @click="rollTalent(latestMyRequest, talent)"
                                            >
                                                W20 würfeln
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="activeTab === 'inventory'" class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">Inventar</div>
                <h3 class="h5 mb-2">Inventar wird vorbereitet</h3>
                <p class="text-muted mb-0">Hier kannst du später deine Gegenstände, Ausrüstung und Notizen verwalten.</p>
            </div>
        </div>

        <div v-else class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">Notizen</div>
                <h3 class="h5 mb-2">Notizen werden vorbereitet</h3>
                <p class="text-muted mb-0">Hier kannst du später Sitzungsnotizen, Namen und wichtige Hinweise sammeln.</p>
            </div>
        </div>
    </AuthenticatedLayout>

    <DiceRoller :party-id="party.id" />
</template>
