<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Welcome" />
    <div class="bg-light min-vh-100 d-flex align-items-center">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h1 class="display-5 fw-bold">Willkommen bei Laravel</h1>
                <p class="lead text-muted">
                    Schnell starten mit Inertia + Vue und Bootstrap.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <Link
                        class="btn btn-outline-dark"
                        :href="route('lore.history')"
                    >
                        Eldoria Chronik
                    </Link>
                    <Link
                        class="btn btn-outline-dark"
                        :href="route('lore.rules')"
                    >
                        Eldoria Regelwerk
                    </Link>
                    <Link
                        v-if="canLogin && $page.props.auth.user"
                        class="btn btn-primary"
                        :href="route('lobby')"
                    >
                        Zur Lobby
                    </Link>
                    <template v-else>
                        <Link
                            v-if="canLogin"
                            class="btn btn-primary"
                            :href="route('login')"
                        >
                            Anmelden
                        </Link>
                        <Link
                            v-if="canRegister"
                            class="btn btn-outline-secondary"
                            :href="route('register')"
                        >
                            Registrieren
                        </Link>
                    </template>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">Dokumentation</h5>
                            <p class="card-text text-muted">
                                Lerne alle Grundlagen und Best Practices von Laravel im offiziellen Handbuch.
                            </p>
                            <a class="btn btn-link px-0" href="https://laravel.com/docs" target="_blank">Zur Doku</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">Laracasts</h5>
                            <p class="card-text text-muted">
                                Video-Tutorials zu Laravel, PHP und JavaScript, um schnell produktiv zu werden.
                            </p>
                            <a class="btn btn-link px-0" href="https://laracasts.com" target="_blank">Ansehen</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">Ecosystem</h5>
                            <p class="card-text text-muted">
                                Forge, Vapor, Nova, Cashier, Horizon und mehr helfen dir beim Ausbau deiner App.
                            </p>
                            <a class="btn btn-link px-0" href="https://laravel.com/ecosystem" target="_blank">
                                Mehr erfahren
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-center text-muted mt-5">
                Laravel v{{ laravelVersion }} &mdash; PHP v{{ phpVersion }}
            </p>
        </div>
    </div>
</template>
