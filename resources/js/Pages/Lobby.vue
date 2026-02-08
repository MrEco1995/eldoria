<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    ownedParties: {
        type: Array,
        default: () => [],
    },
    memberParties: {
        type: Array,
        default: () => [],
    },
    inStartedParty: {
        type: Boolean,
        default: false,
    },
});

</script>

<template>
    <Head title="Lobby" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="h4 m-0">Lobby</h2>
        </template>

        <div class="row position-relative">
            <div
                class="position-absolute top-0 start-50 translate-middle-x"
                style="width: 380px; height: 180px; background: radial-gradient(circle, rgba(77,208,225,0.35), rgba(77,208,225,0)); filter: blur(6px);"
            ></div>
            <div
                class="position-absolute bottom-0 end-0"
                style="width: 260px; height: 260px; background: radial-gradient(circle, rgba(107,92,255,0.25), rgba(107,92,255,0)); filter: blur(8px);"
            ></div>

            <div class="col-12 col-lg-4 position-relative">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                            Dein Bereich
                        </div>
                        <h5 class="card-title mb-2">Willkommen!</h5>
                        <p class="text-muted mb-3">
                            Verwalte deine Partys, sammle Leute und starte dein Abenteuer.
                        </p>
                        <div class="mt-3 d-flex flex-column gap-2">
                            <Link
                                v-if="!inStartedParty"
                                :href="route('parties.create')"
                                class="btn btn-outline-primary btn-sm"
                            >
                                Party erstellen
                            </Link>
                            <div v-else class="text-muted small">
                                Du bist bereits in einer gestarteten Party.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                            Ritual
                        </div>
                        <h6 class="mb-2">Lobby-Checkliste</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge text-bg-light border">1</span>
                                <span>Party erstellen oder beitreten</span>
                            </li>
                            <li class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge text-bg-light border">2</span>
                                <span>Alle bereit markieren</span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="badge text-bg-light border">3</span>
                                <span>Owner startet die Session</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8 position-relative">
                <div class="row">
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-body p-4">
                                <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                                    Owner
                                </div>
                                <h6 class="mb-3">Deine Partys</h6>
                                <div v-if="ownedParties.length === 0" class="text-muted">
                                    Du hast noch keine Partys erstellt.
                                </div>
                                <ul v-else class="list-group list-group-flush">
                                    <li
                                        v-for="party in ownedParties"
                                        :key="party.id"
                                        class="list-group-item px-0 d-flex justify-content-between align-items-center"
                                    >
                                        <span class="fw-semibold">{{ party.name }}</span>
                                        <Link
                                            :href="route('parties.show', party.id)"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                        Öffnen
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-body p-4">
                                <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                                    Mitglied
                                </div>
                                <h6 class="mb-3">Partys, in denen du bist</h6>
                                <div v-if="memberParties.length === 0" class="text-muted">
                                    Du bist aktuell in keiner Party.
                                </div>
                                <ul v-else class="list-group list-group-flush">
                                    <li
                                        v-for="party in memberParties"
                                        :key="party.id"
                                        class="list-group-item px-0 d-flex justify-content-between align-items-center"
                                    >
                                        <span class="fw-semibold">{{ party.name }}</span>
                                        <Link
                                            :href="route('parties.show', party.id)"
                                            class="btn btn-sm btn-outline-secondary"
                                        >
                                            Öffnen
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                            <div>
                                <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                                    Atmosphaere
                                </div>
                                <h5 class="mb-1">Die Lobby lebt</h5>
                                <p class="text-muted mb-0">
                                    Baue Spannung auf, teile den Link und bring alle in Position.
                                </p>
                            </div>
                            <div class="d-flex gap-2">
                                <span class="badge text-bg-primary">Invite</span>
                                <span class="badge text-bg-info">Ready</span>
                                <span class="badge text-bg-dark">Start</span>
                            </div>
                        </div>
                        <div class="progress mt-4" style="height: 6px;">
                            <div
                                class="progress-bar"
                                role="progressbar"
                                style="width: 35%; background: linear-gradient(90deg, #6b5cff, #4dd0e1);"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
