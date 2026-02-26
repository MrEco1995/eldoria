<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const page = usePage();
const pendingFriendRequestCount = ref(Number(page.props.notifications?.pendingFriendRequests ?? 0));

const syncPendingFriendRequestCount = (nextCount) => {
    pendingFriendRequestCount.value = Math.max(0, Number(nextCount ?? 0));
};

const onFriendRequestCreated = () => {
    pendingFriendRequestCount.value += 1;
};

const onFriendRequestCountSync = (event) => {
    syncPendingFriendRequestCount(event?.detail?.count ?? 0);
};

watch(() => page.props.notifications?.pendingFriendRequests, (nextCount) => {
    syncPendingFriendRequestCount(nextCount ?? 0);
}, { immediate: true });

onMounted(() => {
    const userId = Number(page.props.auth?.user?.id ?? 0);
    if (window.Echo && userId > 0) {
        window.Echo.private(`user.${userId}`)
            .listen('.friend.request.created', onFriendRequestCreated);
    }
    window.addEventListener('friend-requests:count-sync', onFriendRequestCountSync);
});

onBeforeUnmount(() => {
    const userId = Number(page.props.auth?.user?.id ?? 0);
    if (window.Echo && userId > 0) {
        window.Echo.leave(`user.${userId}`);
    }
    window.removeEventListener('friend-requests:count-sync', onFriendRequestCountSync);
});
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

                    <div class="d-flex align-items-center gap-2">
                        <Link :href="route('lobby')" class="btn btn-outline-secondary position-relative" title="Freundschaftsanfragen">
                            <span aria-hidden="true">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2a6 6 0 0 0-6 6v3.3l-1.7 2.9A1 1 0 0 0 5.2 16h13.6a1 1 0 0 0 .9-1.5L18 11.3V8a6 6 0 0 0-6-6Zm0 20a3 3 0 0 0 2.8-2H9.2A3 3 0 0 0 12 22Z"/>
                                </svg>
                            </span>
                            <span
                                v-if="pendingFriendRequestCount > 0"
                                class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"
                                aria-label="Neue Freundschaftsanfrage"
                            ></span>
                        </Link>

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
                            <li>
                                <Link class="dropdown-item" :href="route('lore.history')">Eldoria Chronik</Link>
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
