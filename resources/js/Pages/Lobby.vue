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
const removingFriendshipIds = ref({});
const onlineUserMap = ref({});
const canSubmitUserSearch = computed(() => (searchQuery.value ?? '').trim().length >= 5);
const hasActiveSearch = computed(() => (props.userSearch ?? '').trim().length >= 5);
const searchDropdownOpen = ref(false);
const searchWrapRef = ref(null);
const showSearchDropdown = computed(() => hasActiveSearch.value && searchDropdownOpen.value);

const submitUserSearch = () => {
    const value = (searchQuery.value ?? '').trim();
    if (value.length < 5) return;
    searchDropdownOpen.value = true;
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
    searchDropdownOpen.value = false;
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

const removeFriend = async (friend) => {
    const friendshipId = Number(friend?.friendshipId ?? 0);
    if (friendshipId <= 0 || removingFriendshipIds.value[String(friendshipId)]) return;

    removingFriendshipIds.value[String(friendshipId)] = true;
    try {
        const response = await window.axios.post(route('friends.remove', friendshipId));
        const removedFriendId = Number(response?.data?.friendId ?? friend?.id ?? 0);

        friendsState.value = friendsState.value.filter((entry) => Number(entry.friendshipId) !== friendshipId);
        if (removedFriendId > 0) {
            markUserRelationship(removedFriendId, null);
        }
    } catch {
        // ignore
    } finally {
        removingFriendshipIds.value[String(friendshipId)] = false;
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

const onSearchFocus = () => {
    if (hasActiveSearch.value) {
        searchDropdownOpen.value = true;
    }
};

const onClickOutside = (event) => {
    if (!searchWrapRef.value) return;
    if (searchWrapRef.value.contains(event.target)) return;
    searchDropdownOpen.value = false;
};

watch(() => props.userSearch, (next) => {
    searchQuery.value = next ?? '';
}, { immediate: true });

watch(() => props.users, (next) => {
    usersState.value = [...(next ?? [])];
    if (hasActiveSearch.value) {
        searchDropdownOpen.value = true;
    }
}, { immediate: true });

watch(() => props.pendingFriendRequests, (next) => {
    pendingFriendRequestsState.value = [...(next ?? [])];
    emitPendingCount();
}, { immediate: true });

watch(() => props.friends, (next) => {
    friendsState.value = [...(next ?? [])];
}, { immediate: true });

onMounted(() => {
    document.addEventListener('click', onClickOutside);
    if (!window.Echo) return;
    window.Echo.join('online')
        .here((users) => setOnlineUsers(users))
        .joining((user) => addOnlineUser(user))
        .leaving((user) => removeOnlineUser(user));
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside);
    if (!window.Echo) return;
    window.Echo.leave('online');
});
</script>

<template>
    <Head title="Lobby" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                <h2 class="h4 m-0">Lobby</h2>
                <div ref="searchWrapRef" class="position-relative" style="width: min(560px, 100%);">
                    <form class="d-flex gap-2" @submit.prevent="submitUserSearch">
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="form-control"
                            placeholder="User suchen (min. 5 Zeichen)..."
                            @focus="onSearchFocus"
                        >
                        <button type="submit" class="btn btn-primary" :disabled="!canSubmitUserSearch">Suchen</button>
                        <button type="button" class="btn btn-outline-secondary" @click="resetUserSearch">Reset</button>
                    </form>

                    <div
                        v-if="showSearchDropdown"
                        class="position-absolute start-0 end-0 mt-1 bg-white border rounded shadow-sm"
                        style="z-index: 30; max-height: 360px; overflow-y: auto;"
                    >
                        <div v-if="usersState.length === 0" class="px-3 py-2 text-muted small">
                            Keine User gefunden.
                        </div>
                        <div
                            v-for="entry in usersState"
                            :key="`header-user-${entry.id}`"
                            class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center"
                        >
                            <div>
                                <div class="fw-semibold">{{ entry.name }}</div>
                                <div class="small text-muted">{{ entry.email }}</div>
                            </div>
                            <div class="d-inline-flex align-items-center">
                                <button
                                    v-if="!entry.relationshipStatus"
                                    type="button"
                                    class="btn btn-sm btn-link text-decoration-none"
                                    :disabled="sendingRequestUserIds[String(entry.id)] === true"
                                    @click="sendFriendRequest(entry.id)"
                                    title="Freundschaftsanfrage senden"
                                >
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm0 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14Zm0 2.4a2.4 2.4 0 1 0 0 4.8 2.4 2.4 0 0 0 0-4.8Zm-3.2 7.9c.8-1 2-1.6 3.2-1.6s2.4.6 3.2 1.6v.7h-6.4v-.7Zm9.2-6.2h1.3v2h2v1.3h-2v2H18v-2h-2v-1.3h2v-2Z" />
                                    </svg>
                                </button>
                                <span
                                    v-else-if="entry.relationshipStatus === 'accepted'"
                                    class="text-success"
                                    title="Befreundet"
                                >
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm0 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14Zm0 2.4a2.4 2.4 0 1 0 0 4.8 2.4 2.4 0 0 0 0-4.8Zm-3.2 7.9c.8-1 2-1.6 3.2-1.6s2.4.6 3.2 1.6v.7h-6.4v-.7Zm9.5-2.1 1.2 1.2 2.5-2.5 1.2 1.2-3.7 3.7-2.4-2.4 1.2-1.2Z" />
                                    </svg>
                                </span>
                                <span
                                    v-else
                                    class="small text-muted"
                                    :title="entry.relationshipStatus === 'outgoing_pending' ? 'Anfrage gesendet' : 'Hat dir angefragt'"
                                >
                                    ...
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold d-inline-flex align-items-center gap-2">
                                        <span
                                            class="rounded-circle d-inline-block"
                                            :class="isFriendOnline(friend.id) ? 'bg-success' : 'bg-secondary'"
                                            style="width: 10px; height: 10px;"
                                        ></span>
                                        {{ friend.name }}
                                    </span>
                                    <span class="text-muted small">{{ friend.email }}</span>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    :disabled="removingFriendshipIds[String(friend.friendshipId)] === true"
                                    @click="removeFriend(friend)"
                                >
                                    Entfolgen
                                </button>
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
