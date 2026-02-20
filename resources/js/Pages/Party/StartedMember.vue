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
const inventoryItems = ref([...(props.character?.inventoryItems ?? [])]);
const inventoryForm = ref({
    name: '',
    quantity: 1,
    category: '',
    notes: '',
});
const inventoryBusy = ref(false);

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

watch(() => props.character?.inventoryItems, (next) => {
    inventoryItems.value = [...(next ?? [])];
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

const makeRollKey = (requestId, talentKey) => `${requestId}:${talentKey}`;

const resetInventoryForm = () => {
    inventoryForm.value = {
        name: '',
        quantity: 1,
        category: '',
        notes: '',
    };
};

const addInventoryItem = async () => {
    if (!props.character?.id || !inventoryForm.value.name.trim() || inventoryBusy.value) return;
    inventoryBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.inventory-items.store', props.party.id), {
            party_character_id: props.character.id,
            name: inventoryForm.value.name.trim(),
            quantity: Number(inventoryForm.value.quantity || 1),
            category: inventoryForm.value.category?.trim() || null,
            notes: inventoryForm.value.notes?.trim() || null,
        });
        if (response?.data?.item) {
            inventoryItems.value.push(response.data.item);
            inventoryItems.value.sort((a, b) => Number(a.sortOrder) - Number(b.sortOrder));
        }
        resetInventoryForm();
    } catch {
        // handled by backend flashes/logging
    } finally {
        inventoryBusy.value = false;
    }
};

const updateInventoryQuantity = async (item, delta) => {
    const nextQuantity = Math.max(1, Number(item.quantity) + delta);
    if (nextQuantity === Number(item.quantity)) return;
    try {
        const response = await window.axios.patch(route('parties.inventory-items.update', {
            party: props.party.id,
            inventoryItem: item.id,
        }), {
            quantity: nextQuantity,
        });
        if (response?.data?.item) {
            const idx = inventoryItems.value.findIndex((entry) => Number(entry.id) === Number(item.id));
            if (idx >= 0) inventoryItems.value[idx] = response.data.item;
        }
    } catch {
        // handled by backend
    }
};

const removeInventoryItem = async (item) => {
    try {
        await window.axios.delete(route('parties.inventory-items.destroy', {
            party: props.party.id,
            inventoryItem: item.id,
        }));
        inventoryItems.value = inventoryItems.value.filter((entry) => Number(entry.id) !== Number(item.id));
    } catch {
        // handled by backend
    }
};

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

        <div class="eldoria-page">
            <div class="card shadow-sm border-0 mb-4 eldoria-panel">
                <div class="card-body p-3 p-md-4">
                    <ul class="nav nav-tabs eldoria-nav-tabs" role="tablist">
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
                    <div class="card shadow-sm border-0 eldoria-panel">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Charakterbogen</div>
                        <h3 class="h4 mb-3 eldoria-title">{{ character.name }}</h3>

                        <div class="text-muted mb-3">{{ character.race }} · {{ character.class_name }} · {{ character.gender }}</div>
                        <div class="text-muted mb-4">{{ character.age }} Jahre · {{ character.height_cm }} cm · {{ character.weight_kg }} kg</div>

                        <div class="mb-4">
                            <div class="small text-uppercase text-muted mb-2 eldoria-kicker-soft">Traits</div>
                            <div class="d-flex flex-wrap gap-2">
                                <span v-for="trait in character.traits" :key="trait" class="badge text-bg-light border eldoria-trait">{{ trait }}</span>
                            </div>
                        </div>

                        <div>
                            <div class="small text-uppercase text-muted mb-2 eldoria-kicker-soft">Talente</div>
                            <div class="row g-3">
                                <div v-for="group in talentGroups" :key="group.category" class="col-12 col-lg-6">
                                    <div class="border rounded p-3 bg-light-subtle h-100 eldoria-subpanel">
                                        <div class="fw-semibold small mb-2 eldoria-subtitle">{{ group.category }}</div>
                                        <div
                                            v-for="talent in group.items"
                                            :key="talent.key"
                                            class="d-flex justify-content-between border rounded px-3 py-2 bg-white mb-2 eldoria-row"
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
                    <div class="card shadow-sm border-0 mb-4 eldoria-panel">
                    <div class="card-body p-3">
                        <img
                            v-if="displayCharacterImage"
                            :src="displayCharacterImage"
                            :alt="`Charakterbild von ${character.name}`"
                            class="img-fluid rounded border eldoria-portrait"
                            @error="handleCharacterImageError"
                        >
                        <div v-else class="text-muted small">Kein Charakterbild verfügbar.</div>
                    </div>
                </div>

                    <div class="card shadow-sm border-0 eldoria-panel">
                    <div class="card-body p-4">
                        <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Anforderungen</div>
                        <div v-if="!latestMyRequest" class="text-muted small">Keine Talentanforderungen vom Spielleiter.</div>
                        <div v-else class="d-flex flex-column gap-2">
                            <div class="border rounded p-3 bg-light-subtle eldoria-subpanel">
                                <div class="small mb-2">
                                    <strong>{{ latestMyRequest.ownerUserName }}</strong> fordert:
                                    {{ latestMyRequest.talents.map((t) => t.label).join(', ') }}
                                    <span class="text-muted"> · {{ modifierLabel(latestMyRequest) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge" :class="requestResultClass(latestMyRequest)">
                                        {{ requestResultText(latestMyRequest) }}
                                    </span>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div
                                        v-for="talent in latestMyRequest.talents"
                                        :key="`${latestMyRequest.id}:${talent.key}`"
                                        class="d-flex justify-content-between align-items-center gap-2 border rounded px-2 py-2 bg-white eldoria-row"
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

            <div v-else-if="activeTab === 'inventory'" class="card shadow-sm border-0 eldoria-panel">
                <div class="card-body p-4 p-md-5">
                    <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Inventar</div>
                    <h3 class="h5 mb-3 eldoria-title">Reiseausrüstung von {{ character.name }}</h3>

                    <div class="row g-2 mb-4">
                        <div class="col-12 col-md-5">
                            <input v-model="inventoryForm.name" type="text" class="form-control" placeholder="Gegenstand">
                        </div>
                        <div class="col-6 col-md-2">
                            <input v-model.number="inventoryForm.quantity" type="number" min="1" max="999" class="form-control" placeholder="Menge">
                        </div>
                        <div class="col-6 col-md-3">
                            <input v-model="inventoryForm.category" type="text" class="form-control" placeholder="Kategorie">
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="button" class="btn btn-primary w-100" :disabled="inventoryBusy" @click="addInventoryItem">
                                Hinzufügen
                            </button>
                        </div>
                        <div class="col-12">
                            <input v-model="inventoryForm.notes" type="text" class="form-control" placeholder="Notiz (optional)">
                        </div>
                    </div>

                    <div class="bag-area p-3 p-md-4">
                        <div class="bag-mouth mb-3">Jutebeutel</div>
                        <div v-if="inventoryItems.length === 0" class="text-muted small">
                            Dein Beutel ist leer.
                        </div>
                        <div v-else class="row g-2">
                            <div v-for="item in inventoryItems" :key="item.id" class="col-12 col-md-6">
                                <div class="bag-item d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold">{{ item.name }}</div>
                                        <div class="small text-muted">
                                            {{ item.category || 'Allgemein' }}<span v-if="item.notes"> · {{ item.notes }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="updateInventoryQuantity(item, -1)">-</button>
                                        <span class="px-2 small fw-semibold">{{ item.quantity }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="updateInventoryQuantity(item, 1)">+</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger ms-1" @click="removeInventoryItem(item)">x</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="card shadow-sm border-0 eldoria-panel">
            <div class="card-body p-4 p-md-5">
                <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Notizen</div>
                <h3 class="h5 mb-2 eldoria-title">Notizen werden vorbereitet</h3>
                <p class="text-muted mb-0">Hier kannst du später Sitzungsnotizen, Namen und wichtige Hinweise sammeln.</p>
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

.bag-area {
    background:
        radial-gradient(circle at 20% 15%, rgba(255, 255, 255, 0.25), transparent 35%),
        repeating-linear-gradient(
            45deg,
            rgba(134, 97, 54, 0.14) 0px,
            rgba(134, 97, 54, 0.14) 6px,
            rgba(122, 88, 49, 0.08) 6px,
            rgba(122, 88, 49, 0.08) 12px
        ),
        #b48650;
    border: 2px solid #8d6234;
    border-radius: 18px 18px 26px 26px;
    box-shadow: inset 0 2px 8px rgba(72, 45, 21, 0.35);
}

.bag-mouth {
    display: inline-block;
    background: #6f4c27;
    color: #f7ead5;
    font-size: 0.74rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
}

.bag-item {
    background: rgba(255, 246, 230, 0.9);
    border: 1px solid rgba(96, 64, 30, 0.25);
    border-radius: 10px;
    padding: 0.6rem 0.65rem;
}
</style>
