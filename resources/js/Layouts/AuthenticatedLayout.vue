<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <div class="min-vh-100 bg-light">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container">
                <Link class="navbar-brand d-flex align-items-center" :href="route('lobby')">
                    <ApplicationLogo class="me-2" style="height: 36px; width: auto;" />
                    <span class="fw-semibold">P & P</span>
                </Link>

                <button
                    class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mainNavbar"
                    aria-controls="mainNavbar"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNavbar" style="visibility: visible;">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                       
                    </ul>

                    <div class="dropdown">
                        <button
                            class="btn btn-outline-secondary     dropdown-toggle"
                            type="button"
                            id="userDropdown"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            {{ $page.props.auth.user.name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <Link class="dropdown-item" :href="route('profile.edit')">Profil</Link>
                            </li>
                            <li>
                                <Link class="dropdown-item" :href="route('parties.create')">Party erstellen</Link>
                            </li>
                            <hr></hr>
                            <li>
                                <Link class="dropdown-item" :href="route('logout')" method="post" as="button">
                                    Abmelden
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="bg-white border-bottom py-3">
            <div class="container">
                <slot name="header" />
            </div>
        </header>

        <main class="py-4">
            <div class="container">
                <div v-if="page.props.flash?.error" class="alert alert-danger border-0 mb-3" role="alert">
                    {{ page.props.flash.error }}
                </div>

                <div v-if="page.props.flash?.warning" class="alert alert-warning border-0 mb-3" role="alert">
                    {{ page.props.flash.warning }}
                </div>

                <div v-if="page.props.flash?.info" class="alert alert-info border-0 mb-3" role="status" aria-live="polite">
                    {{ page.props.flash.info }}
                </div>

                <div v-if="page.props.flash?.status" class="alert alert-success border-0 mb-3" role="status" aria-live="polite">
                    {{ page.props.flash.status }}
                </div>

                <slot />
            </div>
        </main>
    </div>
</template>
