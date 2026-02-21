<script setup>
import DiceRoller from '@/Components/DiceRoller.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    party: { type: Object, required: true },
    character: { type: Object, required: true },
    characters: { type: Array, default: () => [] },
    talentDefinitions: { type: Array, default: () => [] },
    talentRequests: { type: Array, default: () => [] },
    tradeSessions: { type: Array, default: () => [] },
    npcTradeOffer: { type: Object, default: null },
});

const requestState = ref([...(props.talentRequests ?? [])]);
const rollingKeys = ref({});
const activeTab = ref('character');
const usePreviewFallback = ref(false);
const inventoryItems = ref([...(props.character?.inventoryItems ?? [])]);
const noteDraftByItemId = ref({});
const noteEditorOpenByItemId = ref({});
const unseenInventoryItemIds = ref({});
const inventoryBusy = ref(false);
const inventoryActionHint = ref('');
const walletModalOpen = ref(false);
const npcTradeModalOpen = ref(false);
const npcTradeBusy = ref(false);
const npcBuyQuantityByItemId = ref({});
const npcSellForm = ref({
    inventoryItemId: null,
    quantity: 1,
    amountGold: 0,
    amountSilver: 0,
    amountCopper: 1,
});
const npcTradeState = ref(props.npcTradeOffer ? { ...props.npcTradeOffer } : null);
const tradePickerOpen = ref(false);
const tradeBusy = ref(false);
const activeTradeModalOpen = ref(false);
const selectedTradeTargetCharacterId = ref(null);
const tradeSessionState = ref([...(props.tradeSessions ?? [])]);

const raceImageBaseMap = {
    Menschen: 'Mensch',
    Elfen: 'Elf',
    Zwerge: 'Zwerg',
    Orks: 'Ork',
    Faelun: 'Faelun',
    Noctyr: 'Noctyr',
    Tharokh: 'Tharokh',
};

const genderImageSuffixMap = {
    Männlich: 'Man',
    Weiblich: 'Woman',
};

watch(() => props.talentRequests, (next) => {
    requestState.value = [...(next ?? [])];
}, { immediate: true });

watch(() => props.character?.inventoryItems, (next) => {
    inventoryItems.value = [...(next ?? [])];
}, { immediate: true });

watch(() => props.tradeSessions, (next) => {
    tradeSessionState.value = [...(next ?? [])];
}, { immediate: true });

watch(() => props.npcTradeOffer, (nextOffer) => {
    npcTradeState.value = nextOffer ? { ...nextOffer } : null;
}, { immediate: true });

const getTalentValue = (key) => Number(props.character?.talents?.[key] ?? 0);

const talentGroups = computed(() => {
    const groups = new Map();
    (props.talentDefinitions ?? []).forEach((talent) => {
        const category = talent.category || 'Sonstige Talente';
        if (!groups.has(category)) groups.set(category, []);
        groups.get(category).push(talent);
    });
    return Array.from(groups.entries()).map(([category, items]) => ({ category, items }));
});

const myRequests = computed(() => {
    return requestState.value
        .filter((request) => Number(request.targetUserId) === Number(props.character.user_id))
        .sort((a, b) => Number(b.id) - Number(a.id));
});

const latestMyRequest = computed(() => myRequests.value[0] ?? null);
const hasUnseenInventoryItems = computed(() => {
    return Object.values(unseenInventoryItemIds.value).some((value) => value === true);
});
const walletTypeLabels = {
    in: 'IN',
    out: 'OUT',
};
const walletTypeBadges = {
    in: 'text-bg-success',
    out: 'text-bg-danger',
};
const walletState = ref({ ...(props.character?.wallet ?? { transactions: [] }) });
const wallet = computed(() => walletState.value ?? null);
const walletTransactions = ref([...(walletState.value?.transactions ?? [])]);
const currentCharacterId = computed(() => Number(props.character?.id ?? 0));
const allCharacters = computed(() => props.characters ?? []);

const availableTradeTargets = computed(() => {
    return allCharacters.value.filter((entry) => Number(entry.id) !== currentCharacterId.value);
});

const incomingPendingTrades = computed(() => {
    return tradeSessionState.value.filter((session) => (
        session.status === 'pending'
        && Number(session.counterpartyPartyCharacterId) === currentCharacterId.value
    ));
});

const outgoingPendingTrades = computed(() => {
    return tradeSessionState.value.filter((session) => (
        session.status === 'pending'
        && Number(session.initiatorPartyCharacterId) === currentCharacterId.value
    ));
});

const activeTrade = computed(() => {
    return tradeSessionState.value.find((session) => (
        session.status === 'active'
        && (
            Number(session.initiatorPartyCharacterId) === currentCharacterId.value
            || Number(session.counterpartyPartyCharacterId) === currentCharacterId.value
        )
    )) ?? null;
});

const tradePartnerCharacterId = computed(() => {
    if (!activeTrade.value) return null;
    if (Number(activeTrade.value.initiatorPartyCharacterId) === currentCharacterId.value) {
        return Number(activeTrade.value.counterpartyPartyCharacterId);
    }
    return Number(activeTrade.value.initiatorPartyCharacterId);
});

const tradePartnerCharacter = computed(() => {
    if (!tradePartnerCharacterId.value) return null;
    return allCharacters.value.find((entry) => Number(entry.id) === tradePartnerCharacterId.value) ?? null;
});

const npcTradeIsOpen = computed(() => Boolean(npcTradeState.value?.isOpen));
const npcTradeConfigured = computed(() => {
    return Boolean(npcTradeState.value?.name)
        && Number(npcTradeState.value?.items?.length ?? 0) > 0;
});
const npcTradeActiveBySelf = computed(() => Number(npcTradeState.value?.activePartyCharacterId ?? 0) === currentCharacterId.value);
const npcTradeActiveByOther = computed(() => (
    Number(npcTradeState.value?.activePartyCharacterId ?? 0) > 0
    && Number(npcTradeState.value?.activePartyCharacterId ?? 0) !== currentCharacterId.value
));
const ownPendingNpcSellOffers = computed(() => {
    return (npcTradeState.value?.sellOffers ?? []).filter((entry) => (
        Number(entry.partyCharacterId) === currentCharacterId.value
        && entry.status === 'pending'
    ));
});
const ownLastRejectedNpcSellOfferByInventoryItemId = computed(() => {
    const map = {};
    (npcTradeState.value?.sellOffers ?? []).forEach((entry) => {
        if (Number(entry.partyCharacterId) !== currentCharacterId.value || entry.status !== 'rejected') return;
        const key = String(entry.inventoryItemId);
        if (!map[key] || Number(entry.id) > Number(map[key].id)) {
            map[key] = entry;
        }
    });
    return map;
});
const npcSellableInventoryItems = computed(() => {
    return inventoryItems.value.filter((entry) => Number(entry.quantity || 0) > 0);
});
const npcSellAmountCopper = computed(() => {
    const gold = Math.max(0, Number(npcSellForm.value.amountGold || 0));
    const silver = Math.max(0, Number(npcSellForm.value.amountSilver || 0));
    const copper = Math.max(0, Number(npcSellForm.value.amountCopper || 0));
    return (gold * 100) + (silver * 10) + copper;
});

const normalizeWalletType = (type) => {
    return ['grant', 'transfer_in', 'in'].includes(String(type)) ? 'in' : 'out';
};

const formatCopper = (amountCopper) => {
    const normalized = Math.max(0, Number(amountCopper || 0));
    const gold = Math.floor(normalized / 100);
    const silver = Math.floor((normalized % 100) / 10);
    const copper = normalized % 10;
    return `${gold}G ${silver}S ${copper}K`;
};

watch(() => props.character?.wallet, (nextWallet) => {
    walletState.value = { ...(nextWallet ?? { transactions: [] }) };
    walletTransactions.value = [...(nextWallet?.transactions ?? [])];
}, { immediate: true });

watch(availableTradeTargets, (targets) => {
    if (!targets.length) {
        selectedTradeTargetCharacterId.value = null;
        return;
    }
    if (!selectedTradeTargetCharacterId.value || !targets.some((entry) => Number(entry.id) === Number(selectedTradeTargetCharacterId.value))) {
        selectedTradeTargetCharacterId.value = Number(targets[0].id);
    }
}, { immediate: true });

watch(npcSellableInventoryItems, (items) => {
    if (!items.length) {
        npcSellForm.value.inventoryItemId = null;
        return;
    }
    if (!npcSellForm.value.inventoryItemId || !items.some((entry) => Number(entry.id) === Number(npcSellForm.value.inventoryItemId))) {
        npcSellForm.value.inventoryItemId = Number(items[0].id);
    }
}, { immediate: true });

const racePreviewSources = computed(() => {
    if (!props.character?.race || !props.character?.gender) {
        return [];
    }

    const raceKey = Object.keys(raceImageBaseMap).find((key) => props.character.race.startsWith(key));
    const genderSuffix = genderImageSuffixMap[props.character.gender];

    if (!raceKey || !genderSuffix) {
        return [];
    }

    const base = raceImageBaseMap[raceKey];
    const candidates = [
        `${base}${genderSuffix}.jpeg`,
        `${base}${genderSuffix}.jpg`,
        `${base}${genderSuffix}.png`,
        `${base}.jpeg`,
        `${base}.jpg`,
        `${base}.png`,
    ];

    return candidates.flatMap((path) => [
        `/storage/${path}`,
        route('media.public', { path }),
    ]);
});

const racePreviewIndex = ref(0);

watch(
    racePreviewSources,
    () => {
        racePreviewIndex.value = 0;
        usePreviewFallback.value = false;
    },
    { immediate: true },
);

const currentRacePreviewSrc = computed(() => {
    return racePreviewSources.value[racePreviewIndex.value] ?? null;
});

const displayCharacterImage = computed(() => {
    if (!usePreviewFallback.value && props.character?.image_url) {
        return props.character.image_url;
    }

    return currentRacePreviewSrc.value || null;
});

const handleCharacterImageError = () => {
    if (!usePreviewFallback.value && props.character?.image_url) {
        usePreviewFallback.value = true;
        racePreviewIndex.value = 0;
        return;
    }

    if (racePreviewIndex.value < racePreviewSources.value.length - 1) {
        racePreviewIndex.value += 1;
        return;
    }

    racePreviewIndex.value = racePreviewSources.value.length;
};

const upsertRequest = (payload) => {
    const idx = requestState.value.findIndex((item) => Number(item.id) === Number(payload.id));
    if (idx >= 0) {
        requestState.value[idx] = payload;
    } else {
        requestState.value.unshift(payload);
    }
};

const onRequestCreated = (event) => {
    if (Number(event.partyId) !== Number(props.party.id) || !event.request) return;
    if (Number(event.request.targetUserId) !== Number(props.character.user_id)) return;
    upsertRequest(event.request);
};

const onRequestConfirmed = (event) => {
    if (Number(event.partyId) !== Number(props.party.id) || !event.request) return;
    upsertRequest(event.request);
};

const upsertInventoryItemLocal = (item, options = {}) => {
    const markAsUnseen = options.markAsUnseen === true;
    const idx = inventoryItems.value.findIndex((entry) => Number(entry.id) === Number(item.id));
    if (idx >= 0) {
        inventoryItems.value[idx] = item;
    } else {
        inventoryItems.value.push(item);
    }
    inventoryItems.value.sort((a, b) => Number(a.sortOrder) - Number(b.sortOrder));
    noteDraftByItemId.value[String(item.id)] = item.notes ?? '';
    if (markAsUnseen) {
        unseenInventoryItemIds.value[String(item.id)] = true;
    }
};

const removeInventoryItemLocal = (itemId) => {
    inventoryItems.value = inventoryItems.value.filter((entry) => Number(entry.id) !== Number(itemId));
    delete noteDraftByItemId.value[String(itemId)];
    delete unseenInventoryItemIds.value[String(itemId)];
};

const onInventoryItemUpdated = (event) => {
    if (Number(event.partyId) !== Number(props.party.id)) return;
    if (Number(event.partyCharacterId) !== Number(props.character.id)) return;

    if (event.action === 'remove' && event.itemId) {
        removeInventoryItemLocal(event.itemId);
        return;
    }

    if (event.action === 'upsert' && event.item) {
        upsertInventoryItemLocal(event.item, { markAsUnseen: event.notify === true });
    }
};

const onWalletUpdated = (event) => {
    if (Number(event.partyId) !== Number(props.party.id)) return;
    if (Number(event.partyCharacterId) !== Number(props.character.id)) return;
    if (!event.wallet) return;

    walletState.value = {
        ...(walletState.value ?? {}),
        ...event.wallet,
        transactions: event.wallet.transactions ?? walletTransactions.value,
    };

    if (event.transaction) {
        const txIndex = walletTransactions.value.findIndex((entry) => Number(entry.id) === Number(event.transaction.id));
        if (txIndex >= 0) {
            walletTransactions.value[txIndex] = event.transaction;
        } else {
            walletTransactions.value.unshift(event.transaction);
        }
    } else {
        walletTransactions.value = [...(event.wallet.transactions ?? walletTransactions.value)];
    }
};

const onNpcTradeUpdated = (event) => {
    if (Number(event.partyId) !== Number(props.party.id)) return;
    npcTradeState.value = event.offer && Object.keys(event.offer).length ? event.offer : null;
};

const claimNpcTrade = async () => {
    if (npcTradeBusy.value || !npcTradeIsOpen.value || npcTradeActiveByOther.value) return;
    npcTradeBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.npc-trade-offer.claim', props.party.id));
        if (response?.data?.offer) {
            npcTradeState.value = response.data.offer;
            npcTradeModalOpen.value = true;
        }
    } catch {
        // handled by backend flash/validation
    } finally {
        npcTradeBusy.value = false;
    }
};

const releaseNpcTrade = async () => {
    if (npcTradeBusy.value || !npcTradeActiveBySelf.value) return;
    npcTradeBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.npc-trade-offer.release', props.party.id));
        if (response?.data?.offer) {
            npcTradeState.value = response.data.offer;
            npcTradeModalOpen.value = false;
        }
    } catch {
        // handled by backend flash/validation
    } finally {
        npcTradeBusy.value = false;
    }
};

const npcBuyQuantityFor = (itemId) => {
    const key = String(itemId);
    const current = Number(npcBuyQuantityByItemId.value[key] || 1);
    return current > 0 ? current : 1;
};

const buyNpcItem = async (item) => {
    if (npcTradeBusy.value || !item?.id) return;

    const quantity = Math.max(1, npcBuyQuantityFor(item.id));
    if (quantity > Number(item.quantity || 0)) return;

    npcTradeBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.npc-trade-offer.buy', props.party.id), {
            item_id: Number(item.id),
            quantity,
        });

        if (response?.data?.offer) {
            npcTradeState.value = response.data.offer;
        }

        if (response?.data?.inventoryItem) {
            upsertInventoryItemLocal(response.data.inventoryItem, { markAsUnseen: false });
        }

        npcBuyQuantityByItemId.value[String(item.id)] = 1;
    } catch {
        // handled by backend flash/validation
    } finally {
        npcTradeBusy.value = false;
    }
};

const hasEnoughForNpcItem = (item) => {
    const quantity = Math.max(1, npcBuyQuantityFor(item.id));
    const totalPrice = quantity * Math.max(0, Number(item.priceCopper || 0));
    return Number(wallet.value?.copperBalance || 0) >= totalPrice;
};

const submitNpcSellOffer = async () => {
    if (npcTradeBusy.value || !npcTradeActiveBySelf.value) return;
    if (!npcSellForm.value.inventoryItemId || npcSellAmountCopper.value <= 0) return;
    npcTradeBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.npc-trade-offer.sell-offers.store', props.party.id), {
            inventory_item_id: Number(npcSellForm.value.inventoryItemId),
            quantity: Math.max(1, Number(npcSellForm.value.quantity || 1)),
            amount_copper: npcSellAmountCopper.value,
        });
        if (response?.data?.offer) {
            npcTradeState.value = response.data.offer;
            npcSellForm.value.quantity = 1;
            npcSellForm.value.amountGold = 0;
            npcSellForm.value.amountSilver = 0;
            npcSellForm.value.amountCopper = 1;
        }
    } catch {
        // handled by backend flash/validation
    } finally {
        npcTradeBusy.value = false;
    }
};

const upsertTradeSession = (trade) => {
    if (!trade?.id) return;
    const idx = tradeSessionState.value.findIndex((entry) => Number(entry.id) === Number(trade.id));
    if (idx >= 0) {
        tradeSessionState.value[idx] = trade;
    } else {
        tradeSessionState.value.unshift(trade);
    }
};

const onTradeRequested = (event) => {
    if (Number(event.partyId) !== Number(props.party.id) || !event.trade) return;
    const selfId = currentCharacterId.value;
    const involvesSelf = Number(event.trade.initiatorPartyCharacterId) === selfId
        || Number(event.trade.counterpartyPartyCharacterId) === selfId;
    if (!involvesSelf) return;
    upsertTradeSession(event.trade);
};

const onTradeAccepted = (event) => {
    if (Number(event.partyId) !== Number(props.party.id) || !event.trade) return;
    const selfId = currentCharacterId.value;
    const involvesSelf = Number(event.trade.initiatorPartyCharacterId) === selfId
        || Number(event.trade.counterpartyPartyCharacterId) === selfId;
    if (!involvesSelf) return;
    upsertTradeSession(event.trade);
    activeTradeModalOpen.value = true;
};

const startTrade = async () => {
    if (!selectedTradeTargetCharacterId.value || tradeBusy.value) return;
    tradeBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.trades.store', props.party.id), {
            counterparty_party_character_id: Number(selectedTradeTargetCharacterId.value),
        });
        if (response?.data?.trade) {
            upsertTradeSession(response.data.trade);
            tradePickerOpen.value = false;
        }
    } catch {
        // handled by backend flash/validation
    } finally {
        tradeBusy.value = false;
    }
};

const acceptTrade = async (trade) => {
    if (!trade?.id || tradeBusy.value) return;
    tradeBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.trades.accept', {
            party: props.party.id,
            tradeSession: trade.id,
        }));
        if (response?.data?.trade) {
            upsertTradeSession(response.data.trade);
            activeTradeModalOpen.value = true;
        }
    } catch {
        // handled by backend flash/validation
    } finally {
        tradeBusy.value = false;
    }
};

const markItemSeen = (itemId) => {
    if (unseenInventoryItemIds.value[String(itemId)]) {
        unseenInventoryItemIds.value[String(itemId)] = false;
    }
};

const modifierLabel = (request) => {
    if (!request || request.modifierType === 'none' || !request.modifierPoints) return 'Normal';
    return request.modifierType === 'easy'
        ? `Erleichtert +${request.modifierPoints}`
        : `Erschwert -${request.modifierPoints}`;
};

const isRolled = (talent) => talent?.rolledAt != null;
const resultClass = (talent) => {
    if (!isRolled(talent)) return 'text-bg-warning';
    return talent.isSuccess ? 'text-bg-success' : 'text-bg-danger';
};
const resultText = (talent) => {
    if (!isRolled(talent)) return 'Offen';
    return talent.isSuccess ? 'Erfolg' : 'Fehlschlag';
};

const requestResultClass = (request) => {
    if (!request || request.status !== 'confirmed') return 'text-bg-warning';
    const allSuccess = (request.talents ?? []).every((talent) => talent?.isSuccess === true);
    return allSuccess ? 'text-bg-success' : 'text-bg-danger';
};

const requestResultText = (request) => {
    if (!request || request.status !== 'confirmed') return 'Offen';
    const allSuccess = (request.talents ?? []).every((talent) => talent?.isSuccess === true);
    return allSuccess ? 'Bestanden' : 'Nicht bestanden';
};

const makeRollKey = (requestId, talentKey) => `${requestId}:${talentKey}`;

const noteDraftFor = (item) => {
    const key = String(item.id);
    if (noteDraftByItemId.value[key] === undefined) {
        noteDraftByItemId.value[key] = item.notes ?? '';
    }
    return noteDraftByItemId.value[key];
};

const isNoteEditorOpen = (itemId) => Boolean(noteEditorOpenByItemId.value[String(itemId)]);

const toggleNoteEditor = (itemId) => {
    const key = String(itemId);
    noteEditorOpenByItemId.value[key] = !noteEditorOpenByItemId.value[key];
};

const closeNoteEditor = (itemId) => {
    noteEditorOpenByItemId.value[String(itemId)] = false;
};

const saveItemNote = async (item) => {
    const key = String(item.id);
    const notes = (noteDraftByItemId.value[key] ?? '').trim();
    try {
        const response = await window.axios.patch(route('parties.inventory-items.update', {
            party: props.party.id,
            inventoryItem: item.id,
        }), {
            notes: notes || null,
        });
        if (response?.data?.item) {
            const idx = inventoryItems.value.findIndex((entry) => Number(entry.id) === Number(item.id));
            if (idx >= 0) inventoryItems.value[idx] = response.data.item;
            noteDraftByItemId.value[key] = response.data.item.notes ?? '';
            closeNoteEditor(item.id);
        }
    } catch {
        // ignore
    }
};

const useItem = async (item) => {
    inventoryActionHint.value = '';
    if (inventoryBusy.value) return;
    if (!isUsableItem(item)) return;
    inventoryBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.inventory-items.use', {
            party: props.party.id,
            inventoryItem: item.id,
        }));
        if (response?.data?.removed) {
            inventoryItems.value = inventoryItems.value.filter((entry) => Number(entry.id) !== Number(response.data.itemId));
            delete noteDraftByItemId.value[String(item.id)];
            delete noteEditorOpenByItemId.value[String(item.id)];
        } else if (response?.data?.item) {
            const idx = inventoryItems.value.findIndex((entry) => Number(entry.id) === Number(item.id));
            if (idx >= 0) inventoryItems.value[idx] = response.data.item;
        }
    } catch {
        // ignore
    } finally {
        inventoryBusy.value = false;
    }
};

const isUsableItem = (item) => {
    const category = String(item?.category ?? '').toLowerCase();
    return ['verbrauchbar', 'werkzeug'].includes(category);
};

const trySellItem = (item) => {
    const confirmSell = window.confirm(`"${item.name}" verkaufen?`);
    if (!confirmSell) return;
    inventoryActionHint.value = 'Verkaufen ist nur beim Handeln mit jemandem möglich. Das Handelssystem folgt später.';
};

const rollTalent = async (request, talent) => {
    if (!talent?.key || isRolled(talent)) return;
    const rollKey = makeRollKey(request.id, talent.key);
    if (rollingKeys.value[rollKey]) return;

    rollingKeys.value[rollKey] = true;
    const rolledValue = Math.floor(Math.random() * 20) + 1;

    try {
        const response = await window.axios.post(route('parties.talent-requests.confirm', {
            party: props.party.id,
            talentRequest: request.id,
        }), {
            rolled_talent_key: talent.key,
            rolled_value: rolledValue,
        });
        if (response?.data?.request) {
            upsertRequest(response.data.request);
        }
    } catch {
        // ignore; flash handles errors
    } finally {
        rollingKeys.value[rollKey] = false;
    }
};

onMounted(() => {
    if (!window.Echo) return;
    window.Echo.private(`party.${props.party.id}`)
        .listen('.party.talent-request.created', onRequestCreated)
        .listen('.party.talent-request.confirmed', onRequestConfirmed)
        .listen('.party.inventory-item.updated', onInventoryItemUpdated)
        .listen('.party.wallet.updated', onWalletUpdated)
        .listen('.party.trade.requested', onTradeRequested)
        .listen('.party.trade.accepted', onTradeAccepted)
        .listen('.party.npc-trade.updated', onNpcTradeUpdated);
});

onBeforeUnmount(() => {
    if (window.Echo) {
        window.Echo.leave(`party.${props.party.id}`);
    }
});
</script>

<template>
    <Head :title="`${party.name} - Charakter`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="h4 m-0">{{ party.name }} - Dein Charakter</h2>
        </template>

        <div class="eldoria-page">
            <div class="card shadow-sm border-0 mb-4 eldoria-panel">
                <div class="card-body p-3 p-md-4">
                    <ul class="nav nav-tabs eldoria-nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button
                            type="button"
                            class="nav-link"
                            :class="{ active: activeTab === 'character' }"
                            @click="activeTab = 'character'"
                        >
                            Charakter
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button
                            type="button"
                            class="nav-link"
                            :class="{ active: activeTab === 'inventory' }"
                            @click="activeTab = 'inventory'"
                        >
                            Inventar
                            <span v-if="hasUnseenInventoryItems" class="inventory-unseen-dot ms-2" aria-hidden="true"></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button
                            type="button"
                            class="nav-link"
                            :class="{ active: activeTab === 'notes' }"
                            @click="activeTab = 'notes'"
                        >
                            Notizen
                        </button>
                    </li>
                    </ul>
                </div>
            </div>

            <div v-if="activeTab === 'character'" class="row g-4">
                <div class="col-12 col-xl-8">
                    <div class="card shadow-sm border-0 eldoria-panel">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Charakterbogen</div>
                        <h3 class="h4 mb-3 eldoria-title">{{ character.name }}</h3>

                        <div class="text-muted mb-3">{{ character.race }} · {{ character.class_name }} · {{ character.gender }}</div>
                        <div class="text-muted mb-4">{{ character.age }} Jahre · {{ character.height_cm }} cm · {{ character.weight_kg }} kg</div>

                        <div class="mb-4">
                            <div class="small text-uppercase text-muted mb-2 eldoria-kicker-soft">Traits</div>
                            <div class="d-flex flex-wrap gap-2">
                                <span v-for="trait in character.traits" :key="trait" class="badge text-bg-light border eldoria-trait">{{ trait }}</span>
                            </div>
                        </div>

                        <div>
                            <div class="small text-uppercase text-muted mb-2 eldoria-kicker-soft">Talente</div>
                            <div class="row g-3">
                                <div v-for="group in talentGroups" :key="group.category" class="col-12 col-lg-6">
                                    <div class="border rounded p-3 bg-light-subtle h-100 eldoria-subpanel">
                                        <div class="fw-semibold small mb-2 eldoria-subtitle">{{ group.category }}</div>
                                        <div
                                            v-for="talent in group.items"
                                            :key="talent.key"
                                            class="d-flex justify-content-between border rounded px-3 py-2 bg-white mb-2 eldoria-row"
                                        >
                                            <span>{{ talent.label }}</span>
                                            <strong>{{ getTalentValue(talent.key) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                <div class="col-12 col-xl-4">
                    <div class="card shadow-sm border-0 mb-4 eldoria-panel">
                    <div class="card-body p-3">
                        <img
                            v-if="displayCharacterImage"
                            :src="displayCharacterImage"
                            :alt="`Charakterbild von ${character.name}`"
                            class="img-fluid rounded border eldoria-portrait"
                            @error="handleCharacterImageError"
                        >
                        <div v-else class="text-muted small">Kein Charakterbild verfügbar.</div>
                    </div>
                </div>

                    <div class="card shadow-sm border-0 eldoria-panel">
                    <div class="card-body p-4">
                        <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Anforderungen</div>
                        <div v-if="!latestMyRequest" class="text-muted small">Keine Talentanforderungen vom Spielleiter.</div>
                        <div v-else class="d-flex flex-column gap-2">
                            <div class="border rounded p-3 bg-light-subtle eldoria-subpanel">
                                <div class="small mb-2">
                                    <strong>{{ latestMyRequest.ownerUserName }}</strong> fordert:
                                    {{ latestMyRequest.talents.map((t) => t.label).join(', ') }}
                                    <span class="text-muted"> · {{ modifierLabel(latestMyRequest) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge" :class="requestResultClass(latestMyRequest)">
                                        {{ requestResultText(latestMyRequest) }}
                                    </span>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div
                                        v-for="talent in latestMyRequest.talents"
                                        :key="`${latestMyRequest.id}:${talent.key}`"
                                        class="d-flex justify-content-between align-items-center gap-2 border rounded px-2 py-2 bg-white eldoria-row"
                                    >
                                        <div class="small">
                                            <div class="fw-semibold">{{ talent.label }}</div>
                                            <div v-if="isRolled(talent)" class="text-muted">
                                                Wurf: {{ talent.rolledValue }} · Zielwert: {{ talent.targetValue }}
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge" :class="resultClass(talent)">
                                                {{ resultText(talent) }}
                                            </span>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary"
                                                :disabled="isRolled(talent) || rollingKeys[`${latestMyRequest.id}:${talent.key}`]"
                                                @click="rollTalent(latestMyRequest, talent)"
                                            >
                                                W20 würfeln
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <div v-else-if="activeTab === 'inventory'" class="card shadow-sm border-0 eldoria-panel">
                <div class="card-body p-4 p-md-5">
                    <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Inventar</div>
                    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                        <h3 class="h5 mb-0 eldoria-title">Reiseausrüstung von {{ character.name }}</h3>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="tradePickerOpen = true">
                                Handel starten
                            </button>
                            <button
                                v-if="npcTradeConfigured"
                                type="button"
                                class="btn btn-sm btn-outline-primary"
                                :disabled="npcTradeBusy || !npcTradeIsOpen || npcTradeActiveByOther"
                                @click="npcTradeActiveBySelf ? (npcTradeModalOpen = true) : claimNpcTrade()"
                            >
                                {{ npcTradeActiveBySelf ? 'NPC Handel öffnen' : 'Mit NPC handeln' }}
                            </button>
                            <div class="wallet-bag-pill" title="Charakterbeutel" role="button" tabindex="0" @click="walletModalOpen = true">
                                <span class="wallet-bag-icon" aria-hidden="true">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M10.1 2h3.8l.5 2.2h-4.8L10.1 2zm-3 4.2h9.8c2.7 0 5 2.2 5 5v6.2c0 2.5-2 4.6-4.6 4.6H6.7c-2.5 0-4.6-2-4.6-4.6v-6.2c0-2.8 2.2-5 5-5zm1.2 4.1c0 .7.5 1.2 1.2 1.2h5c.7 0 1.2-.6 1.2-1.2s-.5-1.2-1.2-1.2h-5c-.7 0-1.2.5-1.2 1.2z"/>
                                    </svg>
                                </span>
                                <span>{{ wallet?.display ?? '0G 0S 0K' }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="incomingPendingTrades.length" class="alert alert-warning py-2 px-3 small mb-3">
                        <div
                            v-for="trade in incomingPendingTrades"
                            :key="`incoming-${trade.id}`"
                            class="d-flex justify-content-between align-items-center gap-2 flex-wrap"
                        >
                            <span>Handelsanfrage von {{ trade.initiatorName }}</span>
                            <button type="button" class="btn btn-sm btn-primary" :disabled="tradeBusy" @click="acceptTrade(trade)">
                                Annehmen
                            </button>
                        </div>
                    </div>

                    <div v-if="outgoingPendingTrades.length" class="alert alert-info py-2 px-3 small mb-3">
                        Wartet auf Annahme:
                        {{ outgoingPendingTrades.map((trade) => trade.counterpartyName).join(', ') }}
                    </div>

                    <div v-if="activeTrade" class="alert alert-success py-2 px-3 small mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>Aktiver Handel mit {{ tradePartnerCharacter?.user?.name ?? tradePartnerCharacter?.name ?? 'Partner' }}</span>
                        <button type="button" class="btn btn-sm btn-outline-success" @click="activeTradeModalOpen = true">
                            Handel öffnen
                        </button>
                    </div>

                    <div class="alert py-2 px-3 small mb-3" :class="npcTradeIsOpen ? 'alert-success' : 'alert-secondary'">
                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <span>
                                NPC Handel:
                                <strong>{{ npcTradeState?.name || 'Kein NPC konfiguriert' }}</strong>
                                <span v-if="npcTradeActiveByOther" class="text-muted">
                                    · belegt durch {{ npcTradeState?.activeCharacterName || 'anderen Spieler' }}
                                </span>
                            </span>
                            <div class="d-flex gap-2">
                                <button
                                    v-if="npcTradeIsOpen && !npcTradeActiveByOther && !npcTradeActiveBySelf"
                                    type="button"
                                    class="btn btn-sm btn-primary"
                                    :disabled="npcTradeBusy"
                                    @click="claimNpcTrade"
                                >
                                    Mit NPC handeln
                                </button>
                                <button
                                    v-if="npcTradeActiveBySelf"
                                    type="button"
                                    class="btn btn-sm btn-outline-success"
                                    @click="npcTradeModalOpen = true"
                                >
                                    NPC Handel öffnen
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="tradePickerOpen" class="wallet-modal-backdrop" @click.self="tradePickerOpen = false">
                        <div class="wallet-modal-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="h6 mb-0">Handel starten</h4>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="tradePickerOpen = false">
                                    Schließen
                                </button>
                            </div>
                            <div v-if="!availableTradeTargets.length" class="text-muted small">
                                Kein Handelspartner verfügbar.
                            </div>
                            <div v-else class="d-flex flex-column gap-3">
                                <select v-model="selectedTradeTargetCharacterId" class="form-select">
                                    <option v-for="entry in availableTradeTargets" :key="entry.id" :value="entry.id">
                                        {{ entry.user?.name ?? entry.name }}
                                    </option>
                                </select>
                                <button type="button" class="btn btn-primary" :disabled="tradeBusy" @click="startTrade">
                                    Anfrage senden
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="walletModalOpen" class="wallet-modal-backdrop" @click.self="walletModalOpen = false">
                        <div class="wallet-modal-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="h6 mb-0">Wallet-Transaktionen</h4>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="walletModalOpen = false">
                                    Schließen
                                </button>
                            </div>
                            <div v-if="walletTransactions.length === 0" class="text-muted small">
                                Noch keine Wallet-Transaktionen.
                            </div>
                            <div v-else class="d-flex flex-column gap-2">
                                <div
                                    v-for="tx in walletTransactions"
                                    :key="tx.id"
                                    class="wallet-transaction-row d-flex justify-content-between align-items-start gap-2"
                                >
                                    <div>
                                        <div class="small fw-semibold">
                                            {{ walletTypeLabels[normalizeWalletType(tx.type)] || tx.type }} · {{ tx.amountDisplay }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ tx.actorUserName || 'System' }}<span v-if="tx.note"> · {{ tx.note }}</span>
                                        </div>
                                    </div>
                                    <span class="badge" :class="walletTypeBadges[normalizeWalletType(tx.type)] || 'text-bg-secondary'">
                                        {{ normalizeWalletType(tx.type).toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="activeTradeModalOpen && activeTrade" class="wallet-modal-backdrop" @click.self="activeTradeModalOpen = false">
                        <div class="trade-modal-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="h6 mb-0">Aktiver Handel</h4>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="activeTradeModalOpen = false">
                                    Schließen
                                </button>
                            </div>
                            <div class="row g-3">
                                <div class="col-12 col-lg-6">
                                    <div class="trade-column p-3 h-100">
                                        <div class="fw-semibold mb-2">Du: {{ character.name }}</div>
                                        <div class="small text-muted mb-2">Wallet: {{ wallet?.display ?? '0G 0S 0K' }}</div>
                                        <div class="small text-uppercase text-muted mb-2">Dein Inventar</div>
                                        <div v-if="inventoryItems.length === 0" class="small text-muted">Leer</div>
                                        <ul v-else class="small mb-0 ps-3">
                                            <li v-for="item in inventoryItems" :key="`self-trade-${item.id}`">
                                                {{ item.name }} x{{ item.quantity }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="trade-column p-3 h-100">
                                        <div class="fw-semibold mb-2">
                                            {{ tradePartnerCharacter?.name ?? 'Handelspartner' }}
                                        </div>
                                        <div class="small text-uppercase text-muted mb-2">Inventar Partner</div>
                                        <div v-if="!(tradePartnerCharacter?.inventoryItems ?? []).length" class="small text-muted">Leer</div>
                                        <ul v-else class="small mb-0 ps-3">
                                            <li v-for="item in (tradePartnerCharacter?.inventoryItems ?? [])" :key="`partner-trade-${item.id}`">
                                                {{ item.name }} x{{ item.quantity }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="npcTradeModalOpen && npcTradeState && npcTradeActiveBySelf" class="wallet-modal-backdrop" @click.self="npcTradeModalOpen = false">
                        <div class="trade-modal-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="h6 mb-0">NPC Handel: {{ npcTradeState.name }}</h4>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-warning" :disabled="npcTradeBusy" @click="releaseNpcTrade">
                                        Verlassen
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="npcTradeModalOpen = false">
                                        Schließen
                                    </button>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-12 col-lg-6">
                                    <div class="trade-column p-3 h-100">
                                        <div class="fw-semibold mb-2">Du: {{ character.name }}</div>
                                        <div class="small text-muted mb-2">Wallet: {{ wallet?.display ?? '0G 0S 0K' }}</div>
                                        <div class="small text-uppercase text-muted mb-2">Dein Inventar</div>
                                        <div v-if="inventoryItems.length === 0" class="small text-muted">Leer</div>
                                        <ul v-else class="small mb-0 ps-3">
                                            <li v-for="item in inventoryItems" :key="`self-npc-trade-${item.id}`">
                                                {{ item.name }} x{{ item.quantity }}
                                            </li>
                                        </ul>
                                        <hr class="my-3">
                                        <div class="small text-uppercase text-muted mb-2">Item an NPC verkaufen</div>
                                        <div v-if="!npcSellableInventoryItems.length" class="small text-muted">Kein Item zum Verkaufen.</div>
                                        <div v-else class="row g-2">
                                            <div class="col-12">
                                                <select v-model="npcSellForm.inventoryItemId" class="form-select form-select-sm">
                                                    <option v-for="entry in npcSellableInventoryItems" :key="`npc-sell-item-${entry.id}`" :value="entry.id">
                                                        {{ entry.name }} x{{ entry.quantity }}
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <input v-model.number="npcSellForm.quantity" type="number" min="1" class="form-control form-control-sm" placeholder="Menge">
                                            </div>
                                            <div class="col-8 d-flex gap-1">
                                                <input v-model.number="npcSellForm.amountGold" type="number" min="0" class="form-control form-control-sm" placeholder="G">
                                                <input v-model.number="npcSellForm.amountSilver" type="number" min="0" class="form-control form-control-sm" placeholder="S">
                                                <input v-model.number="npcSellForm.amountCopper" type="number" min="0" class="form-control form-control-sm" placeholder="K">
                                            </div>
                                            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                <span class="small text-muted">Angebot: {{ formatCopper(npcSellAmountCopper) }}</span>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    :disabled="npcTradeBusy || npcSellAmountCopper <= 0 || !npcSellForm.inventoryItemId"
                                                    @click="submitNpcSellOffer"
                                                >
                                                    Verkauf anbieten
                                                </button>
                                            </div>
                                            <div
                                                v-if="ownLastRejectedNpcSellOfferByInventoryItemId[String(npcSellForm.inventoryItemId)]"
                                                class="col-12 small text-warning"
                                            >
                                                Letztes Angebot wurde abgelehnt:
                                                {{ ownLastRejectedNpcSellOfferByInventoryItemId[String(npcSellForm.inventoryItemId)].amountDisplay }}.
                                                Neuer Wert muss darunter liegen.
                                            </div>
                                        </div>
                                        <div class="small text-uppercase text-muted mb-2 mt-3">Offene Verkaufsangebote</div>
                                        <div v-if="!ownPendingNpcSellOffers.length" class="small text-muted">Keine offenen Angebote.</div>
                                        <ul v-else class="small mb-0 ps-3">
                                            <li v-for="offer in ownPendingNpcSellOffers" :key="`own-pending-sell-${offer.id}`">
                                                {{ offer.itemName }} x{{ offer.quantity }} · {{ offer.amountDisplay }} (wartet auf Spielleiter)
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="trade-column p-3 h-100">
                                        <div class="fw-semibold mb-2">{{ npcTradeState.name }}</div>
                                        <div class="small text-uppercase text-muted mb-2">NPC Inventar</div>
                                        <div v-if="!(npcTradeState.items ?? []).length" class="small text-muted">Leer</div>
                                        <div v-else class="d-flex flex-column gap-2">
                                                <div
                                                    v-for="item in (npcTradeState.items ?? [])"
                                                    :key="`npc-item-${item.id}`"
                                                    class="d-flex justify-content-between align-items-center gap-2 border rounded p-2 bg-white"
                                                >
                                                <div class="small">
                                                    <div class="fw-semibold">{{ item.name }} x{{ item.quantity }} · {{ item.priceDisplay }}</div>
                                                    <div class="text-muted">
                                                        {{ item.category || 'Allgemein' }}<span v-if="item.notes"> · {{ item.notes }}</span>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input
                                                        :value="npcBuyQuantityFor(item.id)"
                                                        type="number"
                                                        min="1"
                                                        :max="item.quantity"
                                                        class="form-control form-control-sm"
                                                        style="width: 88px;"
                                                        @input="npcBuyQuantityByItemId[String(item.id)] = Number($event.target.value || 1)"
                                                    >
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-primary"
                                                        :disabled="npcTradeBusy || npcBuyQuantityFor(item.id) > item.quantity || !hasEnoughForNpcItem(item)"
                                                        @click="buyNpcItem(item)"
                                                    >
                                                        Kaufen
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bag-area p-3 p-md-4">
                        <div class="bag-mouth mb-3">Jutebeutel</div>
                        <div v-if="inventoryActionHint" class="alert alert-warning py-2 px-3 small mb-3">
                            {{ inventoryActionHint }}
                        </div>
                        <div v-if="inventoryItems.length === 0" class="text-muted small">
                            Dein Beutel ist leer.
                        </div>
                        <div v-else class="row g-2">
                            <div v-for="item in inventoryItems" :key="item.id" class="col-12 col-md-6">
                                <div class="bag-item d-flex justify-content-between align-items-start gap-2" @mouseenter="markItemSeen(item.id)">
                                    <div>
                                        <div class="fw-semibold d-flex align-items-center gap-2">
                                            <span>{{ item.name }}</span>
                                            <span v-if="unseenInventoryItemIds[String(item.id)]" class="inventory-unseen-dot" aria-hidden="true"></span>
                                            <span class="note-tooltip-wrap">
                                                <button
                                                    type="button"
                                                    class="inventory-note-icon"
                                                    :class="{ 'has-note': item.notes }"
                                                    aria-label="Notiz anzeigen oder bearbeiten"
                                                    @click="toggleNoteEditor(item.id)"
                                                >
                                                    i
                                                </button>
                                                <span class="note-tooltip-content">
                                                    {{ item.notes || 'Keine Notiz. Klicke auf das Icon zum Bearbeiten.' }}
                                                </span>
                                            </span>
                                        </div>
                                        <div class="small text-muted">
                                            {{ item.category || 'Allgemein' }}
                                        </div>
                                        <div v-if="isNoteEditorOpen(item.id)" class="mt-2 d-flex gap-2">
                                            <input
                                                :value="noteDraftFor(item)"
                                                type="text"
                                                class="form-control form-control-sm"
                                                placeholder="Notiz zu diesem Item"
                                                @input="noteDraftByItemId[String(item.id)] = $event.target.value"
                                            >
                                            <button type="button" class="btn btn-sm btn-outline-primary" @click="saveItemNote(item)">
                                                Notiz speichern
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="closeNoteEditor(item.id)">
                                                Schließen
                                            </button>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="px-2 small fw-semibold">{{ item.quantity }}</span>
                                        <button
                                            v-if="isUsableItem(item)"
                                            type="button"
                                            class="btn btn-sm btn-outline-success ms-1"
                                            :disabled="inventoryBusy"
                                            @click="useItem(item)"
                                        >
                                            Nutzen
                                        </button>
                                        <button
                                            v-else
                                            type="button"
                                            class="btn btn-sm btn-outline-danger ms-1"
                                            title="Verkaufen"
                                            aria-label="Verkaufen"
                                            @click="trySellItem(item)"
                                        >
                                            <i class="fa-solid fa-coins"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="card shadow-sm border-0 eldoria-panel">
            <div class="card-body p-4 p-md-5">
                <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Notizen</div>
                <h3 class="h5 mb-2 eldoria-title">Notizen werden vorbereitet</h3>
                <p class="text-muted mb-0">Hier kannst du später Sitzungsnotizen, Namen und wichtige Hinweise sammeln.</p>
            </div>
        </div>
        </div>
    </AuthenticatedLayout>

    <DiceRoller :party-id="party.id" />
</template>

<style scoped>
.eldoria-page {
    --eldoria-bg: #f6f1e5;
    --eldoria-paper: #fffdf8;
    --eldoria-ink: #2f2618;
    --eldoria-accent: #7f5a2a;
    --eldoria-border: #d8c7a7;
    background:
        radial-gradient(circle at 15% 0%, rgba(127, 90, 42, 0.14), transparent 32%),
        radial-gradient(circle at 85% 8%, rgba(40, 92, 61, 0.12), transparent 30%),
        var(--eldoria-bg);
    border-radius: 16px;
    padding: 1rem;
}

.eldoria-panel {
    background: linear-gradient(180deg, rgba(255, 253, 248, 0.98), rgba(247, 238, 223, 0.97));
    border: 1px solid var(--eldoria-border) !important;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(64, 43, 19, 0.12) !important;
}

.eldoria-nav-tabs {
    border-bottom: 1px solid var(--eldoria-border);
    gap: 0.25rem;
}

.eldoria-nav-tabs .nav-link {
    color: var(--eldoria-ink);
    border: 1px solid transparent;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
}

.eldoria-nav-tabs .nav-link.active {
    color: var(--eldoria-accent);
    background: var(--eldoria-paper);
    border-color: var(--eldoria-border);
    border-bottom-color: var(--eldoria-paper);
}

.eldoria-kicker {
    letter-spacing: 0.14em;
    color: #6a5233 !important;
}

.eldoria-kicker-soft {
    letter-spacing: 0.08em;
    color: #6a5233 !important;
}

.eldoria-title {
    font-family: Georgia, 'Times New Roman', serif;
    color: var(--eldoria-ink);
}

.eldoria-subtitle {
    color: #6f5432;
}

.eldoria-subpanel {
    border-color: var(--eldoria-border) !important;
    background: rgba(255, 252, 245, 0.86) !important;
}

.eldoria-row {
    border-color: #e4d7bf !important;
}

.eldoria-trait {
    background: #f4ead9 !important;
    border-color: #d9c6a2 !important;
    color: #4f3a21 !important;
}

.eldoria-portrait {
    width: 100%;
    max-height: 460px;
    object-fit: cover;
    border-color: var(--eldoria-border) !important;
}

.bag-area {
    background:
        radial-gradient(circle at 20% 15%, rgba(255, 255, 255, 0.25), transparent 35%),
        repeating-linear-gradient(
            45deg,
            rgba(134, 97, 54, 0.14) 0px,
            rgba(134, 97, 54, 0.14) 6px,
            rgba(122, 88, 49, 0.08) 6px,
            rgba(122, 88, 49, 0.08) 12px
        ),
        #b48650;
    border: 2px solid #8d6234;
    border-radius: 18px 18px 26px 26px;
    box-shadow: inset 0 2px 8px rgba(72, 45, 21, 0.35);
}

.bag-mouth {
    display: inline-block;
    background: #6f4c27;
    color: #f7ead5;
    font-size: 0.74rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
}

.bag-item {
    background: rgba(255, 246, 230, 0.9);
    border: 1px solid rgba(96, 64, 30, 0.25);
    border-radius: 10px;
    padding: 0.6rem 0.65rem;
}

.wallet-bag-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    border: 1px solid #b78a4f;
    background: rgba(250, 235, 206, 0.9);
    color: #5b3f1f;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
}

.wallet-bag-icon {
    font-size: 0.95rem;
}

.wallet-panel {
    border-radius: 14px;
    border: 1px solid rgba(108, 74, 38, 0.3);
    background: rgba(250, 240, 222, 0.78);
}

.wallet-transaction-row {
    border: 1px solid rgba(110, 74, 35, 0.24);
    border-radius: 8px;
    background: rgba(255, 250, 241, 0.86);
    padding: 0.45rem 0.55rem;
}

.wallet-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(28, 20, 10, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    z-index: 1050;
}

.wallet-modal-card {
    width: min(680px, 100%);
    max-height: 80vh;
    overflow: auto;
    background: #fff8ee;
    border: 1px solid rgba(110, 74, 35, 0.35);
    border-radius: 12px;
    padding: 1rem;
}

.trade-modal-card {
    width: min(980px, 100%);
    max-height: 84vh;
    overflow: auto;
    background: #fff8ee;
    border: 1px solid rgba(110, 74, 35, 0.35);
    border-radius: 12px;
    padding: 1rem;
}

.trade-column {
    border-radius: 10px;
    border: 1px solid rgba(110, 74, 35, 0.25);
    background: rgba(255, 250, 241, 0.86);
}

.inventory-note-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    color: #5f3f1d;
    background: #f5deb5;
    border: 1px solid #b9894f;
    cursor: pointer;
}

.inventory-note-icon.has-note {
    color: #fff;
    background: #7b552a;
    border-color: #5f3f1d;
}

.inventory-unseen-dot {
    width: 9px;
    height: 9px;
    border-radius: 999px;
    display: inline-block;
    background: #d11c2d;
    box-shadow: 0 0 0 2px rgba(255, 238, 225, 0.92);
}

.note-tooltip-wrap {
    position: relative;
    display: inline-flex;
}

.note-tooltip-content {
    position: absolute;
    left: 50%;
    bottom: calc(100% + 8px);
    transform: translateX(-50%);
    min-width: 220px;
    max-width: 320px;
    padding: 0.45rem 0.55rem;
    border-radius: 8px;
    background: rgba(44, 30, 16, 0.95);
    color: #f7eddc;
    border: 1px solid rgba(218, 183, 130, 0.45);
    box-shadow: 0 10px 18px rgba(0, 0, 0, 0.22);
    font-size: 0.75rem;
    line-height: 1.25;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.12s ease;
    white-space: normal;
    z-index: 20;
}

.note-tooltip-wrap:hover .note-tooltip-content,
.note-tooltip-wrap:focus-within .note-tooltip-content {
    opacity: 1;
}
</style>
