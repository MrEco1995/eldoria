<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
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
    userSearch: {
        type: String,
        default: '',
    },
    users: {
        type: Array,
        default: () => [],
    },
    pendingFriendRequests: {
        type: Array,
        default: () => [],
    },
    friends: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref(props.userSearch ?? '');
const usersState = ref([...(props.users ?? [])]);
const pendingFriendRequestsState = ref([...(props.pendingFriendRequests ?? [])]);
const friendsState = ref([...(props.friends ?? [])]);
const sendingRequestUserIds = ref({});
const processingRequestIds = ref({});
const onlineUserMap = ref({});
const canSubmitUserSearch = computed(() => (searchQuery.value ?? '').trim().length >= 5);
const hasActiveSearch = computed(() => (props.userSearch ?? '').trim().length >= 5);

const submitUserSearch = () => {
    const value = (searchQuery.value ?? '').trim();
    if (value.length < 5) return;
    router.get(route('lobby'), {
        user_search: value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetUserSearch = () => {
    searchQuery.value = '';
    router.get(route('lobby'), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const emitPendingCount = () => {
    window.dispatchEvent(new CustomEvent('friend-requests:count-sync', {
        detail: { count: pendingFriendRequestsState.value.length },
    }));
};

const markUserRelationship = (userId, relationshipStatus) => {
    const idx = usersState.value.findIndex((entry) => Number(entry.id) === Number(userId));
    if (idx < 0) return;
    usersState.value[idx] = {
        ...usersState.value[idx],
        relationshipStatus,
    };
};

const sendFriendRequest = async (userId) => {
    if (sendingRequestUserIds.value[String(userId)]) return;
    sendingRequestUserIds.value[String(userId)] = true;
    try {
        await window.axios.post(route('friends.requests.store'), {
            recipient_user_id: Number(userId),
        });
        markUserRelationship(userId, 'outgoing_pending');
    } catch {
        // ignore
    } finally {
        sendingRequestUserIds.value[String(userId)] = false;
    }
};

const handleIncomingFriendRequest = async (requestId, action) => {
    if (processingRequestIds.value[String(requestId)]) return;
    processingRequestIds.value[String(requestId)] = true;
    try {
        const response = await window.axios.post(route(
            action === 'accept' ? 'friends.requests.accept' : 'friends.requests.reject',
            requestId,
        ));

        const requestEntry = pendingFriendRequestsState.value.find((entry) => Number(entry.id) === Number(requestId));
        pendingFriendRequestsState.value = pendingFriendRequestsState.value.filter((entry) => Number(entry.id) !== Number(requestId));
        emitPendingCount();

        if (requestEntry?.requester?.id) {
            markUserRelationship(requestEntry.requester.id, action === 'accept' ? 'accepted' : null);
        }

        if (action === 'accept' && response?.data?.friend?.id) {
            const exists = friendsState.value.some((entry) => Number(entry.id) === Number(response.data.friend.id));
            if (!exists) {
                friendsState.value.unshift(response.data.friend);
            }
        }
    } catch {
        // ignore
    } finally {
        processingRequestIds.value[String(requestId)] = false;
    }
};

const isFriendOnline = (friendId) => Boolean(onlineUserMap.value[String(friendId)]);
const setOnlineUsers = (users) => {
    const next = {};
    (users ?? []).forEach((entry) => {
        const id = Number(entry?.id ?? 0);
        if (id > 0) next[String(id)] = true;
    });
    onlineUserMap.value = next;
};

const addOnlineUser = (user) => {
    const id = Number(user?.id ?? 0);
    if (id <= 0) return;
    onlineUserMap.value = {
        ...onlineUserMap.value,
        [String(id)]: true,
    };
};

const removeOnlineUser = (user) => {
    const id = Number(user?.id ?? 0);
    if (id <= 0) return;
    const next = { ...onlineUserMap.value };
    delete next[String(id)];
    onlineUserMap.value = next;
};

watch(() => props.userSearch, (next) => {
    searchQuery.value = next ?? '';
}, { immediate: true });

watch(() => props.users, (next) => {
    usersState.value = [...(next ?? [])];
}, { immediate: true });

watch(() => props.pendingFriendRequests, (next) => {
    pendingFriendRequestsState.value = [...(next ?? [])];
    emitPendingCount();
}, { immediate: true });

watch(() => props.friends, (next) => {
    friendsState.value = [...(next ?? [])];
}, { immediate: true });

onMounted(() => {
    if (!window.Echo) return;
    window.Echo.join('online')
        .here((users) => setOnlineUsers(users))
        .joining((user) => addOnlineUser(user))
        .leaving((user) => removeOnlineUser(user));
});

onBeforeUnmount(() => {
    if (!window.Echo) return;
    window.Echo.leave('online');
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
                            <Link
                                :href="route('lore.history')"
                                class="btn btn-outline-dark btn-sm"
                            >
                                Eldoria Chronik lesen
                            </Link>
                            <div v-if="inStartedParty" class="text-muted small">
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

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                            Kontakte
                        </div>
                        <h6 class="mb-3">Befreundete Spieler</h6>
                        <div v-if="friendsState.length === 0" class="text-muted">
                            Du hast noch keine Freunde.
                        </div>
                        <ul v-else class="list-group list-group-flush">
                            <li
                                v-for="friend in friendsState"
                                :key="`friend-left-${friend.id}`"
                                class="list-group-item px-0 d-flex justify-content-between align-items-center"
                            >
                                <span class="fw-semibold d-inline-flex align-items-center gap-2">
                                    <span
                                        class="rounded-circle d-inline-block"
                                        :class="isFriendOnline(friend.id) ? 'bg-success' : 'bg-secondary'"
                                        style="width: 10px; height: 10px;"
                                    ></span>
                                    {{ friend.name }}
                                </span>
                                <span class="text-muted small">{{ friend.email }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8 position-relative">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                            Nutzer
                        </div>
                        <h6 class="mb-3">User-Suche</h6>
                        <form class="row g-2 mb-3" @submit.prevent="submitUserSearch">
                            <div class="col-12 col-md-8">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    class="form-control"
                                    placeholder="Nach Name oder E-Mail suchen..."
                                >
                            </div>
                            <div class="col-6 col-md-2">
                                <button type="submit" class="btn btn-primary w-100" :disabled="!canSubmitUserSearch">Suchen</button>
                            </div>
                            <div class="col-6 col-md-2">
                                <button type="button" class="btn btn-outline-secondary w-100" @click="resetUserSearch">Reset</button>
                            </div>
                        </form>

                        <div v-if="!hasActiveSearch" class="text-muted small">
                            Gib mindestens 5 Zeichen ein und starte dann die Suche.
                        </div>
                        <div v-else-if="usersState.length === 0" class="text-muted small">Keine User gefunden.</div>
                        <ul v-else class="list-group list-group-flush">
                            <li
                                v-for="entry in usersState"
                                :key="`lobby-user-${entry.id}`"
                                class="list-group-item px-0 d-flex justify-content-between align-items-center"
                            >
                                <div class="d-flex flex-column">
                                    <span class="d-inline-flex align-items-center gap-2 fw-semibold">
                                        <span aria-hidden="true">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 2a6 6 0 0 0-6 6v3.3l-1.7 2.9A1 1 0 0 0 5.2 16h13.6a1 1 0 0 0 .9-1.5L18 11.3V8a6 6 0 0 0-6-6Zm0 20a3 3 0 0 0 2.8-2H9.2A3 3 0 0 0 12 22Z" />
                                            </svg>
                                        </span>
                                        {{ entry.name }}
                                    </span>
                                    <span class="text-muted small">{{ entry.email }}</span>
                                </div>
                                <div>
                                    <button
                                        v-if="!entry.relationshipStatus"
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        :disabled="sendingRequestUserIds[String(entry.id)] === true"
                                        @click="sendFriendRequest(entry.id)"
                                    >
                                        Anfrage senden
                                    </button>
                                    <span v-else-if="entry.relationshipStatus === 'accepted'" class="badge text-bg-success">Befreundet</span>
                                    <span v-else-if="entry.relationshipStatus === 'outgoing_pending'" class="badge text-bg-secondary">Anfrage gesendet</span>
                                    <span v-else-if="entry.relationshipStatus === 'incoming_pending'" class="badge text-bg-warning">Hat dir angefragt</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-body p-4">
                                <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                                    Freundschaft
                                </div>
                                <h6 class="mb-3">Eingehende Anfragen</h6>
                                <div v-if="pendingFriendRequestsState.length === 0" class="text-muted small">
                                    Keine offenen Freundschaftsanfragen.
                                </div>
                                <ul v-else class="list-group list-group-flush">
                                    <li
                                        v-for="requestEntry in pendingFriendRequestsState"
                                        :key="`friend-request-${requestEntry.id}`"
                                        class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2"
                                    >
                                        <div>
                                            <div class="fw-semibold">{{ requestEntry.requester?.name ?? 'Unbekannt' }}</div>
                                            <div class="text-muted small">{{ requestEntry.requester?.email }}</div>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-success"
                                                :disabled="processingRequestIds[String(requestEntry.id)] === true"
                                                @click="handleIncomingFriendRequest(requestEntry.id, 'accept')"
                                            >
                                                Annehmen
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                :disabled="processingRequestIds[String(requestEntry.id)] === true"
                                                @click="handleIncomingFriendRequest(requestEntry.id, 'reject')"
                                            >
                                                Ablehnen
                                            </button>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                    </div>

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
                                            Oeffnen
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                        </div>

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
                                            Oeffnen
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
