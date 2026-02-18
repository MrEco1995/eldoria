<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    party: {
        type: Object,
        required: true,
    },
    members: {
        type: Array,
        default: () => [],
    },
    invite: {
        type: Object,
        default: null,
    },
    character: {
        type: Object,
        default: null,
    },
    characters: {
        type: Array,
        default: () => [],
    },
    races: {
        type: Array,
        default: () => [],
    },
    talents: {
        type: Array,
        default: () => [],
    },
    talentPointPool: {
        type: Number,
        default: 35,
    },
    isOwner: {
        type: Boolean,
        default: false,
    },
});

const isStarted = computed(() => !!props.party.startedAt);
const membersState = ref([...props.members]);
const page = usePage();
const authUserId = computed(() => page.props.auth?.user?.id ?? null);
let membersPollIntervalId = null;

const allReady = computed(() => {
    if (!membersState.value.length) {
        return false;
    }
    return membersState.value.every((member) => member.is_ready);
});

const canCloseParty = computed(() => {
    return props.isOwner && membersState.value.length <= 1;
});

const copied = ref(false);
const characterStep = ref(1);
const maxTalentPoints = computed(() => Number(props.talentPointPool ?? 35));

const talentDefinitions = computed(() => {
    const groups = new Map();
    (props.talents ?? []).forEach((talent) => {
        const category = talent.category || 'Sonstige Talente';
        if (!groups.has(category)) {
            groups.set(category, []);
        }
        groups.get(category).push(talent);
    });
    return Array.from(groups.entries()).map(([category, items]) => ({ category, items }));
});

const talentMaxByKey = computed(() => {
    return Object.fromEntries(
        (props.talents ?? []).map((talent) => [talent.key, Number(talent.maxPoints ?? 15)]),
    );
});

const buildInitialTalents = () => {
    return Object.fromEntries((props.talents ?? []).map((talent) => [talent.key, 0]));
};

const characterForm = useForm({
    name: '',
    race: '',
    class_name: '',
    gender: '',
    age: '',
    height_cm: '',
    weight_kg: '',
    traits: [],
    talents: buildInitialTalents(),
});

const inviteExpiresText = computed(() => {
    if (!props.invite?.expiresAt) {
        return '';
    }
    const date = new Date(props.invite.expiresAt);
    return date.toLocaleString('de-DE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const copyInvite = async () => {
    if (!props.invite?.url) {
        return;
    }

    await navigator.clipboard.writeText(props.invite.url);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 1500);
};

const goToStartedPage = () => {
    router.visit(route('parties.started', props.party.id), {
        preserveScroll: true,
    });
};

const refreshMembers = () => {
    router.reload({
        only: ['members', 'party'],
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
            if (page?.props?.party?.startedAt) {
                goToStartedPage();
            }
        },
    });
};

const toggleReady = (member = null) => {
    const previous = member ? !!member.is_ready : null;

    // Optimistic UI update for the current user.
    if (member) {
        member.is_ready = !member.is_ready;
    }

    router.post(route('parties.ready.toggle', props.party.id), {}, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            if (member) {
                member.is_ready = previous;
            }
        },
        onSuccess: () => {
            // Keep list in sync even without realtime broadcasting.
            refreshMembers();
        },
    });
};

const submitCharacter = () => {
    characterForm.post(route('parties.characters.store', props.party.id), {
        preserveScroll: true,
    });
};

const classes = [
    'Magier',
    'Krieger',
    'Waldläufer',
    'Assassine',
    'Priester',
    'Barde',
];

const genders = [
    'Männlich',
    'Weiblich',
    'Divers',
];

const traits = [
    'Mutig',
    'Gerissen',
    'Loyal',
    'Ehrgeizig',
    'Neugierig',
    'Diszipliniert',
    'Chaotisch',
    'Empathisch',
    'Geduldig',
    'Stur',
    'Taktisch',
    'Impulsiv',
    'Idealistisch',
    'Pragmatisch',
    'Humorvoll',
    'Misstrauisch',
    'Ängstlich',
    'Großzügig',
];

const races = computed(() => props.races ?? []);

const selectedRace = computed(() => {
    return races.value.find((race) => race.name === characterForm.race);
});

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

const racePreviewSources = computed(() => {
    if (!characterForm.race || !characterForm.gender) {
        return [];
    }

    const raceKey = Object.keys(raceImageBaseMap).find((key) => characterForm.race.startsWith(key));
    const genderSuffix = genderImageSuffixMap[characterForm.gender];

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
    },
    { immediate: true },
);

const currentRacePreviewSrc = computed(() => {
    return racePreviewSources.value[racePreviewIndex.value] ?? null;
});

const handleRacePreviewError = () => {
    if (racePreviewIndex.value < racePreviewSources.value.length - 1) {
        racePreviewIndex.value += 1;
        return;
    }

    racePreviewIndex.value = racePreviewSources.value.length;
};

watch(
    () => props.members,
    (nextMembers) => {
        membersState.value = [...nextMembers];
    },
    { immediate: true },
);

watch(
    () => props.talents,
    (nextTalents) => {
        const keys = new Set((nextTalents ?? []).map((talent) => talent.key));
        Object.keys(characterForm.talents).forEach((key) => {
            if (!keys.has(key)) {
                delete characterForm.talents[key];
            }
        });
        keys.forEach((key) => {
            if (characterForm.talents[key] === undefined) {
                characterForm.talents[key] = 0;
            }
        });
    },
    { immediate: true },
);

const allocatedTalentPoints = computed(() => {
    return Object.values(characterForm.talents).reduce((sum, value) => sum + (Number(value) || 0), 0);
});

const remainingTalentPoints = computed(() => {
    return maxTalentPoints.value - allocatedTalentPoints.value;
});

const getTalentPoints = (key) => Number(characterForm.talents[key] || 0);
const getTalentMaxPoints = (key) => Number(talentMaxByKey.value[key] ?? 15);

const incrementTalent = (key) => {
    const current = getTalentPoints(key);
    if (current >= getTalentMaxPoints(key) || remainingTalentPoints.value <= 0) {
        return;
    }

    characterForm.talents[key] = current + 1;
};

const decrementTalent = (key) => {
    const current = getTalentPoints(key);
    if (current <= 0) {
        return;
    }

    characterForm.talents[key] = current - 1;
};

const goToTalentStep = () => {
    characterStep.value = 2;
};

const hasTalentErrors = computed(() => {
    return Object.keys(characterForm.errors).some(
        (key) => key === 'talents' || key.startsWith('talents.'),
    );
});

watch(hasTalentErrors, (value) => {
    if (value) {
        characterStep.value = 2;
    }
});

const missingCharacterCount = computed(() => {
    const memberIds = membersState.value
        .filter((member) => member.id !== props.party.owner.id)
        .map((member) => member.id);
    const withCharacter = props.characters.map((entry) => entry.user.id);
    return memberIds.filter((id) => !withCharacter.includes(id)).length;
});

const canViewCharacterDetails = (entry) => {
    return Number(entry.user_id) === Number(authUserId.value);
};

const onReadyUpdated = (event) => {
    const target = membersState.value.find((member) => member.id === event.userId);
    if (target) {
        target.is_ready = event.isReady;
    }
};

const onPartyStarted = (event) => {
    if (Number(event.partyId) !== Number(props.party.id)) {
        return;
    }

    goToStartedPage();
};

onMounted(() => {
    if (window.Echo) {
        window.Echo.private(`party.${props.party.id}`)
            .listen('.party.ready.updated', onReadyUpdated)
            .listen('.party.started', onPartyStarted);
    } else {
        // Fallback when realtime is disabled/unavailable.
        membersPollIntervalId = setInterval(refreshMembers, 3000);
    }
});

onBeforeUnmount(() => {
    if (membersPollIntervalId) {
        clearInterval(membersPollIntervalId);
        membersPollIntervalId = null;
    }

    if (window.Echo) {
        window.Echo.leave(`party.${props.party.id}`);
    }
});
</script>

<template>
    <Head :title="party.name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="h4 m-0">{{ party.name }}</h2>
        </template>

        <div class="row position-relative">
            <div
                class="position-absolute top-0 start-50 translate-middle-x"
                style="width: 420px; height: 200px; background: radial-gradient(circle, rgba(107,92,255,0.28), rgba(77,208,225,0)); filter: blur(6px);"
            ></div>

            <div v-if="isOwner" class="col-12 col-lg-7 position-relative">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                            Lobby
                        </div>
                        <h5 class="card-title mb-2">Einladung</h5>
                        <p class="text-muted mb-4">
                            Teile den Einladungslink. Er ist 30 Minuten gültig.
                        </p>

                        <div v-if="invite" class="mb-3">
                            <label class="form-label">Einladungslink</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    class="form-control"
                                    :value="invite.url"
                                    readonly
                                />
                                <button
                                    class="btn btn-outline-primary"
                                    type="button"
                                    @click="copyInvite"
                                >
                                    {{ copied ? 'Kopiert' : 'Kopieren' }}
                                </button>
                            </div>
                            <div class="text-muted small mt-2">
                                Gültig bis: {{ inviteExpiresText }}
                            </div>
                        </div>

                        <div v-else class="alert alert-warning border-0">
                            Kein aktiver Einladungslink. Erstelle einen neuen Link.
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <Link
                                v-if="isOwner"
                                :href="route('parties.invites.regenerate', party.id)"
                                method="post"
                                as="button"
                                class="btn btn-outline-primary"
                            >
                                Neuen Link erstellen
                            </Link>
                            <Link
                                v-if="isOwner"
                                :href="route('parties.close', party.id)"
                                method="post"
                                as="button"
                                class="btn btn-outline-danger"
                                :class="{ disabled: !canCloseParty }"
                                :disabled="!canCloseParty"
                            >
                                Party schließen
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div :class="isOwner ? 'col-12 col-lg-5 position-relative' : 'col-12 position-relative'">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                            Teilnehmer
                        </div>
                        <div v-if="membersState.length === 0" class="text-muted">
                            Noch keine Teilnehmer.
                        </div>
                        <ul v-else class="list-group list-group-flush">
                            <li
                                v-for="member in membersState"
                                :key="member.id"
                                class="list-group-item px-0 d-flex justify-content-between align-items-center"
                            >
                                <div>
                                    <div class="fw-semibold d-flex align-items-center gap-2">
                                        <span>{{ member.name }}</span>
                                        <span
                                            v-if="party.owner && member.id === party.owner.id"
                                            class="badge text-bg-warning d-inline-flex align-items-center gap-1"
                                            title="Owner"
                                        >
                                            <svg
                                                width="12"
                                                height="12"
                                                viewBox="0 0 24 24"
                                                fill="currentColor"
                                                aria-hidden="true"
                                            >
                                                <path d="M5 17l-2-9 5 3 4-6 4 6 5-3-2 9H5zm0 2h14v2H5v-2z" />
                                            </svg>
                                            Owner
                                        </span>
                                        <span
                                            v-if="member.is_ready"
                                            class="badge text-bg-success"
                                        >
                                            Bereit
                                        </span>
                                        <span
                                            v-else
                                            class="badge text-bg-secondary"
                                        >
                                            Nicht bereit
                                        </span>
                                    </div>
                                    <div class="text-muted small">{{ member.email }}</div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div v-if="member.id === $page.props.auth.user.id">
                                        <div class="form-check m-0">
                                            <input
                                                :id="`readySwitch-${member.id}`"
                                                class="form-check-input"
                                                :class="member.is_ready ? 'bg-success border-success' : 'bg-danger border-danger'"
                                                type="checkbox"
                                                :checked="member.is_ready"
                                                @change="toggleReady(member)"
                                            />
                                        </div>
                                    </div>
                                    <Link
                                        v-if="isOwner && member.id !== party.owner.id"
                                        :href="route('parties.members.remove', { party: party.id, userId: member.id })"
                                        method="post"
                                        as="button"
                                        class="btn btn-sm btn-outline-danger"
                                    >
                                        Entfernen
                                    </Link>
                                    <Link
                                        v-if="member.id === $page.props.auth.user.id && !isOwner"
                                        :href="route('parties.leave', party.id)"
                                        method="post"
                                        as="button"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        Party verlassen
                                    </Link>
                                </div>
                            </li>
                            <li v-if="isOwner" class="list-group-item px-0">
                                <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap">
                                    <Link
                                        :href="route('parties.start', party.id)"
                                        method="post"
                                        as="button"
                                        class="btn btn-primary"
                                        :class="{ disabled: !allReady || isStarted || missingCharacterCount > 0 }"
                                        :disabled="!allReady || isStarted || missingCharacterCount > 0"
                                    >
                                        {{ isStarted ? 'Gestartet' : 'Party starten' }}
                                    </Link>
                                    <span
                                        v-if="missingCharacterCount > 0"
                                        class="text-muted small"
                                    >
                                        Charaktere fehlen: {{ missingCharacterCount }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                            Charakter
                        </div>
                        <h5 class="card-title mb-2">{{ isOwner ? 'Charakterübersicht' : 'Dein Charakter' }}</h5>
                        <p v-if="!isOwner" class="text-muted mb-4">
                            Ein Charakter pro Party und Spieler. Owner kann keinen erstellen.
                        </p>
                        <p v-else class="text-muted mb-4">
                            Als Party-Owner siehst du nur die erstellten Charaktere.
                        </p>

                        <div v-if="isOwner" class="alert alert-info border-0">
                            Charakter-Erstellung ist nur für Mitglieder verfügbar.
                        </div>

                        <div v-else-if="character" class="alert alert-success border-0">
                            <div>
                                Charakter erstellt: <strong>{{ character.name }}</strong>
                            </div>
                            <div v-if="character.image_url" class="mt-3">
                                <img
                                    :src="character.image_url"
                                    :alt="`Charakterbild von ${character.name}`"
                                    class="img-fluid rounded border"
                                    style="max-width: 320px;"
                                />
                            </div>
                            <div v-else class="small text-muted mt-2">
                                Bild wird gerade von ComfyUI erstellt. Bitte Seite in einem Moment neu laden.
                            </div>
                        </div>

                        <form v-else @submit.prevent="submitCharacter" class="row g-3">
                            <template v-if="characterStep === 1">
                            <div class="col-12">
                                <label class="form-label" for="characterName">Charaktername</label>
                                <input
                                    id="characterName"
                                    v-model="characterForm.name"
                                    type="text"
                                    class="form-control"
                                    placeholder="z.B. Aelyra"
                                    :disabled="isOwner"
                                />
                                <div v-if="characterForm.errors.name" class="text-danger small mt-1">
                                    {{ characterForm.errors.name }}
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="characterGender">Geschlecht</label>
                                <select
                                    id="characterGender"
                                    v-model="characterForm.gender"
                                    class="form-select"
                                    :disabled="isOwner"
                                >
                                    <option value="">Bitte wählen</option>
                                    <option v-for="item in genders" :key="item" :value="item">
                                        {{ item }}
                                    </option>
                                </select>
                                <div v-if="characterForm.errors.gender" class="text-danger small mt-1">
                                    {{ characterForm.errors.gender }}
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="characterRace">Volk</label>
                                <select
                                    id="characterRace"
                                    v-model="characterForm.race"
                                    class="form-select"
                                    :disabled="isOwner"
                                >
                                    <option value="">Bitte wählen</option>
                                    <option v-for="race in races" :key="race.name" :value="race.name">
                                        {{ race.name }}
                                    </option>
                                </select>
                                <div v-if="characterForm.errors.race" class="text-danger small mt-1">
                                    {{ characterForm.errors.race }}
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="characterClass">Klasse</label>
                                <select
                                    id="characterClass"
                                    v-model="characterForm.class_name"
                                    class="form-select"
                                    :disabled="isOwner"
                                >
                                    <option value="">Bitte wählen</option>
                                    <option v-for="item in classes" :key="item" :value="item">
                                        {{ item }}
                                    </option>
                                </select>
                                <div v-if="characterForm.errors.class_name" class="text-danger small mt-1">
                                    {{ characterForm.errors.class_name }}
                                </div>
                            </div>

                            <div v-if="selectedRace" class="col-12">
                                <div class="alert alert-info border-0 mb-0">
                                    <div class="row g-3 align-items-start">
                                        <div class="col-12 col-md-6">
                                            <div class="fw-semibold mb-1">{{ selectedRace.name }}</div>
                                            <div class="mb-2">{{ selectedRace.description }}</div>
                                            <div class="small fw-semibold mt-3 mb-1">Wesen</div>
                                            <ul class="small mb-2">
                                                <li v-for="item in selectedRace.essence" :key="`essence-${item}`">
                                                    {{ item }}
                                                </li>
                                            </ul>
                                            <div class="small fw-semibold mt-2 mb-1">Aussehen</div>
                                            <ul class="small mb-2">
                                                <li v-for="item in selectedRace.appearance" :key="`appearance-${item}`">
                                                    {{ item }}
                                                </li>
                                            </ul>
                                            <div class="small"><span class="fw-semibold">Alter:</span> {{ selectedRace.age }}</div>
                                            <div class="small"><span class="fw-semibold">Größe:</span> {{ selectedRace.height }}</div>
                                            <div class="small mb-2"><span class="fw-semibold">Gewicht:</span> {{ selectedRace.weight }}</div>
                                            <div class="small">
                                                <span class="fw-semibold">Kommen gut klar mit:</span>
                                                {{ selectedRace.goodWith.join(', ') }}
                                            </div>
                                            <div class="small">
                                                <span class="fw-semibold">Kommen schlecht klar mit:</span>
                                                {{ selectedRace.badWith.join(', ') }}
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div v-if="currentRacePreviewSrc" class="rounded overflow-hidden border bg-white">
                                                <img
                                                    :src="currentRacePreviewSrc"
                                                    :alt="`Völkerbild ${selectedRace.name} ${characterForm.gender}`"
                                                    class="img-fluid d-block mx-auto"
                                                    style="max-height: 380px; object-fit: cover;"
                                                    @error="handleRacePreviewError"
                                                />
                                            </div>
                                            <div v-else-if="characterForm.gender" class="small text-muted">
                                                Kein Bild für diese Kombination gefunden.
                                            </div>
                                            <div v-else class="small text-muted">
                                                Wähle zusätzlich ein Geschlecht, um ein Völkerbild zu sehen.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="characterAge">Alter</label>
                                <input
                                    id="characterAge"
                                    v-model="characterForm.age"
                                    type="number"
                                    min="1"
                                    max="200"
                                    class="form-control"
                                    :disabled="isOwner"
                                />
                                <div v-if="characterForm.errors.age" class="text-danger small mt-1">
                                    {{ characterForm.errors.age }}
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label" for="characterHeight">Größe (cm)</label>
                                <input
                                    id="characterHeight"
                                    v-model="characterForm.height_cm"
                                    type="number"
                                    min="50"
                                    max="250"
                                    class="form-control"
                                    :disabled="isOwner"
                                />
                                <div v-if="characterForm.errors.height_cm" class="text-danger small mt-1">
                                    {{ characterForm.errors.height_cm }}
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label" for="characterWeight">Gewicht (kg)</label>
                                <input
                                    id="characterWeight"
                                    v-model="characterForm.weight_kg"
                                    type="number"
                                    min="20"
                                    max="300"
                                    class="form-control"
                                    :disabled="isOwner"
                                />
                                <div v-if="characterForm.errors.weight_kg" class="text-danger small mt-1">
                                    {{ characterForm.errors.weight_kg }}
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Charakterzüge (max. 4)</label>
                                <div class="row g-2">
                                    <div
                                        v-for="item in traits"
                                        :key="item"
                                        class="col-6 col-md-4 col-lg-3"
                                    >
                                        <div class="form-check">
                                            <input
                                                :id="`trait-${item}`"
                                                v-model="characterForm.traits"
                                                type="checkbox"
                                                class="form-check-input"
                                                :value="item"
                                                :disabled="isOwner || (!characterForm.traits.includes(item) && characterForm.traits.length >= 4)"
                                            />
                                            <label class="form-check-label" :for="`trait-${item}`">
                                                {{ item }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="characterForm.errors.traits" class="text-danger small mt-1">
                                    {{ characterForm.errors.traits }}
                                </div>
                                <div class="text-muted small mt-1">
                                    Ausgewählt: {{ characterForm.traits.length }} / 4
                                </div>
                            </div>

                            <div class="col-12 col-md-5 d-grid">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary"
                                    :disabled="isOwner"
                                    @click="goToTalentStep"
                                >
                                    Weiter zu Talenten
                                </button>
                            </div>
                            </template>

                            <template v-else>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <label class="form-label m-0">Talente</label>
                                    <span class="badge text-bg-dark">
                                        Verfügbar: {{ remainingTalentPoints }} / {{ maxTalentPoints }}
                                    </span>
                                </div>

                                <div class="col-12">
                                    <div class="row g-3">
                                        <div
                                            v-for="group in talentDefinitions"
                                            :key="group.category"
                                            class="col-12 col-lg-6"
                                        >
                                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                                <div class="fw-semibold small mb-2">{{ group.category }}</div>
                                                <div
                                                    v-for="talent in group.items"
                                                    :key="talent.key"
                                                    class="mb-3"
                                                >
                                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                                        <label class="small m-0" :for="`talent-${talent.key}`">{{ talent.label }}</label>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <button
                                                                :id="`talent-${talent.key}`"
                                                                type="button"
                                                                class="btn btn-outline-secondary btn-sm px-2 py-0"
                                                                :disabled="isOwner || getTalentPoints(talent.key) <= 0"
                                                                @click="decrementTalent(talent.key)"
                                                            >
                                                                -
                                                            </button>
                                                            <span class="small fw-semibold" style="min-width: 2ch; text-align: center;">
                                                                {{ getTalentPoints(talent.key) }}
                                                            </span>
                                                            <button
                                                                type="button"
                                                                class="btn btn-outline-secondary btn-sm px-2 py-0"
                                                                :disabled="isOwner || getTalentPoints(talent.key) >= getTalentMaxPoints(talent.key) || remainingTalentPoints <= 0"
                                                                @click="incrementTalent(talent.key)"
                                                            >
                                                                +
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div v-if="talent.description" class="small text-muted mb-1">
                                                        {{ talent.description }}
                                                    </div>
                                                    <div class="d-flex gap-1 mt-1 flex-wrap">
                                                        <span
                                                            v-for="slot in getTalentMaxPoints(talent.key)"
                                                            :key="`${talent.key}-slot-${slot}`"
                                                            class="rounded-1"
                                                            :class="slot <= getTalentPoints(talent.key) ? 'bg-primary' : 'bg-secondary-subtle border'"
                                                            style="width: 12px; height: 8px;"
                                                        ></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="characterForm.errors.talents" class="text-danger small mt-2">
                                        {{ characterForm.errors.talents }}
                                    </div>
                                    <div
                                        v-for="(error, key) in characterForm.errors"
                                        :key="`talent-error-${key}`"
                                        class="text-danger small mt-1"
                                    >
                                        <span v-if="key.startsWith('talents.')">{{ error }}</span>
                                    </div>
                                </div>

                                <div class="col-12 d-flex gap-2 flex-wrap">
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        @click="characterStep = 1"
                                    >
                                        Zurück
                                    </button>
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        :disabled="isOwner || characterForm.processing"
                                    >
                                        Charakter erstellen
                                    </button>
                                </div>
                            </template>
                        </form>

                        <div class="mt-4">
                            <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                                Charaktere in der Party
                            </div>
                            <div v-if="characters.length === 0" class="text-muted">
                                Noch keine Charaktere erstellt.
                            </div>
                            <ul v-else class="list-group list-group-flush">
                                <li
                                    v-for="entry in characters"
                                    :key="entry.id"
                                    class="list-group-item px-0 d-flex justify-content-between align-items-start gap-3 flex-wrap"
                                >
                                    <div class="d-flex align-items-start gap-3">
                                        <img
                                            v-if="entry.image_url"
                                            :src="entry.image_url"
                                            :alt="`Charakterbild von ${entry.name}`"
                                            class="rounded border"
                                            style="width: 84px; height: 84px; object-fit: cover;"
                                        />
                                        <div>
                                            <div class="fw-semibold">{{ entry.name }}</div>
                                            <div class="text-muted small">
                                                Volk: {{ entry.race }} · Geschlecht: {{ entry.gender }}
                                            </div>
                                            <div class="text-muted small">
                                                Klasse: {{ entry.class_name }}
                                            </div>
                                            <div class="text-muted small">
                                                {{ entry.age }}J · {{ entry.height_cm }}cm · {{ entry.weight_kg }}kg
                                            </div>
                                            <div v-if="canViewCharacterDetails(entry)">
                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                    <span
                                                        v-for="trait in entry.traits"
                                                        :key="trait"
                                                        class="badge text-bg-light border"
                                                    >
                                                        {{ trait }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-muted small">{{ entry.user.name }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
