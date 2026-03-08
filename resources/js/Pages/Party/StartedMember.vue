<script setup>
import DiceRoller from '@/Components/DiceRoller.vue';
import InteractiveWorldMap from '@/Components/InteractiveWorldMap.vue';
import YouAreDeadOverlay from '@/Components/YouAreDeadOverlay.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CharacterTab from '@/Pages/Party/StartedMemberTabs/CharacterTab.vue';
import InventoryTab from '@/Pages/Party/StartedMemberTabs/InventoryTab.vue';
import NotesTab from '@/Pages/Party/StartedMemberTabs/NotesTab.vue';
import NpcTradeTab from '@/Pages/Party/StartedMemberTabs/NpcTradeTab.vue';
import ActiveTradeModal from '@/Pages/Party/StartedMemberTabs/ActiveTradeModal.vue';
import NpcTradeModal from '@/Pages/Party/StartedMemberTabs/NpcTradeModal.vue';
import TradePickerModal from '@/Pages/Party/StartedMemberTabs/TradePickerModal.vue';
import WalletTransactionsModal from '@/Pages/Party/StartedMemberTabs/WalletTransactionsModal.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, defineAsyncComponent, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    party: { type: Object, required: true },
    character: { type: Object, required: true },
    characters: { type: Array, default: () => [] },
    talentDefinitions: { type: Array, default: () => [] },
    talentRequests: { type: Array, default: () => [] },
    tradeSessions: { type: Array, default: () => [] },
    npcTradeOffers: { type: Array, default: () => [] },
    mapLocations: { type: Array, default: () => [] },
});
const RequirementRollOverlay = defineAsyncComponent(() => import('@/Components/RequirementRollOverlay.vue'));

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
const journalNoteBusy = ref(false);
const journalNoteDraft = ref('');
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
const npcTradeStateList = ref([...(props.npcTradeOffers ?? [])]);
const selectedNpcTradeOfferId = ref((props.npcTradeOffers ?? []).find((entry) => Boolean(entry?.isOpen))?.id ?? null);
const tradePickerOpen = ref(false);
const tradeBusy = ref(false);
const activeTradeModalOpen = ref(false);
const selectedTradeTargetCharacterId = ref(null);
const tradeSessionState = ref([...(props.tradeSessions ?? [])]);
const hpState = ref({
    hpMax: Number(props.character?.hpMax ?? 0),
    hpCurrent: Number(props.character?.hpCurrent ?? 0),
    hpTemp: Number(props.character?.hpTemp ?? 0),
});
const requirementRollOverlayOpen = ref(false);
const requirementRollToken = ref(0);
const requirementRollContext = ref(null);
const requirementRollBusy = ref(false);

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

watch(() => props.character, (next) => {
    hpState.value = {
        hpMax: Number(next?.hpMax ?? 0),
        hpCurrent: Number(next?.hpCurrent ?? 0),
        hpTemp: Number(next?.hpTemp ?? 0),
    };
}, { immediate: true });

watch(() => props.npcTradeOffers, (nextOffers) => {
    npcTradeStateList.value = [...(nextOffers ?? [])];
    const openOffers = npcTradeStateList.value.filter((entry) => Boolean(entry?.isOpen));
    if (!selectedNpcTradeOfferId.value && openOffers.length) {
        selectedNpcTradeOfferId.value = openOffers[0].id;
    }
    if (
        selectedNpcTradeOfferId.value
        && !openOffers.some((entry) => Number(entry.id) === Number(selectedNpcTradeOfferId.value))
    ) {
        selectedNpcTradeOfferId.value = openOffers[0]?.id ?? null;
    }
}, { immediate: true });

const npcTradeState = computed(() => {
    return npcTradeStateList.value.find((entry) => Number(entry.id) === Number(selectedNpcTradeOfferId.value)) ?? null;
});

const getTalentValue = (key) => Number(props.character?.talents?.[key] ?? 0);
const characterWithHp = computed(() => ({
    ...(props.character ?? {}),
    hpMax: Number(hpState.value.hpMax ?? 0),
    hpCurrent: Number(hpState.value.hpCurrent ?? 0),
    hpTemp: Number(hpState.value.hpTemp ?? 0),
}));
const isCharacterDead = computed(() => Number(characterWithHp.value?.hpCurrent ?? 0) <= 0);

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
const npcTradeMerchants = computed(() => {
    return (npcTradeStateList.value ?? []).filter((entry) => Boolean(entry?.isOpen));
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

const hasMapItem = computed(() => {
    return inventoryItems.value.some((item) => {
        if (Number(item?.quantity || 0) <= 0) return false;
        const name = String(item?.name ?? '').trim().toLowerCase();
        return name === 'karte' || name.includes('karte');
    });
});

const isTravelJournalItem = (item) => String(item?.name ?? '').trim().toLowerCase() === 'reisetagebuch';
const activeTravelJournalItem = computed(() => {
    return inventoryItems.value.find((item) => isTravelJournalItem(item) && Number(item.quantity || 0) > 0) ?? null;
});
const hasTravelJournal = computed(() => Boolean(activeTravelJournalItem.value));
const isRequirementRolling = computed(() => requirementRollOverlayOpen.value || requirementRollBusy.value);

watch(npcTradeMerchants, (merchants) => {
    if (!merchants.length && activeTab.value === 'npc-trade') {
        activeTab.value = 'inventory';
    }
}, { immediate: true });

watch(activeTravelJournalItem, (journalItem) => {
    journalNoteDraft.value = journalItem?.notes ?? '';
    if (!journalItem && activeTab.value === 'notes') {
        activeTab.value = 'inventory';
    }
}, { immediate: true });

watch(hasMapItem, (hasMap) => {
    if (!hasMap && activeTab.value === 'map') {
        activeTab.value = 'inventory';
    }
}, { immediate: true });

watch(isCharacterDead, (isDead) => {
    if (!isDead) return;
    walletModalOpen.value = false;
    npcTradeModalOpen.value = false;
    tradePickerOpen.value = false;
    activeTradeModalOpen.value = false;
}, { immediate: true });

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
    if (!event.offer || !Object.keys(event.offer).length) return;
    const next = [...(npcTradeStateList.value ?? [])];
    const index = next.findIndex((entry) => Number(entry.id) === Number(event.offer.id));
    if (index >= 0) {
        next[index] = event.offer;
    } else {
        next.unshift(event.offer);
    }
    npcTradeStateList.value = next;
    const openOffers = next.filter((entry) => Boolean(entry?.isOpen));
    if (!selectedNpcTradeOfferId.value && openOffers.length) {
        selectedNpcTradeOfferId.value = Number(openOffers[0].id);
    }
    if (
        selectedNpcTradeOfferId.value
        && !openOffers.some((entry) => Number(entry.id) === Number(selectedNpcTradeOfferId.value))
    ) {
        selectedNpcTradeOfferId.value = openOffers[0]?.id ?? null;
        npcTradeModalOpen.value = false;
    }
};

const onCharacterHpUpdated = (event) => {
    if (Number(event.partyId) !== Number(props.party.id)) return;
    if (Number(event.partyCharacterId) !== Number(props.character.id)) return;
    if (!event.hp) return;
    hpState.value = {
        hpMax: Number(event.hp.hpMax ?? hpState.value.hpMax ?? 0),
        hpCurrent: Number(event.hp.hpCurrent ?? hpState.value.hpCurrent ?? 0),
        hpTemp: Number(event.hp.hpTemp ?? hpState.value.hpTemp ?? 0),
    };
};

const ownerDisconnectEndTriggered = ref(false);

const onPartyPresenceLeaving = async (user) => {
    const ownerUserId = Number(props.party?.owner?.id ?? 0);
    if (!ownerUserId) return;
    if (Number(user?.id ?? 0) !== ownerUserId) return;
    if (ownerDisconnectEndTriggered.value) return;
    ownerDisconnectEndTriggered.value = true;

    try {
        await window.axios.post(route('parties.end-by-owner-disconnect', props.party.id));
    } catch {
        // ignore; if someone else already ended, this may return non-critical errors
    }
};

const onPartyEnded = (event) => {
    if (Number(event.partyId) !== Number(props.party.id)) return;
    router.visit(route('lobby'), { replace: true });
};

const claimNpcTrade = async () => {
    if (npcTradeBusy.value || !npcTradeIsOpen.value || npcTradeActiveByOther.value) return;
    if (!selectedNpcTradeOfferId.value) return;
    npcTradeBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.npc-trade-offer.claim', props.party.id), {
            npc_trade_offer_id: Number(selectedNpcTradeOfferId.value),
        });
        if (response?.data?.offer) {
            onNpcTradeUpdated({ partyId: props.party.id, offer: response.data.offer });
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
    if (!selectedNpcTradeOfferId.value) return;
    npcTradeBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.npc-trade-offer.release', props.party.id), {
            npc_trade_offer_id: Number(selectedNpcTradeOfferId.value),
        });
        if (response?.data?.offer) {
            onNpcTradeUpdated({ partyId: props.party.id, offer: response.data.offer });
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
    if (!selectedNpcTradeOfferId.value) return;

    const quantity = Math.max(1, npcBuyQuantityFor(item.id));
    if (quantity > Number(item.quantity || 0)) return;

    npcTradeBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.npc-trade-offer.buy', props.party.id), {
            npc_trade_offer_id: Number(selectedNpcTradeOfferId.value),
            item_id: Number(item.id),
            quantity,
        });

        if (response?.data?.offer) {
            onNpcTradeUpdated({ partyId: props.party.id, offer: response.data.offer });
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
    if (!selectedNpcTradeOfferId.value) return;
    if (!npcSellForm.value.inventoryItemId || npcSellAmountCopper.value <= 0) return;
    npcTradeBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.npc-trade-offer.sell-offers.store', props.party.id), {
            npc_trade_offer_id: Number(selectedNpcTradeOfferId.value),
            inventory_item_id: Number(npcSellForm.value.inventoryItemId),
            quantity: Math.max(1, Number(npcSellForm.value.quantity || 1)),
            amount_copper: npcSellAmountCopper.value,
        });
        if (response?.data?.offer) {
            onNpcTradeUpdated({ partyId: props.party.id, offer: response.data.offer });
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

const openTradePickerModal = () => {
    tradePickerOpen.value = true;
};

const closeTradePickerModal = () => {
    tradePickerOpen.value = false;
};

const openWalletModal = () => {
    walletModalOpen.value = true;
};

const closeWalletModal = () => {
    walletModalOpen.value = false;
};

const openActiveTradeModal = () => {
    activeTradeModalOpen.value = true;
};

const closeActiveTradeModal = () => {
    activeTradeModalOpen.value = false;
};

const selectNpcMerchant = (merchantId) => {
    selectedNpcTradeOfferId.value = Number(merchantId);
};

const claimNpcMerchant = async (merchantId) => {
    selectedNpcTradeOfferId.value = Number(merchantId);
    await claimNpcTrade();
};

const openNpcMerchantTrade = (merchantId) => {
    selectedNpcTradeOfferId.value = Number(merchantId);
    npcTradeModalOpen.value = true;
};

const closeNpcTradeModal = () => {
    npcTradeModalOpen.value = false;
};

const selectTradeTarget = (targetCharacterId) => {
    selectedTradeTargetCharacterId.value = Number(targetCharacterId);
};

const handleNoteInput = (itemId, value) => {
    noteDraftByItemId.value[String(itemId)] = value;
};

const handleJournalNoteInput = (value) => {
    journalNoteDraft.value = value;
};

const saveJournalNotes = async () => {
    if (journalNoteBusy.value || !activeTravelJournalItem.value?.id) return;
    journalNoteBusy.value = true;
    try {
        const response = await window.axios.patch(route('parties.inventory-items.update', {
            party: props.party.id,
            inventoryItem: activeTravelJournalItem.value.id,
        }), {
            notes: (journalNoteDraft.value ?? '').trim() || null,
        });
        if (response?.data?.item) {
            upsertInventoryItemLocal(response.data.item, { markAsUnseen: false });
            journalNoteDraft.value = response.data.item.notes ?? '';
        }
    } catch {
        // handled by backend flash/validation
    } finally {
        journalNoteBusy.value = false;
    }
};

const handleNpcSellFormInput = (field, value) => {
    npcSellForm.value[field] = value;
};

const handleNpcBuyQuantityInput = (itemId, quantity) => {
    npcBuyQuantityByItemId.value[String(itemId)] = quantity;
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

const modifierValue = (request) => {
    if (!request || request.modifierType === 'none' || !request.modifierPoints) return 0;
    return request.modifierType === 'easy'
        ? Number(request.modifierPoints)
        : -1 * Number(request.modifierPoints);
};

const difficultyLabel = (request) => {
    const label = String(request?.difficultyLabel ?? 'Normal');
    const sg = Number(request?.difficultySg ?? 12);
    return `${label} (SG ${sg})`;
};

const rollBreakdown = (talent, request) => {
    if (!talent?.rolledAt) return '';
    const talentBase = getTalentValue(talent.key);
    const mod = modifierValue(request);
    const rawValue = Number.isFinite(Number(talent?.rolledRaw)) ? Number(talent.rolledRaw) : null;
    const total = Number(talent?.rolledValue ?? 0);
    const sg = Number(talent?.targetValue ?? request?.difficultySg ?? 12);

    if (rawValue === null) {
        return `Talent ${talentBase} ${mod >= 0 ? `+ Mod ${mod}` : `- Mod ${Math.abs(mod)}`} = ${total} / SG ${sg}`;
    }

    return `W20 ${rawValue} + Talent ${talentBase} ${mod >= 0 ? `+ Mod ${mod}` : `- Mod ${Math.abs(mod)}`} = ${total} / SG ${sg}`;
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
    if (isRequirementRolling.value) return;

    requirementRollContext.value = {
        requestId: Number(request.id),
        talentKey: String(talent.key),
    };
    requirementRollOverlayOpen.value = true;
    requirementRollBusy.value = true;
    requirementRollToken.value += 1;
};

const handleRequirementRolled = async (payload) => {
    const context = requirementRollContext.value;
    if (!context) {
        requirementRollOverlayOpen.value = false;
        requirementRollBusy.value = false;
        return;
    }

    const request = requestState.value.find((entry) => Number(entry.id) === Number(context.requestId));
    const talent = request?.talents?.find((entry) => String(entry.key) === context.talentKey);
    if (!request || !talent || isRolled(talent)) {
        requirementRollOverlayOpen.value = false;
        requirementRollBusy.value = false;
        requirementRollContext.value = null;
        return;
    }

    const rollKey = makeRollKey(request.id, talent.key);
    if (rollingKeys.value[rollKey]) {
        requirementRollBusy.value = false;
        requirementRollContext.value = null;
        return;
    }

    requirementRollOverlayOpen.value = false;
    rollingKeys.value[rollKey] = true;
    const rolledValue = Number(payload?.result ?? 0);
    if (rolledValue < 1 || rolledValue > 20) {
        rollingKeys.value[rollKey] = false;
        requirementRollBusy.value = false;
        requirementRollContext.value = null;
        return;
    }

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
        requirementRollBusy.value = false;
        requirementRollContext.value = null;
    }
};

onMounted(() => {
    if (!window.Echo) return;
    window.Echo.private(`party.${props.party.id}`)
        .listen('.party.talent-request.created', onRequestCreated)
        .listen('.party.talent-request.confirmed', onRequestConfirmed)
        .listen('.party.inventory-item.updated', onInventoryItemUpdated)
        .listen('.party.wallet.updated', onWalletUpdated)
        .listen('.party.character-hp.updated', onCharacterHpUpdated)
        .listen('.party.trade.requested', onTradeRequested)
        .listen('.party.trade.accepted', onTradeAccepted)
        .listen('.party.npc-trade.updated', onNpcTradeUpdated)
        .listen('.party.ended', onPartyEnded);

    window.Echo.join(`party-online.${props.party.id}`)
        .leaving(onPartyPresenceLeaving);
});

onBeforeUnmount(() => {
    if (window.Echo) {
        window.Echo.leave(`party.${props.party.id}`);
        window.Echo.leave(`party-online.${props.party.id}`);
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
                    <li v-if="npcTradeMerchants.length" class="nav-item" role="presentation">
                        <button
                            type="button"
                            class="nav-link"
                            :class="{ active: activeTab === 'npc-trade' }"
                            @click="activeTab = 'npc-trade'"
                        >
                            NPC Handel
                        </button>
                    </li>
                    <li v-if="hasMapItem" class="nav-item" role="presentation">
                        <button
                            type="button"
                            class="nav-link"
                            :class="{ active: activeTab === 'map' }"
                            @click="activeTab = 'map'"
                        >
                            Karte
                        </button>
                    </li>
                    <li v-if="hasTravelJournal" class="nav-item" role="presentation">
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

            <CharacterTab
                v-if="activeTab === 'character'"
                :character="characterWithHp"
                :display-character-image="displayCharacterImage"
                :talent-groups="talentGroups"
                :get-talent-value="getTalentValue"
                :latest-my-request="latestMyRequest"
                :difficulty-label="difficultyLabel"
                :modifier-label="modifierLabel"
                :roll-breakdown="rollBreakdown"
                :request-result-class="requestResultClass"
                :request-result-text="requestResultText"
                :is-rolled="isRolled"
                :result-class="resultClass"
                :result-text="resultText"
                :rolling-keys="rollingKeys"
                :is-global-rolling="isRequirementRolling"
                :on-roll-talent="rollTalent"
                :handle-character-image-error="handleCharacterImageError"
            />

            <InventoryTab
                v-else-if="activeTab === 'inventory'"
                :character="character"
                :wallet="wallet"
                :trade-busy="tradeBusy"
                :incoming-pending-trades="incomingPendingTrades"
                :outgoing-pending-trades="outgoingPendingTrades"
                :active-trade="activeTrade"
                :trade-partner-character="tradePartnerCharacter"
                :inventory-action-hint="inventoryActionHint"
                :inventory-items="inventoryItems"
                :inventory-busy="inventoryBusy"
                :unseen-inventory-item-ids="unseenInventoryItemIds"
                :is-usable-item="isUsableItem"
                :is-note-editor-open="isNoteEditorOpen"
                :note-draft-for="noteDraftFor"
                :on-open-trade-picker="openTradePickerModal"
                :on-open-wallet="openWalletModal"
                :on-accept-trade="acceptTrade"
                :on-open-active-trade="openActiveTradeModal"
                :on-mark-item-seen="markItemSeen"
                :on-toggle-note-editor="toggleNoteEditor"
                :on-close-note-editor="closeNoteEditor"
                :on-note-input="handleNoteInput"
                :on-save-item-note="saveItemNote"
                :on-use-item="useItem"
            />

            <NpcTradeTab
                v-else-if="activeTab === 'npc-trade'"
                :npc-trade-merchants="npcTradeMerchants"
                :selected-npc-trade-offer-id="selectedNpcTradeOfferId"
                :current-character-id="currentCharacterId"
                :npc-trade-busy="npcTradeBusy"
                :on-select-merchant="selectNpcMerchant"
                :on-claim-merchant="claimNpcMerchant"
                :on-open-merchant="openNpcMerchantTrade"
            />

            <div v-else-if="activeTab === 'map'" class="card shadow-sm border-0 eldoria-panel">
                <div class="card-body p-4">
                    <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Weltkarte</div>
                    <h3 class="h5 mb-3 eldoria-title">Eldoria</h3>
                        <InteractiveWorldMap
                            src="/images/EldoriaMap.png"
                            alt="Eldoria Weltkarte"
                            :locations="props.mapLocations"
                            viewport-min-height="70vh"
                            :show-controls="false"
                            :show-details="false"
                            :show-reset-overlay="true"
                            :show-top-selection-info="true"
                            :selection-info-timeout-ms="60000"
                        />
                </div>
            </div>

            <NotesTab
                v-else-if="activeTab === 'notes'"
                :journal-item="activeTravelJournalItem"
                :note-draft="journalNoteDraft"
                :save-busy="journalNoteBusy"
                :on-input="handleJournalNoteInput"
                :on-save="saveJournalNotes"
            />

            <TradePickerModal
                :is-open="tradePickerOpen"
                :available-trade-targets="availableTradeTargets"
                :selected-trade-target-character-id="selectedTradeTargetCharacterId"
                :trade-busy="tradeBusy"
                :on-close="closeTradePickerModal"
                :on-select-target="selectTradeTarget"
                :on-start-trade="startTrade"
            />

            <WalletTransactionsModal
                :is-open="walletModalOpen"
                :wallet-transactions="walletTransactions"
                :wallet-type-labels="walletTypeLabels"
                :wallet-type-badges="walletTypeBadges"
                :normalize-wallet-type="normalizeWalletType"
                :on-close="closeWalletModal"
            />

            <ActiveTradeModal
                :is-open="activeTradeModalOpen"
                :active-trade="activeTrade"
                :character="character"
                :wallet="wallet"
                :inventory-items="inventoryItems"
                :trade-partner-character="tradePartnerCharacter"
                :on-close="closeActiveTradeModal"
            />

            <NpcTradeModal
                :is-open="npcTradeModalOpen"
                :npc-trade-state="npcTradeState"
                :npc-trade-active-by-self="npcTradeActiveBySelf"
                :character="character"
                :wallet="wallet"
                :inventory-items="inventoryItems"
                :npc-trade-busy="npcTradeBusy"
                :npc-sellable-inventory-items="npcSellableInventoryItems"
                :npc-sell-form="npcSellForm"
                :npc-sell-amount-copper="npcSellAmountCopper"
                :own-last-rejected-npc-sell-offer-by-inventory-item-id="ownLastRejectedNpcSellOfferByInventoryItemId"
                :own-pending-npc-sell-offers="ownPendingNpcSellOffers"
                :format-copper="formatCopper"
                :npc-buy-quantity-for="npcBuyQuantityFor"
                :has-enough-for-npc-item="hasEnoughForNpcItem"
                :on-close="closeNpcTradeModal"
                :on-release-npc-trade="releaseNpcTrade"
                :on-sell-form-input="handleNpcSellFormInput"
                :on-submit-npc-sell-offer="submitNpcSellOffer"
                :on-npc-buy-quantity-input="handleNpcBuyQuantityInput"
                :on-buy-npc-item="buyNpcItem"
            />
        </div>

        <YouAreDeadOverlay :show="isCharacterDead" message="Du bist Tot" />
    </AuthenticatedLayout>

    <RequirementRollOverlay
        :show="requirementRollOverlayOpen"
        :roll-token="requirementRollToken"
        :sides="20"
        @rolled="handleRequirementRolled"
    />

    <DiceRoller v-if="!isCharacterDead" :party-id="party.id" />
</template>

<style>
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
