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
            <div class="lobby-header">
                <div>
                    <div class="lobby-kicker">Torhalle von Eldoria</div>
                    <h2 class="lobby-title mb-1">Versammlung der Reisenden</h2>
                    <p class="lobby-subtitle mb-0">
                        Sammle Verbündete, öffne deine Gruppe und ziehe weiter in die Grenzlande.
                    </p>
                </div>
                <div ref="searchWrapRef" class="lobby-search-wrap position-relative">
                    <form class="lobby-search-form" @submit.prevent="submitUserSearch">
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="form-control lobby-search-input"
                            placeholder="User suchen (min. 5 Zeichen)..."
                            @focus="onSearchFocus"
                        >
                        <button type="submit" class="btn lobby-search-button" :disabled="!canSubmitUserSearch">Suchen</button>
                        <button type="button" class="btn lobby-reset-button" @click="resetUserSearch">Reset</button>
                    </form>

                    <div
                        v-if="showSearchDropdown"
                        class="lobby-search-dropdown position-absolute start-0 end-0 mt-2"
                    >
                        <div v-if="usersState.length === 0" class="px-3 py-3 text-muted small">
                            Keine User gefunden.
                        </div>
                        <div
                            v-for="entry in usersState"
                            :key="`header-user-${entry.id}`"
                            class="lobby-search-entry"
                        >
                            <div>
                                <div class="fw-semibold text-white">{{ entry.name }}</div>
                                <div class="small text-white-50">{{ entry.email }}</div>
                            </div>
                            <div class="d-inline-flex align-items-center">
                                <button
                                    v-if="!entry.relationshipStatus"
                                    type="button"
                                    class="btn btn-sm btn-link text-decoration-none text-warning"
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
                                    class="small text-white-50"
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

        <section class="lobby-scene">
            <div class="lobby-glow lobby-glow-cyan"></div>
            <div class="lobby-glow lobby-glow-gold"></div>

            <div class="row g-4 position-relative">
                <div class="col-12 col-xl-4">
                    <div class="lobby-card lobby-card-hero mb-4">
                        <div class="lobby-card-body">
                            <div class="lobby-kicker">Reisendenhalle</div>
                            <h5 class="card-title mb-2 text-white">Dein Weg beginnt hier</h5>
                            <p class="lobby-copy mb-4">
                                Führe eine Gruppe durch Eldoria oder schließe dich einer bestehenden Schar an.
                            </p>
                            <div class="d-flex flex-column gap-2">
                                <Link
                                    v-if="!inStartedParty"
                                    :href="route('parties.create')"
                                    class="btn lobby-primary-button"
                                >
                                    Neue Party gründen
                                </Link>
                                <Link
                                    :href="route('lore.history')"
                                    class="btn lobby-primary-button"
                                >
                                    Chronik von Eldoria
                                </Link>
                                <div v-if="inStartedParty" class="lobby-note small">
                                    Du bist bereits Teil einer gestarteten Gruppe.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lobby-card mb-4">
                        <div class="lobby-card-body">
                            <div class="lobby-kicker">Botenpfad</div>
                            <h6 class="mb-3 text-white">Kontakte und Gefährten</h6>
                            <div v-if="friendsState.length === 0" class="lobby-muted">
                                Noch keine vertrauten Namen im Botenbuch.
                            </div>
                            <ul v-else class="list-unstyled mb-0 d-flex flex-column gap-2">
                                <li
                                    v-for="friend in friendsState"
                                    :key="`friend-left-${friend.id}`"
                                    class="lobby-list-item"
                                >
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold d-inline-flex align-items-center gap-2 text-white">
                                            <span
                                                class="lobby-online-dot"
                                                :class="isFriendOnline(friend.id) ? 'is-online' : 'is-offline'"
                                            ></span>
                                            {{ friend.name }}
                                        </span>
                                        <span class="lobby-muted small">{{ friend.email }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-sm lobby-danger-button"
                                        :disabled="removingFriendshipIds[String(friend.friendshipId)] === true"
                                        @click="removeFriend(friend)"
                                    >
                                        Entfolgen
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="lobby-card">
                        <div class="lobby-card-body">
                            <div class="lobby-kicker">Sternenruf</div>
                            <h6 class="mb-3 text-white">Offene Freundschaftsanfragen</h6>
                            <div v-if="pendingFriendRequestsState.length === 0" class="lobby-muted small">
                                Keine Boten warten auf deine Antwort.
                            </div>
                            <ul v-else class="list-unstyled mb-0 d-flex flex-column gap-2">
                                <li
                                    v-for="requestEntry in pendingFriendRequestsState"
                                    :key="`friend-request-${requestEntry.id}`"
                                    class="lobby-list-item align-items-start"
                                >
                                    <div>
                                        <div class="fw-semibold text-white">{{ requestEntry.requester?.name ?? 'Unbekannt' }}</div>
                                        <div class="lobby-muted small">{{ requestEntry.requester?.email }}</div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button
                                            type="button"
                                            class="btn btn-sm lobby-success-button"
                                            :disabled="processingRequestIds[String(requestEntry.id)] === true"
                                            @click="handleIncomingFriendRequest(requestEntry.id, 'accept')"
                                        >
                                            Annehmen
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm lobby-danger-button"
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

                <div class="col-12 col-xl-8">
                    <div class="lobby-banner mb-4">
                        <div>
                            <div class="lobby-kicker">Schwarzes Brett</div>
                            <h4 class="mb-2 text-white">Die Grenzlande sind unruhig</h4>
                            <p class="lobby-copy mb-0">
                                Forme eine Reisegruppe, sammle vertraute Gesichter und halte deine Truppe bereit für den Aufbruch.
                            </p>
                        </div>
                        <div class="lobby-banner-seals">
                            <span class="lobby-seal">Gruppen</span>
                            <span class="lobby-seal">Gefährten</span>
                            <span class="lobby-seal">Aufbruch</span>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <div class="lobby-card h-100">
                                <div class="lobby-card-body">
                                    <div class="lobby-kicker">Meisterzirkel</div>
                                    <h6 class="mb-3 text-white">Deine Partys</h6>
                                    <div v-if="ownedParties.length === 0" class="lobby-muted">
                                        Du hast noch keine eigene Gruppe gegründet.
                                    </div>
                                    <ul v-else class="list-unstyled mb-0 d-flex flex-column gap-2">
                                        <li
                                            v-for="party in ownedParties"
                                            :key="party.id"
                                            class="lobby-list-item"
                                        >
                                            <div>
                                                <div class="fw-semibold text-white">{{ party.name }}</div>
                                                <div class="lobby-muted small">Du führst diese Reisegruppe.</div>
                                            </div>
                                            <Link
                                                :href="route('parties.show', party.id)"
                                                class="btn btn-sm lobby-primary-button"
                                            >
                                                Öffnen
                                            </Link>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="lobby-card h-100">
                                <div class="lobby-card-body">
                                    <div class="lobby-kicker">Lagerfeuer</div>
                                    <h6 class="mb-3 text-white">Partys, in denen du bist</h6>
                                    <div v-if="memberParties.length === 0" class="lobby-muted">
                                        Du rastest derzeit in keiner fremden Gruppe.
                                    </div>
                                    <ul v-else class="list-unstyled mb-0 d-flex flex-column gap-2">
                                        <li
                                            v-for="party in memberParties"
                                            :key="party.id"
                                            class="lobby-list-item"
                                        >
                                            <div>
                                                <div class="fw-semibold text-white">{{ party.name }}</div>
                                                <div class="lobby-muted small">Du reist als Mitglied mit.</div>
                                            </div>
                                            <Link
                                                :href="route('parties.show', party.id)"
                                                class="btn btn-sm lobby-primary-button"
                                            >
                                                Öffnen
                                            </Link>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="lobby-card lobby-card-wide">
                                <div class="lobby-card-body">
                                    <div class="lobby-kicker">Wegweiser</div>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-4">
                                            <div class="lobby-feature">
                                                <div class="lobby-feature-title">Gefährten finden</div>
                                                <p class="mb-0 lobby-muted">
                                                    Suche im Header nach Reisenden und sende direkt eine Anfrage.
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div class="lobby-feature">
                                                <div class="lobby-feature-title">Gruppen ordnen</div>
                                                <p class="mb-0 lobby-muted">
                                                    Halte eigene Gruppen und Einladungen sauber getrennt und schnell erreichbar.
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div class="lobby-feature">
                                                <div class="lobby-feature-title">Direkter Draht</div>
                                                <p class="mb-0 lobby-muted">
                                                    Deine Freunde, Chats und Anfragen bleiben live mit der Glocke und dem Seitenchat verbunden.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>

<style scoped>
.lobby-scene {
    --lobby-bg: linear-gradient(180deg, #07131b 0%, #102330 46%, #172b2f 100%);
    --lobby-card-bg: linear-gradient(180deg, rgba(11, 23, 32, 0.94), rgba(16, 31, 42, 0.92));
    --lobby-border: rgba(155, 208, 186, 0.16);
    --lobby-gold: #d6b26e;
    --lobby-text: #eef4ea;
    --lobby-muted: rgba(221, 231, 223, 0.72);
    position: relative;
    overflow: hidden;
    padding: 1.25rem;
    border-radius: 28px;
    background:
        radial-gradient(circle at top left, rgba(123, 216, 208, 0.16), transparent 28%),
        radial-gradient(circle at bottom right, rgba(214, 178, 110, 0.12), transparent 34%),
        var(--lobby-bg);
    border: 1px solid rgba(255, 255, 255, 0.04);
    box-shadow: 0 24px 60px rgba(1, 8, 14, 0.35);
}

.lobby-glow {
    position: absolute;
    border-radius: 999px;
    filter: blur(16px);
    pointer-events: none;
}

.lobby-glow-cyan {
    top: -40px;
    left: 20%;
    width: 240px;
    height: 180px;
    background: radial-gradient(circle, rgba(90, 214, 206, 0.28), transparent 72%);
}

.lobby-glow-gold {
    right: 3%;
    bottom: -50px;
    width: 280px;
    height: 220px;
    background: radial-gradient(circle, rgba(214, 178, 110, 0.18), transparent 72%);
}

.lobby-header {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.lobby-kicker {
    color: var(--lobby-gold);
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-size: 0.72rem;
    font-weight: 700;
}

.lobby-title {
    color: #12242d;
    font-size: clamp(1.7rem, 3vw, 2.4rem);
    font-weight: 700;
}

.lobby-subtitle {
    color: #4a5f67;
    max-width: 760px;
}

.lobby-search-wrap {
    width: min(620px, 100%);
}

.lobby-search-form {
    display: flex;
    gap: 0.75rem;
}

.lobby-search-input {
    min-height: 48px;
    border-radius: 999px;
    border: 1px solid rgba(29, 65, 67, 0.16);
    padding-inline: 1rem;
    box-shadow: 0 8px 24px rgba(18, 34, 41, 0.08);
}

.lobby-search-button,
.lobby-reset-button,
.lobby-primary-button,
.lobby-secondary-button,
.lobby-success-button,
.lobby-danger-button {
    border-radius: 999px;
    border: 1px solid transparent;
    transition: 180ms ease;
}

.lobby-search-button,
.lobby-primary-button {
    background: linear-gradient(135deg, #d6b26e, #b98a43);
    color: #112129;
    font-weight: 700;
}

.lobby-reset-button,
.lobby-secondary-button {
    background: rgba(12, 32, 40, 0.06);
    border-color: rgba(18, 44, 49, 0.16);
    color: #17303a;
}

.lobby-success-button {
    background: rgba(72, 156, 121, 0.14);
    border-color: rgba(72, 156, 121, 0.28);
    color: #b6efd4;
}

.lobby-danger-button {
    background: rgba(160, 72, 72, 0.14);
    border-color: rgba(160, 72, 72, 0.28);
    color: #f0b8b8;
}

.lobby-search-button:hover,
.lobby-reset-button:hover,
.lobby-primary-button:hover,
.lobby-secondary-button:hover,
.lobby-success-button:hover,
.lobby-danger-button:hover {
    transform: translateY(-1px);
}

.lobby-search-dropdown {
    z-index: 30;
    max-height: 360px;
    overflow-y: auto;
    border-radius: 24px;
    border: 1px solid rgba(130, 193, 189, 0.18);
    background: linear-gradient(180deg, rgba(10, 20, 28, 0.98), rgba(16, 30, 41, 0.96));
    box-shadow: 0 22px 48px rgba(4, 10, 16, 0.42);
}

.lobby-search-entry {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.9rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.lobby-search-entry:last-child {
    border-bottom: 0;
}

.lobby-card,
.lobby-banner {
    position: relative;
    overflow: hidden;
    border-radius: 26px;
    border: 1px solid var(--lobby-border);
    background: var(--lobby-card-bg);
    box-shadow: 0 18px 42px rgba(2, 8, 15, 0.24);
}

.lobby-card-hero {
    background:
        linear-gradient(180deg, rgba(18, 35, 46, 0.96), rgba(16, 26, 34, 0.94)),
        linear-gradient(135deg, rgba(123, 216, 208, 0.16), transparent 40%);
}

.lobby-card-wide {
    background:
        linear-gradient(180deg, rgba(11, 25, 34, 0.94), rgba(18, 31, 42, 0.92)),
        radial-gradient(circle at center, rgba(214, 178, 110, 0.08), transparent 60%);
}

.lobby-card-body {
    padding: 1.4rem;
}

.lobby-copy,
.lobby-muted,
.lobby-note {
    color: var(--lobby-muted);
}

.lobby-banner {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 1rem;
    padding: 1.5rem;
    background:
        linear-gradient(120deg, rgba(17, 39, 52, 0.96), rgba(14, 24, 33, 0.94)),
        radial-gradient(circle at top right, rgba(123, 216, 208, 0.15), transparent 26%);
}

.lobby-banner-seals {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.lobby-seal {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: 0 0.9rem;
    border-radius: 999px;
    background: rgba(214, 178, 110, 0.12);
    border: 1px solid rgba(214, 178, 110, 0.24);
    color: #f0dfb9;
    font-size: 0.83rem;
    font-weight: 600;
}

.lobby-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 0.9rem 1rem;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.035);
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.lobby-online-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    display: inline-block;
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.04);
}

.lobby-online-dot.is-online {
    background: #55d48b;
    box-shadow: 0 0 12px rgba(85, 212, 139, 0.5);
}

.lobby-online-dot.is-offline {
    background: #65757d;
}

.lobby-feature {
    height: 100%;
    padding: 1rem;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.lobby-feature-title {
    margin-bottom: 0.45rem;
    color: var(--lobby-text);
    font-weight: 700;
}

@media (max-width: 991.98px) {
    .lobby-scene {
        padding: 1rem;
        border-radius: 20px;
    }

    .lobby-search-form {
        flex-wrap: wrap;
    }

    .lobby-banner {
        flex-direction: column;
        align-items: flex-start;
    }

    .lobby-list-item {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
