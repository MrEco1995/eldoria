<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    currentUserId: { type: Number, required: true },
});

const loading = ref(false);
const friends = ref([]);
const activeFriendId = ref(null);
const messages = ref([]);
const messageDraft = ref('');
const sending = ref(false);
const minimized = ref(false);
const hidden = ref(false);
const messageListRef = ref(null);

const hasActiveChat = computed(() => activeFriendId.value !== null);
const activeFriend = computed(() => friends.value.find((entry) => Number(entry.id) === Number(activeFriendId.value)) ?? null);

const sortFriends = (entries) => {
    return [...entries].sort((a, b) => {
        const aDate = a.latestMessage?.createdAt ? Date.parse(a.latestMessage.createdAt) : 0;
        const bDate = b.latestMessage?.createdAt ? Date.parse(b.latestMessage.createdAt) : 0;
        return bDate - aDate;
    });
};

const refreshFriends = async () => {
    loading.value = true;
    try {
        const response = await window.axios.get(route('friends.chat.index'));
        friends.value = sortFriends(response?.data?.friends ?? []);
    } catch {
        // ignore
    } finally {
        loading.value = false;
    }
};

const markRead = async (friendId) => {
    try {
        await window.axios.post(route('friends.chat.read', friendId));
    } catch {
        // ignore
    }
};

const scrollToBottom = () => {
    requestAnimationFrame(() => {
        const el = messageListRef.value;
        if (!el) return;
        el.scrollTop = el.scrollHeight;
    });
};

const loadMessages = async (friendId) => {
    try {
        const response = await window.axios.get(route('friends.chat.messages', friendId));
        messages.value = response?.data?.messages ?? [];
        scrollToBottom();
    } catch {
        messages.value = [];
    }
};

const openChat = async (friendId, options = {}) => {
    const forceShow = options.forceShow === true;
    activeFriendId.value = Number(friendId);
    if (forceShow) {
        hidden.value = false;
        minimized.value = false;
    }
    await loadMessages(friendId);
    await markRead(friendId);

    const idx = friends.value.findIndex((entry) => Number(entry.id) === Number(friendId));
    if (idx >= 0) {
        friends.value[idx] = {
            ...friends.value[idx],
            unreadCount: 0,
        };
    }
};

const closeChat = () => {
    hidden.value = true;
};

const toggleMinimize = () => {
    minimized.value = !minimized.value;
};

const sendMessage = async () => {
    const body = (messageDraft.value ?? '').trim();
    if (!body || !activeFriend.value || sending.value) return;
    sending.value = true;
    try {
        const response = await window.axios.post(route('friends.chat.store', activeFriend.value.id), { body });
        const message = response?.data?.message;
        if (message) {
            messages.value.push(message);
            messageDraft.value = '';
            scrollToBottom();

            const idx = friends.value.findIndex((entry) => Number(entry.id) === Number(activeFriend.value.id));
            if (idx >= 0) {
                friends.value[idx] = {
                    ...friends.value[idx],
                    latestMessage: message,
                };
                friends.value = sortFriends(friends.value);
            }
        }
    } catch {
        // ignore
    } finally {
        sending.value = false;
    }
};

const onIncomingMessage = async (event) => {
    const message = event?.message;
    if (!message) return;
    const senderId = Number(message.senderId ?? event?.sender?.id ?? 0);
    if (senderId <= 0) return;

    const idx = friends.value.findIndex((entry) => Number(entry.id) === senderId);
    if (idx >= 0) {
        const currentUnread = Number(friends.value[idx].unreadCount ?? 0);
        friends.value[idx] = {
            ...friends.value[idx],
            latestMessage: message,
            unreadCount: currentUnread + 1,
        };
        friends.value = sortFriends(friends.value);
    } else {
        await refreshFriends();
    }

    if (Number(activeFriendId.value) === senderId) {
        messages.value.push(message);
        hidden.value = false;
        minimized.value = false;
        scrollToBottom();
        await markRead(senderId);
        const friendIdx = friends.value.findIndex((entry) => Number(entry.id) === senderId);
        if (friendIdx >= 0) {
            friends.value[friendIdx] = {
                ...friends.value[friendIdx],
                unreadCount: 0,
            };
        }
        return;
    }

    await openChat(senderId, { forceShow: true });
};

onMounted(async () => {
    await refreshFriends();

    if (!window.Echo || !props.currentUserId) return;
    window.Echo.private(`user.${props.currentUserId}`)
        .listen('.chat.message.created', onIncomingMessage);
});

onBeforeUnmount(() => {
    if (!window.Echo || !props.currentUserId) return;
    window.Echo.leave(`user.${props.currentUserId}`);
});
</script>

<template>
    <aside class="friend-chat-sidebar d-none d-lg-flex flex-column">
        <div class="friend-chat-sidebar-head">
            <div class="small text-uppercase text-muted mb-1">Freunde Chat</div>
            <div class="fw-semibold">Kontakte</div>
        </div>

        <div class="friend-chat-sidebar-list">
            <div v-if="loading" class="small text-muted px-2 py-2">Lade Kontakte...</div>
            <div v-else-if="friends.length === 0" class="small text-muted px-2 py-2">Noch keine Freunde.</div>
            <button
                v-for="friend in friends"
                :key="`chat-friend-${friend.id}`"
                type="button"
                class="friend-chat-list-item btn btn-link text-start text-decoration-none"
                @click="openChat(friend.id, { forceShow: true })"
            >
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <span class="fw-semibold text-dark">{{ friend.name }}</span>
                    <span v-if="Number(friend.unreadCount ?? 0) > 0" class="badge text-bg-danger">{{ friend.unreadCount }}</span>
                </div>
                <div class="small text-muted text-truncate">{{ friend.latestMessage?.body ?? 'Noch keine Nachricht' }}</div>
            </button>
        </div>

        <div
            v-if="hasActiveChat && !hidden"
            class="friend-chat-window card shadow-sm border-0"
        >
            <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
                <strong class="small">{{ activeFriend?.name ?? 'Chat' }}</strong>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" @click="toggleMinimize">
                        {{ minimized ? '+' : '-' }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" @click="closeChat">x</button>
                </div>
            </div>

            <div v-if="!minimized" class="card-body p-2">
                <div ref="messageListRef" class="friend-chat-message-list mb-2">
                    <div
                        v-for="message in messages"
                        :key="`msg-${message.id}`"
                        class="friend-chat-message"
                        :class="Number(message.senderId) === Number(currentUserId) ? 'mine' : 'theirs'"
                    >
                        <div class="friend-chat-bubble">{{ message.body }}</div>
                    </div>
                </div>
                <form class="d-flex gap-1" @submit.prevent="sendMessage">
                    <input
                        v-model="messageDraft"
                        type="text"
                        class="form-control form-control-sm"
                        placeholder="Nachricht..."
                        maxlength="2000"
                    >
                    <button type="submit" class="btn btn-sm btn-primary" :disabled="sending">Senden</button>
                </form>
            </div>
        </div>
    </aside>
</template>

<style scoped>
.friend-chat-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 270px;
    height: 100vh;
    background: linear-gradient(180deg, #ffffff, #f4f7fb);
    border-right: 1px solid #d8dde8;
    z-index: 1100;
    padding: 0.8rem;
}

.friend-chat-sidebar-head {
    padding: 0.25rem 0.2rem 0.5rem;
    border-bottom: 1px solid #e3e8f0;
}

.friend-chat-sidebar-list {
    margin-top: 0.55rem;
    overflow-y: auto;
    flex: 1;
    padding-right: 0.1rem;
}

.friend-chat-list-item {
    width: 100%;
    border: 1px solid #e2e7f0;
    background: #fff;
    border-radius: 10px;
    margin-bottom: 0.45rem;
    padding: 0.45rem 0.55rem;
}

.friend-chat-list-item:hover {
    background: #f7faff;
}

.friend-chat-window {
    position: absolute;
    left: 0.8rem;
    right: 0.8rem;
    bottom: 0.8rem;
    max-height: 56vh;
}

.friend-chat-message-list {
    max-height: 250px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.friend-chat-message {
    display: flex;
}

.friend-chat-message.mine {
    justify-content: flex-end;
}

.friend-chat-message.theirs {
    justify-content: flex-start;
}

.friend-chat-bubble {
    max-width: 80%;
    border-radius: 12px;
    padding: 0.32rem 0.55rem;
    font-size: 0.85rem;
    line-height: 1.25;
    background: #f0f3f9;
    color: #1d2430;
}

.friend-chat-message.mine .friend-chat-bubble {
    background: #d8ebff;
}
</style>
