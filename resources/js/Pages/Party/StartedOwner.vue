<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    party: {
        type: Object,
        required: true,
    },
    characters: {
        type: Array,
        default: () => [],
    },
});

const playerCharacters = computed(() => props.characters ?? []);
const activeCharacterId = ref(playerCharacters.value[0]?.id ?? null);

const activeCharacter = computed(() => {
    return playerCharacters.value.find((entry) => entry.id === activeCharacterId.value) ?? null;
});

const formatTalentKey = (key) => {
    return String(key)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};
</script>

<template>
    <Head :title="`${party.name} - Spielleiter`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="h4 m-0">{{ party.name }} - Spielleiter</h2>
        </template>

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <div class="text-uppercase small text-muted" style="letter-spacing: 2px;">
                    Spielleiter Ansicht
                </div>
                <div class="text-muted">Alle Charaktere deiner Party im Überblick.</div>
            </div>
            <Link
                :href="route('parties.end', party.id)"
                method="post"
                as="button"
                class="btn btn-outline-danger"
            >
                Party beenden
            </Link>
        </div>

        <div v-if="playerCharacters.length === 0" class="alert alert-warning border-0">
            Keine Charaktere gefunden.
        </div>

        <div v-else class="card shadow-sm border-0">
            <div class="card-body p-3 p-md-4">
                <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
                    <li
                        v-for="entry in playerCharacters"
                        :key="entry.id"
                        class="nav-item"
                        role="presentation"
                    >
                        <button
                            type="button"
                            class="nav-link"
                            :class="{ active: activeCharacterId === entry.id }"
                            @click="activeCharacterId = entry.id"
                        >
                            {{ entry.user.name }}
                        </button>
                    </li>
                </ul>

                <div v-if="activeCharacter" class="row g-4">
                    <div class="col-12 col-xl-8">
                        <h4 class="h5 mb-1">{{ activeCharacter.name }}</h4>
                        <div class="text-muted mb-2">
                            {{ activeCharacter.race }} · {{ activeCharacter.class_name }} · {{ activeCharacter.gender }}
                        </div>
                        <div class="text-muted mb-3">
                            {{ activeCharacter.age }} Jahre · {{ activeCharacter.height_cm }} cm · {{ activeCharacter.weight_kg }} kg
                        </div>

                        <div class="mb-3">
                            <div class="small text-uppercase text-muted mb-2" style="letter-spacing: 1px;">Traits</div>
                            <div class="d-flex flex-wrap gap-2">
                                <span
                                    v-for="trait in activeCharacter.traits"
                                    :key="trait"
                                    class="badge text-bg-light border"
                                >
                                    {{ trait }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <div class="small text-uppercase text-muted mb-2" style="letter-spacing: 1px;">Talente</div>
                            <div class="row g-2">
                                <div
                                    v-for="(points, key) in activeCharacter.talents"
                                    :key="key"
                                    class="col-12 col-md-6"
                                >
                                    <div class="d-flex justify-content-between border rounded px-3 py-2 bg-light-subtle">
                                        <span>{{ formatTalentKey(key) }}</span>
                                        <strong>{{ points }}</strong>
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
                        />
                        <div v-else class="text-muted small">
                            Kein Charakterbild verfügbar.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
