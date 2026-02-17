<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    party: {
        type: Object,
        required: true,
    },
    character: {
        type: Object,
        required: true,
    },
});

const formatTalentKey = (key) => {
    return String(key)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};
</script>

<template>
    <Head :title="`${party.name} - Charakter`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="h4 m-0">{{ party.name }} - Dein Charakter</h2>
        </template>

        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                            Charakterbogen
                        </div>
                        <h3 class="h4 mb-3">{{ character.name }}</h3>

                        <div class="text-muted mb-3">
                            {{ character.race }} · {{ character.class_name }} · {{ character.gender }}
                        </div>
                        <div class="text-muted mb-4">
                            {{ character.age }} Jahre · {{ character.height_cm }} cm · {{ character.weight_kg }} kg
                        </div>

                        <div class="mb-4">
                            <div class="small text-uppercase text-muted mb-2" style="letter-spacing: 1px;">Traits</div>
                            <div class="d-flex flex-wrap gap-2">
                                <span
                                    v-for="trait in character.traits"
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
                                    v-for="(points, key) in character.talents"
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
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-3">
                        <img
                            v-if="character.image_url"
                            :src="character.image_url"
                            :alt="`Charakterbild von ${character.name}`"
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
