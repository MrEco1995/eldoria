<script setup>
import DiceRoller from '@/Components/DiceRoller.vue';
import InteractiveWorldMap from '@/Components/InteractiveWorldMap.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    party: { type: Object, required: true },
    characters: { type: Array, default: () => [] },
    talentDefinitions: { type: Array, default: () => [] },
    talentRequests: { type: Array, default: () => [] },
    npcTradeOffers: { type: Array, default: () => [] },
});

const page = usePage();
const ownerName = computed(() => page.props.auth?.user?.name ?? 'Spielleiter');

const characterState = ref([...(props.characters ?? [])]);
const playerCharacters = computed(() => characterState.value ?? []);
const NPC_TRADE_TOP_TAB = 'npc-trade';
const MAP_TOP_TAB = 'map';
const activeCharacterId = ref(playerCharacters.value[0]?.id ?? NPC_TRADE_TOP_TAB);
const activeDetailTab = ref('character');
const selectedTalentsByUser = ref({});
const modifierByUser = ref({});
const requestState = ref([...props.talentRequests]);
const usePreviewFallback = ref(false);
const racePreviewIndex = ref(0);
const inventoryForm = ref({
    name: '',
    quantity: 1,
    category: '',
    notes: '',
});
const inventoryBusy = ref(false);
const walletBusy = ref(false);
const walletModalOpen = ref(false);
const walletForm = ref({
    type: 'in',
    amountGold: 0,
    amountSilver: 0,
    amountCopper: 0,
    note: '',
});
const npcTradeBusy = ref(false);
const npcTradeStateList = ref([...(props.npcTradeOffers ?? [])]);
const selectedNpcTradeOfferId = ref(props.npcTradeOffers?.[0]?.id ?? null);
const npcTradeSessionModalOpen = ref(false);
const npcSellOfferBusy = ref(false);
const npcTradeForm = ref({
    name: '',
    category: '',
    itemName: '',
    quantity: 1,
    priceGold: 0,
    priceSilver: 0,
    priceCopper: 1,
    notes: '',
    items: [],
});
const mapLocations = [
    {
        id: 'capital',
        name: 'Hauptstadt Eldoria',
        x: 49,
        y: 44,
        description: 'Politisches Zentrum des Reiches und häufigster Treffpunkt für neue Quests.',
    },
    {
        id: 'northwatch',
        name: 'Nordwacht',
        x: 58,
        y: 20,
        description: 'Festung an der nördlichen Grenze. Hohe Präsenz von Wachen und Patrouillen.',
    },
    {
        id: 'silverwald',
        name: 'Silberwald',
        x: 36,
        y: 51,
        description: 'Dichter Wald mit alten Ruinen und seltenen Ressourcen.',
    },
    {
        id: 'ashen-coast',
        name: 'Aschenküste',
        x: 23,
        y: 69,
        description: 'Gefährliche Küstenregion, bekannt für Piraten und verlorene Schätze.',
    },
];

const npcTradeState = computed(() => {
    return npcTradeStateList.value.find((entry) => Number(entry.id) === Number(selectedNpcTradeOfferId.value)) ?? null;
});

const inventoryPresetCategories = [
    'Waffen',
    'Rüstung',
    'Verbrauchbar',
    'Werkzeug',
    'Magie',
    'Quest',
    'Sonstiges',
];

const inventoryPresetItemsByCategory = {
    Waffen: [
        { name: 'Kurzschwert', quantity: 1 },
        { name: 'Dolch', quantity: 1 },
        { name: 'Kurzbogen', quantity: 1 },
    ],
    Rüstung: [
        { name: 'Lederwams', quantity: 1 },
        { name: 'Holzschild', quantity: 1 },
    ],
    Verbrauchbar: [
        { name: 'Heiltrank', quantity: 2 },
        { name: 'Ration', quantity: 3 },
        { name: 'Fackel', quantity: 2 },
    ],
    Werkzeug: [
        { name: 'Seil (10m)', quantity: 1 },
        { name: 'Dietrichset', quantity: 1 },
        { name: 'Feuerstein', quantity: 1 },
    ],
    Magie: [
        { name: 'Runenstein', quantity: 1 },
        { name: 'Ätherkristall', quantity: 1 },
    ],
    Quest: [
        { name: 'Versiegelter Brief', quantity: 1 },
        { name: 'Alte Karte', quantity: 1 },
    ],
    Sonstiges: [
        { name: 'Reisetagebuch', quantity: 1 },
        { name: 'Wasserflasche', quantity: 1 },
    ],
};

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

watch(() => props.characters, (next) => {
    characterState.value = [...(next ?? [])];
    if ([NPC_TRADE_TOP_TAB, MAP_TOP_TAB].includes(activeCharacterId.value)) {
        return;
    }
    if (!characterState.value.some((entry) => Number(entry.id) === Number(activeCharacterId.value))) {
        activeCharacterId.value = characterState.value[0]?.id ?? null;
    }
}, { immediate: true });

watch(() => props.npcTradeOffers, (nextOffers) => {
    npcTradeStateList.value = [...(nextOffers ?? [])];
    if (!selectedNpcTradeOfferId.value && npcTradeStateList.value.length) {
        selectedNpcTradeOfferId.value = npcTradeStateList.value[0].id;
    }
    if (
        selectedNpcTradeOfferId.value
        && !npcTradeStateList.value.some((entry) => Number(entry.id) === Number(selectedNpcTradeOfferId.value))
    ) {
        selectedNpcTradeOfferId.value = npcTradeStateList.value[0]?.id ?? null;
    }
}, { immediate: true });

watch(() => npcTradeState.value, (nextOffer) => {
    npcTradeForm.value.name = nextOffer?.name ?? '';
    npcTradeForm.value.items = (nextOffer?.items ?? []).map((item) => ({
        id: Number(item.id || Date.now()),
        name: String(item.name || ''),
        quantity: Math.max(1, Number(item.quantity || 1)),
        priceCopper: Math.max(1, Number(item.priceCopper || 1)),
        category: item.category || null,
        notes: item.notes || null,
    }));
}, { immediate: true });

watch(() => npcTradeState.value?.activePartyCharacterId, (nextId, prevId) => {
    if (Number(nextId || 0) > 0 && Number(prevId || 0) !== Number(nextId || 0)) {
        npcTradeSessionModalOpen.value = true;
    }
    if (!nextId) {
        npcTradeSessionModalOpen.value = false;
    }
});

const activeCharacter = computed(() => {
    return playerCharacters.value.find((entry) => entry.id === activeCharacterId.value) ?? null;
});

const isNpcTradeTopTabActive = computed(() => activeCharacterId.value === NPC_TRADE_TOP_TAB);
const isMapTopTabActive = computed(() => activeCharacterId.value === MAP_TOP_TAB);

const walletTypeLabels = {
    in: 'IN',
    out: 'OUT',
};

const walletTypeBadges = {
    in: 'text-bg-success',
    out: 'text-bg-danger',
};

const activeWallet = computed(() => activeCharacter.value?.wallet ?? null);

const walletTransactions = computed(() => activeWallet.value?.transactions ?? []);
const activeNpcTradeCharacter = computed(() => {
    const id = Number(npcTradeState.value?.activePartyCharacterId || 0);
    if (!id) return null;
    return playerCharacters.value.find((entry) => Number(entry.id) === id) ?? null;
});
const npcPendingSellOffers = computed(() => {
    return (npcTradeState.value?.sellOffers ?? []).filter((offer) => offer.status === 'pending');
});

const normalizeWalletType = (type) => {
    return ['grant', 'transfer_in', 'in'].includes(String(type)) ? 'in' : 'out';
};

const walletFormTotalCopper = computed(() => {
    const gold = Math.max(0, Number(walletForm.value.amountGold || 0));
    const silver = Math.max(0, Number(walletForm.value.amountSilver || 0));
    const copper = Math.max(0, Number(walletForm.value.amountCopper || 0));
    return (gold * 100) + (silver * 10) + copper;
});

const getTalentValue = (key) => Number(activeCharacter.value?.talents?.[key] ?? 0);

const talentGroups = computed(() => {
    const groups = new Map();
    (props.talentDefinitions ?? []).forEach((talent) => {
        const category = talent.category || 'Sonstige Talente';
        if (!groups.has(category)) groups.set(category, []);
        groups.get(category).push(talent);
    });
    return Array.from(groups.entries()).map(([category, items]) => ({ category, items }));
});

const latestActiveRequest = computed(() => {
    if (!activeCharacter.value) return null;
    return requestState.value
        .filter((request) => Number(request.targetUserId) === Number(activeCharacter.value.user_id))
        .sort((a, b) => Number(b.id) - Number(a.id))[0] ?? null;
});

const isTalentSelected = (userId, talentKey) => {
    return (selectedTalentsByUser.value[userId] ?? []).includes(talentKey);
};

const toggleTalentSelection = (userId, talentKey) => {
    const current = [...(selectedTalentsByUser.value[userId] ?? [])];
    const index = current.indexOf(talentKey);
    if (index >= 0) {
        current.splice(index, 1);
    } else {
        current.push(talentKey);
    }
    selectedTalentsByUser.value[userId] = current;
};

const ensureModifierState = (userId) => {
    if (!modifierByUser.value[userId]) {
        modifierByUser.value[userId] = {
            type: 'none',
            points: 0,
        };
    }
    return modifierByUser.value[userId];
};

const sendTalentRequest = async () => {
    if (!activeCharacter.value) return;

    const targetUserId = activeCharacter.value.user_id;
    const talents = selectedTalentsByUser.value[targetUserId] ?? [];
    if (!talents.length) return;
    const modifier = ensureModifierState(targetUserId);

    try {
        await window.axios.post(route('parties.talent-requests.store', props.party.id), {
            target_user_id: targetUserId,
            talents,
            modifier_type: modifier.type,
            modifier_points: modifier.type === 'none' ? 0 : Number(modifier.points || 0),
        });

        const optimistic = {
            id: Date.now(),
            partyId: props.party.id,
            ownerUserId: page.props.auth?.user?.id,
            ownerUserName: ownerName.value,
            targetUserId,
            targetUserName: activeCharacter.value.user?.name ?? 'Spieler',
            talents: talents.map((key) => {
                const definition = (props.talentDefinitions ?? []).find((entry) => entry.key === key);
                return { key, label: definition?.label ?? key };
            }),
            modifierType: modifier.type,
            modifierPoints: modifier.type === 'none' ? 0 : Number(modifier.points || 0),
            status: 'pending',
            rolledTalentKey: null,
            rolledValue: null,
            targetValue: null,
            isSuccess: null,
            createdAt: new Date().toISOString(),
            confirmedAt: null,
        };
        requestState.value.unshift(optimistic);
        selectedTalentsByUser.value[targetUserId] = [];
    } catch {
        // ignore; flash message handled server-side if available
    }
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
    upsertRequest(event.request);
};

const onRequestConfirmed = (event) => {
    if (Number(event.partyId) !== Number(props.party.id) || !event.request) return;
    upsertRequest(event.request);
};

const onInventoryItemUpdated = (event) => {
    if (Number(event.partyId) !== Number(props.party.id)) return;
    if (!event.partyCharacterId) return;

    if (event.action === 'remove' && event.itemId) {
        removeInventoryItemLocal(event.partyCharacterId, event.itemId);
        return;
    }

    if (event.action === 'upsert' && event.item) {
        replaceInventoryItem(event.partyCharacterId, event.item);
    }
};

const onWalletUpdated = (event) => {
    if (Number(event.partyId) !== Number(props.party.id)) return;
    if (!event.partyCharacterId || !event.wallet) return;

    upsertWallet(event.partyCharacterId, event.wallet, event.transaction ?? null);
};

const modifierLabel = (request) => {
    if (!request || request.modifierType === 'none' || !request.modifierPoints) {
        return 'Normal';
    }

    if (request.modifierType === 'easy') {
        return `Erleichtert +${request.modifierPoints}`;
    }

    return `Erschwert -${request.modifierPoints}`;
};

const racePreviewSources = computed(() => {
    if (!activeCharacter.value?.race || !activeCharacter.value?.gender) {
        return [];
    }

    const raceKey = Object.keys(raceImageBaseMap).find((key) => activeCharacter.value.race.startsWith(key));
    const genderSuffix = genderImageSuffixMap[activeCharacter.value.gender];

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

watch(
    () => activeCharacter.value?.id,
    () => {
        activeDetailTab.value = 'character';
        racePreviewIndex.value = 0;
        usePreviewFallback.value = false;
    },
    { immediate: true },
);

const currentRacePreviewSrc = computed(() => {
    return racePreviewSources.value[racePreviewIndex.value] ?? null;
});

const displayCharacterImage = computed(() => {
    if (!usePreviewFallback.value && activeCharacter.value?.image_url) {
        return activeCharacter.value.image_url;
    }
    return currentRacePreviewSrc.value || null;
});

const handleCharacterImageError = () => {
    if (!usePreviewFallback.value && activeCharacter.value?.image_url) {
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

const talentResultClass = (talent) => {
    if (!talent?.rolledAt) return 'text-bg-warning';
    return talent.isSuccess ? 'text-bg-success' : 'text-bg-danger';
};
const talentResultText = (talent) => {
    if (!talent?.rolledAt) return 'Offen';
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

const resetInventoryForm = () => {
    inventoryForm.value = {
        name: '',
        quantity: 1,
        category: '',
        notes: '',
    };
};

const replaceInventoryItem = (characterId, item) => {
    const charIndex = characterState.value.findIndex((entry) => Number(entry.id) === Number(characterId));
    if (charIndex < 0) return;

    const items = [...(characterState.value[charIndex].inventoryItems ?? [])];
    const itemIndex = items.findIndex((entry) => Number(entry.id) === Number(item.id));
    if (itemIndex >= 0) {
        items[itemIndex] = item;
    } else {
        items.push(item);
    }

    items.sort((a, b) => Number(a.sortOrder) - Number(b.sortOrder));
    characterState.value[charIndex] = {
        ...characterState.value[charIndex],
        inventoryItems: items,
    };
};

const removeInventoryItemLocal = (characterId, itemId) => {
    const charIndex = characterState.value.findIndex((entry) => Number(entry.id) === Number(characterId));
    if (charIndex < 0) return;
    characterState.value[charIndex] = {
        ...characterState.value[charIndex],
        inventoryItems: (characterState.value[charIndex].inventoryItems ?? []).filter((entry) => Number(entry.id) !== Number(itemId)),
    };
};

const upsertWallet = (characterId, wallet, transaction = null) => {
    const charIndex = characterState.value.findIndex((entry) => Number(entry.id) === Number(characterId));
    if (charIndex < 0) return;

    const nextTransactions = [...(wallet.transactions ?? characterState.value[charIndex]?.wallet?.transactions ?? [])];
    if (transaction) {
        const txIndex = nextTransactions.findIndex((entry) => Number(entry.id) === Number(transaction.id));
        if (txIndex >= 0) {
            nextTransactions[txIndex] = transaction;
        } else {
            nextTransactions.unshift(transaction);
        }
    }

    characterState.value[charIndex] = {
        ...characterState.value[charIndex],
        wallet: {
            ...(characterState.value[charIndex].wallet ?? {}),
            ...wallet,
            transactions: nextTransactions,
        },
    };
};

const splitCopper = (copperAmount) => {
    const normalized = Math.max(0, Number(copperAmount || 0));
    return {
        gold: Math.floor(normalized / 100),
        silver: Math.floor((normalized % 100) / 10),
        copper: normalized % 10,
    };
};

const formatCopper = (copperAmount) => {
    const coins = splitCopper(copperAmount);
    return `${coins.gold}G ${coins.silver}S ${coins.copper}K`;
};

const setNpcTradeCategory = (category) => {
    npcTradeForm.value.category = category;
};

const addNpcTradeItemLocal = ({ name, quantity = 1, category = null, notes = null }) => {
    const normalizedName = String(name || '').trim();
    if (!normalizedName) return;

    const normalizedCategory = category ? String(category).trim() : null;
    const normalizedNotes = notes ? String(notes).trim() : null;
    const addQuantity = Math.max(1, Number(quantity || 1));
    const addPriceCopper = Math.max(1, Number(
        npcTradeForm.value.priceCopper
        + (npcTradeForm.value.priceSilver * 10)
        + (npcTradeForm.value.priceGold * 100)
        || 1
    ));

    const items = [...(npcTradeForm.value.items ?? [])];
    const existingIndex = items.findIndex((entry) => (
        String(entry.name).toLowerCase() === normalizedName.toLowerCase()
        && String(entry.category || '') === String(normalizedCategory || '')
    ));

    if (existingIndex >= 0) {
        items[existingIndex].quantity = Math.min(999, Number(items[existingIndex].quantity || 0) + addQuantity);
        items[existingIndex].priceCopper = addPriceCopper;
        if (!items[existingIndex].notes && normalizedNotes) {
            items[existingIndex].notes = normalizedNotes;
        }
    } else {
        items.push({
            id: Date.now() + Math.floor(Math.random() * 1000),
            name: normalizedName,
            quantity: Math.min(999, addQuantity),
            priceCopper: addPriceCopper,
            category: normalizedCategory,
            notes: normalizedNotes,
        });
    }

    npcTradeForm.value.items = items;
};

const quickAddNpcPresetItem = (presetItem, category) => {
    addNpcTradeItemLocal({
        name: presetItem.name,
        quantity: Number(presetItem.quantity || 1),
        category,
        notes: null,
    });
};

const addNpcTradeItem = () => {
    addNpcTradeItemLocal({
        name: npcTradeForm.value.itemName,
        quantity: Number(npcTradeForm.value.quantity || 1),
        category: npcTradeForm.value.category || null,
        notes: npcTradeForm.value.notes || null,
    });
    npcTradeForm.value.itemName = '';
    npcTradeForm.value.quantity = 1;
    npcTradeForm.value.priceGold = 0;
    npcTradeForm.value.priceSilver = 0;
    npcTradeForm.value.priceCopper = 1;
    npcTradeForm.value.notes = '';
};

const updateNpcTradeItemQuantity = (index, delta) => {
    const items = [...(npcTradeForm.value.items ?? [])];
    if (!items[index]) return;
    const nextQuantity = Math.max(1, Number(items[index].quantity || 1) + delta);
    items[index].quantity = Math.min(999, nextQuantity);
    npcTradeForm.value.items = items;
};

const removeNpcTradeItem = (index) => {
    npcTradeForm.value.items = (npcTradeForm.value.items ?? []).filter((_, entryIndex) => entryIndex !== index);
};

const upsertNpcTradeOfferLocal = (offer) => {
    if (!offer?.id) return;
    const list = [...(npcTradeStateList.value ?? [])];
    const index = list.findIndex((entry) => Number(entry.id) === Number(offer.id));
    if (index >= 0) {
        list[index] = offer;
    } else {
        list.unshift(offer);
    }
    npcTradeStateList.value = list;
    if (!selectedNpcTradeOfferId.value) {
        selectedNpcTradeOfferId.value = Number(offer.id);
    }
};

const createNpcTradeMerchant = () => {
    selectedNpcTradeOfferId.value = null;
    npcTradeForm.value = {
        name: '',
        category: '',
        itemName: '',
        quantity: 1,
        priceGold: 0,
        priceSilver: 0,
        priceCopper: 1,
        notes: '',
        items: [],
    };
};

const updateNpcTradeItemPrice = (index, value) => {
    const items = [...(npcTradeForm.value.items ?? [])];
    if (!items[index]) return;
    items[index].priceCopper = Math.max(1, Number(value || 1));
    npcTradeForm.value.items = items;
};

const onNpcTradeUpdated = (event) => {
    if (Number(event.partyId) !== Number(props.party.id)) return;
    if (!event.offer || !Object.keys(event.offer).length) return;
    upsertNpcTradeOfferLocal(event.offer);
};

const saveNpcTradeOffer = async () => {
    if (npcTradeBusy.value) return;
    const items = (npcTradeForm.value.items ?? [])
        .map((item) => ({
            name: String(item.name || '').trim(),
            quantity: Math.max(1, Number(item.quantity || 1)),
            price_copper: Math.max(1, Number(item.priceCopper || 1)),
            category: item.category || null,
            notes: item.notes || null,
        }))
        .filter((item) => item.name.length > 0);
    if (!npcTradeForm.value.name.trim() || !items.length) return;

    npcTradeBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.npc-trade-offer.upsert', props.party.id), {
            npc_trade_offer_id: selectedNpcTradeOfferId.value ? Number(selectedNpcTradeOfferId.value) : null,
            name: npcTradeForm.value.name.trim(),
            items,
        });
        if (response?.data?.offer) {
            upsertNpcTradeOfferLocal(response.data.offer);
            selectedNpcTradeOfferId.value = Number(response.data.offer.id);
        }
    } catch {
        // handled by backend flash/validation
    } finally {
        npcTradeBusy.value = false;
    }
};

const resolveNpcSellOffer = async (sellOffer, action) => {
    if (npcSellOfferBusy.value || !sellOffer?.id) return;
    npcSellOfferBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.npc-trade-offer.sell-offers.resolve', {
            party: props.party.id,
            sellOffer: sellOffer.id,
        }), { action });
        if (response?.data?.offer) {
            upsertNpcTradeOfferLocal(response.data.offer);
        }
    } catch {
        // handled by backend flash/validation
    } finally {
        npcSellOfferBusy.value = false;
    }
};

const openNpcTradeOffer = async () => {
    if (npcTradeBusy.value) return;
    npcTradeBusy.value = true;
    try {
        if (!selectedNpcTradeOfferId.value) return;
        const response = await window.axios.post(route('parties.npc-trade-offer.open', props.party.id), {
            npc_trade_offer_id: Number(selectedNpcTradeOfferId.value),
        });
        if (response?.data?.offer) {
            upsertNpcTradeOfferLocal(response.data.offer);
        }
    } catch {
        // handled by backend flash/validation
    } finally {
        npcTradeBusy.value = false;
    }
};

const closeNpcTradeOffer = async () => {
    if (npcTradeBusy.value) return;
    npcTradeBusy.value = true;
    try {
        if (!selectedNpcTradeOfferId.value) return;
        const response = await window.axios.post(route('parties.npc-trade-offer.close', props.party.id), {
            npc_trade_offer_id: Number(selectedNpcTradeOfferId.value),
        });
        if (response?.data?.offer !== undefined) {
            if (response.data.offer) {
                upsertNpcTradeOfferLocal(response.data.offer);
            }
        }
    } catch {
        // handled by backend flash/validation
    } finally {
        npcTradeBusy.value = false;
    }
};

const releaseNpcTradeSession = async () => {
    if (npcTradeBusy.value) return;
    npcTradeBusy.value = true;
    try {
        if (!selectedNpcTradeOfferId.value) return;
        const response = await window.axios.post(route('parties.npc-trade-offer.release', props.party.id), {
            npc_trade_offer_id: Number(selectedNpcTradeOfferId.value),
        });
        if (response?.data?.offer) {
            upsertNpcTradeOfferLocal(response.data.offer);
        }
    } catch {
        // handled by backend flash/validation
    } finally {
        npcTradeBusy.value = false;
    }
};

const submitWalletTransaction = async () => {
    if (!activeCharacter.value?.id || walletBusy.value) return;
    if (walletFormTotalCopper.value <= 0) return;

    walletBusy.value = true;
    try {
        const response = await window.axios.post(route('parties.wallet-transactions.store', props.party.id), {
            party_character_id: activeCharacter.value.id,
            type: walletForm.value.type,
            amount_copper: walletFormTotalCopper.value,
            note: walletForm.value.note?.trim() || null,
        });

        if (response?.data?.wallet) {
            upsertWallet(activeCharacter.value.id, response.data.wallet, response.data.transaction ?? null);
            walletForm.value.note = '';
            walletForm.value.amountGold = 0;
            walletForm.value.amountSilver = 0;
            walletForm.value.amountCopper = 0;
        }
    } catch {
        // handled by backend flash/validation
    } finally {
        walletBusy.value = false;
    }
};

const addInventoryItem = async () => {
    if (!activeCharacter.value?.id || !inventoryForm.value.name.trim() || inventoryBusy.value) return;

    inventoryBusy.value = true;
    try {
        const response = await createInventoryItem({
            name: inventoryForm.value.name.trim(),
            quantity: Number(inventoryForm.value.quantity || 1),
            category: inventoryForm.value.category?.trim() || null,
            notes: inventoryForm.value.notes?.trim() || null,
        });
        if (response?.data?.item) {
            replaceInventoryItem(activeCharacter.value.id, response.data.item);
        }
        resetInventoryForm();
    } catch {
        // handled by backend
    } finally {
        inventoryBusy.value = false;
    }
};

const createInventoryItem = async ({ name, quantity = 1, category = null, notes = null }) => {
    return window.axios.post(route('parties.inventory-items.store', props.party.id), {
        party_character_id: activeCharacter.value.id,
        name,
        quantity,
        category,
        notes,
    });
};

const setInventoryCategory = (category) => {
    inventoryForm.value.category = category;
};

const quickAddPresetItem = async (presetItem, category) => {
    if (!activeCharacter.value?.id || inventoryBusy.value) return;
    inventoryBusy.value = true;
    try {
        const response = await createInventoryItem({
            name: presetItem.name,
            quantity: Number(presetItem.quantity || 1),
            category,
            notes: null,
        });
        if (response?.data?.item) {
            replaceInventoryItem(activeCharacter.value.id, response.data.item);
        }
    } catch {
        // handled by backend
    } finally {
        inventoryBusy.value = false;
    }
};

const updateInventoryQuantity = async (item, delta) => {
    if (!activeCharacter.value?.id) return;
    const nextQuantity = Math.max(1, Number(item.quantity) + delta);
    if (nextQuantity === Number(item.quantity)) return;

    try {
        const response = await window.axios.patch(route('parties.inventory-items.update', {
            party: props.party.id,
            inventoryItem: item.id,
        }), {
            quantity: nextQuantity,
        });
        if (response?.data?.item) {
            replaceInventoryItem(activeCharacter.value.id, response.data.item);
        }
    } catch {
        // handled by backend
    }
};

const removeInventoryItem = async (item) => {
    if (!activeCharacter.value?.id) return;

    try {
        await window.axios.delete(route('parties.inventory-items.destroy', {
            party: props.party.id,
            inventoryItem: item.id,
        }));
        removeInventoryItemLocal(activeCharacter.value.id, item.id);
    } catch {
        // handled by backend
    }
};

onMounted(() => {
    if (!window.Echo) return;
    window.Echo.private(`party.${props.party.id}`)
        .listen('.party.talent-request.created', onRequestCreated)
        .listen('.party.talent-request.confirmed', onRequestConfirmed)
        .listen('.party.inventory-item.updated', onInventoryItemUpdated)
        .listen('.party.wallet.updated', onWalletUpdated)
        .listen('.party.npc-trade.updated', onNpcTradeUpdated);
});

onBeforeUnmount(() => {
    if (window.Echo) {
        window.Echo.leave(`party.${props.party.id}`);
    }
});
</script>

<template>
    <Head :title="`${party.name} - Spielleiter`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="h4 m-0">{{ party.name }} - Spielleiter</h2>
        </template>

        <div class="eldoria-page">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <div class="text-uppercase small text-muted eldoria-kicker">Spielleiter Ansicht</div>
                    <div class="text-muted">Talente auswählen und live an Spieler anfordern.</div>
                </div>
                <Link :href="route('parties.end', party.id)" method="post" as="button" class="btn btn-outline-danger">
                    Party beenden
                </Link>
            </div>

            <div v-if="playerCharacters.length === 0" class="alert alert-warning border-0">Keine Charaktere gefunden.</div>

            <div v-else class="card shadow-sm border-0 eldoria-panel">
                <div class="card-body p-3 p-md-4">
                    <ul class="nav nav-tabs eldoria-nav-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" :class="{ active: isNpcTradeTopTabActive }" @click="activeCharacterId = NPC_TRADE_TOP_TAB">
                            NPC Handel
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" :class="{ active: isMapTopTabActive }" @click="activeCharacterId = MAP_TOP_TAB">
                            Karte
                        </button>
                    </li>
                    <li v-for="entry in playerCharacters" :key="entry.id" class="nav-item" role="presentation">
                        <button type="button" class="nav-link" :class="{ active: activeCharacterId === entry.id }" @click="activeCharacterId = entry.id">
                            {{ entry.user.name }}
                        </button>
                    </li>
                    </ul>

                    <div v-if="isNpcTradeTopTabActive" class="card shadow-sm border-0 eldoria-panel">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-uppercase small text-muted mb-2 eldoria-kicker">NPC Handel</div>
                            <h3 class="h5 mb-3 eldoria-title">NPC konfigurieren & freigeben</h3>
                            <div class="wallet-panel p-3 p-md-4">
                                <div class="mb-3">
                                    <div class="small text-uppercase text-muted mb-2 eldoria-kicker-soft">Händler</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button
                                            v-for="offer in npcTradeStateList"
                                            :key="`npc-offer-${offer.id}`"
                                            type="button"
                                            class="btn btn-sm"
                                            :class="Number(selectedNpcTradeOfferId) === Number(offer.id) ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="selectedNpcTradeOfferId = offer.id"
                                        >
                                            {{ offer.name || `Händler ${offer.id}` }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success" @click="createNpcTradeMerchant">
                                            + Neuer Händler
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                                    <div class="small text-uppercase text-muted eldoria-kicker-soft">Status</div>
                                    <span class="badge" :class="npcTradeState?.isOpen ? 'text-bg-success' : 'text-bg-secondary'">
                                        {{ npcTradeState?.isOpen ? 'Freigegeben' : 'Nicht freigegeben' }}
                                    </span>
                                </div>
                                <div v-if="!npcTradeState && selectedNpcTradeOfferId" class="alert alert-warning py-2 px-3 small">
                                    Händler nicht gefunden.
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small mb-1">NPC Name</label>
                                        <input v-model="npcTradeForm.name" type="text" class="form-control form-control-sm" placeholder="z.B. Händler Borin">
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <label class="form-label small mb-1">Kategorien</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button
                                                v-for="category in inventoryPresetCategories"
                                                :key="`npc-category-${category}`"
                                                type="button"
                                                class="btn btn-sm"
                                                :class="npcTradeForm.category === category ? 'btn-primary' : 'btn-outline-secondary'"
                                                @click="setNpcTradeCategory(category)"
                                            >
                                                {{ category }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Schnellauswahl</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button
                                            v-for="(presetItem, idx) in (inventoryPresetItemsByCategory[npcTradeForm.category] ?? [])"
                                            :key="`npc-preset-${npcTradeForm.category}-${presetItem.name}-${idx}`"
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            @click="quickAddNpcPresetItem(presetItem, npcTradeForm.category)"
                                        >
                                            + {{ presetItem.name }} <span class="opacity-75">x{{ presetItem.quantity }}</span>
                                        </button>
                                        <span
                                            v-if="!(inventoryPresetItemsByCategory[npcTradeForm.category] ?? []).length"
                                            class="text-muted small"
                                        >
                                            Wähle oben eine Kategorie für Vorschläge.
                                        </span>
                                    </div>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-12 col-md-3">
                                        <input v-model="npcTradeForm.itemName" type="text" class="form-control form-control-sm" placeholder="Itemname">
                                    </div>
                                    <div class="col-6 col-md-1">
                                        <input v-model.number="npcTradeForm.quantity" type="number" min="1" max="999" class="form-control form-control-sm" placeholder="Menge">
                                    </div>
                                    <div class="col-4 col-md-1">
                                        <input v-model.number="npcTradeForm.priceGold" type="number" min="0" class="form-control form-control-sm" placeholder="G">
                                    </div>
                                    <div class="col-4 col-md-1">
                                        <input v-model.number="npcTradeForm.priceSilver" type="number" min="0" class="form-control form-control-sm" placeholder="S">
                                    </div>
                                    <div class="col-4 col-md-1">
                                        <input v-model.number="npcTradeForm.priceCopper" type="number" min="0" class="form-control form-control-sm" placeholder="K">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <input v-model="npcTradeForm.category" type="text" class="form-control form-control-sm" placeholder="Kategorie">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <button type="button" class="btn btn-sm btn-primary w-100" @click="addNpcTradeItem">
                                            Item hinzufügen
                                        </button>
                                    </div>
                                    <div class="col-12">
                                        <input v-model="npcTradeForm.notes" type="text" class="form-control form-control-sm" placeholder="Notiz (optional)">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div v-if="!(npcTradeForm.items ?? []).length" class="text-muted small">
                                        Noch keine NPC-Items hinzugefügt.
                                    </div>
                                    <div v-else class="d-flex flex-column gap-2">
                                        <div
                                            v-for="(item, index) in (npcTradeForm.items ?? [])"
                                            :key="`npc-config-item-${item.id}-${index}`"
                                            class="wallet-transaction-row d-flex justify-content-between align-items-start gap-2"
                                        >
                                            <div>
                                                <div class="small fw-semibold">{{ item.name }}</div>
                                                <div class="small text-muted">
                                                    {{ item.category || 'Allgemein' }} · {{ formatCopper(item.priceCopper) }}<span v-if="item.notes"> · {{ item.notes }}</span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-1 flex-wrap justify-content-end">
                                                <input
                                                    :value="item.priceCopper"
                                                    type="number"
                                                    min="1"
                                                    class="form-control form-control-sm"
                                                    style="width: 86px;"
                                                    @input="updateNpcTradeItemPrice(index, $event.target.value)"
                                                >
                                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="updateNpcTradeItemQuantity(index, -1)">-</button>
                                                <span class="px-2 small fw-semibold">{{ item.quantity }}</span>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="updateNpcTradeItemQuantity(index, 1)">+</button>
                                                <button type="button" class="btn btn-sm btn-outline-danger ms-1" @click="removeNpcTradeItem(index)">x</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap align-items-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary" :disabled="npcTradeBusy" @click="saveNpcTradeOffer">
                                        Speichern
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" :disabled="npcTradeBusy || !selectedNpcTradeOfferId" @click="openNpcTradeOffer">
                                        Zum Handeln freigeben
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" :disabled="npcTradeBusy || !selectedNpcTradeOfferId" @click="closeNpcTradeOffer">
                                        Freigabe schließen
                                    </button>
                                    <button
                                        v-if="npcTradeState?.activePartyCharacterId"
                                        type="button"
                                        class="btn btn-sm btn-outline-warning"
                                        :disabled="npcTradeBusy"
                                        @click="releaseNpcTradeSession"
                                    >
                                        Aktiven Handel freigeben
                                    </button>
                                    <span class="small text-muted" v-if="npcTradeState?.activeCharacterName">
                                        Aktiver Handel mit: {{ npcTradeState.activeCharacterName }}
                                    </span>
                                    <button
                                        v-if="npcTradeState?.activePartyCharacterId"
                                        type="button"
                                        class="btn btn-sm btn-outline-success"
                                        @click="npcTradeSessionModalOpen = true"
                                    >
                                        Handel ansehen
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="isMapTopTabActive" class="card shadow-sm border-0 eldoria-panel">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Weltkarte</div>
                            <h3 class="h5 mb-3 eldoria-title">Eldoria</h3>
                            <InteractiveWorldMap
                                src="/images/EldoriaMap.png"
                                alt="Eldoria Weltkarte"
                                :locations="mapLocations"
                            />
                        </div>
                    </div>

                    <div
                        v-if="npcTradeSessionModalOpen && npcTradeState?.activePartyCharacterId"
                        class="wallet-modal-backdrop"
                        @click.self="npcTradeSessionModalOpen = false"
                    >
                        <div class="trade-modal-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="h6 mb-0">NPC Handel: {{ npcTradeState?.name }}</h4>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="npcTradeSessionModalOpen = false">
                                    Schließen
                                </button>
                            </div>
                            <div class="row g-3">
                                <div class="col-12 col-lg-6">
                                    <div class="trade-column p-3 h-100">
                                        <div class="fw-semibold mb-2">NPC: {{ npcTradeState?.name }}</div>
                                        <div class="small text-uppercase text-muted mb-2">NPC Inventar</div>
                                        <div v-if="!(npcTradeState?.items ?? []).length" class="small text-muted">Leer</div>
                                        <ul v-else class="small mb-0 ps-3">
                                            <li v-for="item in (npcTradeState?.items ?? [])" :key="`owner-npc-item-${item.id}`">
                                                {{ item.name }} x{{ item.quantity }} · {{ item.priceDisplay }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="trade-column p-3 h-100">
                                        <div class="fw-semibold mb-2">
                                            Spieler: {{ activeNpcTradeCharacter?.user?.name ?? activeNpcTradeCharacter?.name ?? npcTradeState?.activeCharacterName }}
                                        </div>
                                        <div class="small text-uppercase text-muted mb-2">Spieler Inventar</div>
                                        <div v-if="!(activeNpcTradeCharacter?.inventoryItems ?? []).length" class="small text-muted">Leer</div>
                                        <ul v-else class="small mb-3 ps-3">
                                            <li v-for="item in (activeNpcTradeCharacter?.inventoryItems ?? [])" :key="`owner-player-item-${item.id}`">
                                                {{ item.name }} x{{ item.quantity }}
                                            </li>
                                        </ul>
                                        <div class="small text-uppercase text-muted mb-2">Verkaufsangebote</div>
                                        <div v-if="!npcPendingSellOffers.length" class="small text-muted">Keine offenen Angebote.</div>
                                        <div v-else class="d-flex flex-column gap-2">
                                            <div
                                                v-for="offer in npcPendingSellOffers"
                                                :key="`owner-sell-offer-${offer.id}`"
                                                class="wallet-transaction-row d-flex justify-content-between align-items-center gap-2"
                                            >
                                                <div class="small">
                                                    <div class="fw-semibold">{{ offer.partyCharacterName }}: {{ offer.itemName }} x{{ offer.quantity }}</div>
                                                    <div class="text-muted">{{ offer.amountDisplay }}</div>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-sm btn-outline-success" :disabled="npcSellOfferBusy" @click="resolveNpcSellOffer(offer, 'accept')">
                                                        Annehmen
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" :disabled="npcSellOfferBusy" @click="resolveNpcSellOffer(offer, 'reject')">
                                                        Ablehnen
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="activeCharacter">
                        <ul class="nav nav-tabs eldoria-nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button
                                type="button"
                                class="nav-link"
                                :class="{ active: activeDetailTab === 'character' }"
                                @click="activeDetailTab = 'character'"
                            >
                                Charakter
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                type="button"
                                class="nav-link"
                                :class="{ active: activeDetailTab === 'inventory' }"
                                @click="activeDetailTab = 'inventory'"
                            >
                                Inventar
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                type="button"
                                class="nav-link"
                                :class="{ active: activeDetailTab === 'notes' }"
                                @click="activeDetailTab = 'notes'"
                            >
                                Notizen
                            </button>
                        </li>
                        </ul>

                        <div v-if="activeDetailTab === 'character'" class="row g-4">
                            <div class="col-12 col-xl-8">
                            <h4 class="h5 mb-1">{{ activeCharacter.name }}</h4>
                            <div class="text-muted mb-2">{{ activeCharacter.race }} · {{ activeCharacter.class_name }} · {{ activeCharacter.gender }}</div>
                            <div class="text-muted mb-3">{{ activeCharacter.age }} Jahre · {{ activeCharacter.height_cm }} cm · {{ activeCharacter.weight_kg }} kg</div>

                            <div class="mb-4">
                                <div class="small text-uppercase text-muted mb-2 eldoria-kicker-soft">Traits</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <span v-for="trait in activeCharacter.traits" :key="trait" class="badge text-bg-light border eldoria-trait">{{ trait }}</span>
                                </div>
                            </div>

                            <div>
                                <div class="small text-uppercase text-muted mb-2 eldoria-kicker-soft">Talente anfordern</div>
                                <div class="row g-3">
                                    <div v-for="group in talentGroups" :key="group.category" class="col-12 col-lg-6">
                                        <div class="border rounded p-3 bg-light-subtle h-100 eldoria-subpanel">
                                            <div class="fw-semibold small mb-2 eldoria-subtitle">{{ group.category }}</div>
                                            <div
                                                v-for="talent in group.items"
                                                :key="talent.key"
                                                class="d-flex justify-content-between align-items-center border rounded px-3 py-2 bg-white mb-2 eldoria-row"
                                            >
                                                <label class="d-flex align-items-center gap-2 m-0">
                                                    <input
                                                        type="checkbox"
                                                        :checked="isTalentSelected(activeCharacter.user_id, talent.key)"
                                                        @change="toggleTalentSelection(activeCharacter.user_id, talent.key)"
                                                    >
                                                    <span>{{ talent.label }}</span>
                                                </label>
                                                <strong>{{ getTalentValue(talent.key) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 d-flex gap-2 align-items-center flex-wrap">
                                    <select
                                        class="form-select form-select-sm"
                                        style="max-width: 170px;"
                                        :value="ensureModifierState(activeCharacter.user_id).type"
                                        @change="ensureModifierState(activeCharacter.user_id).type = $event.target.value"
                                    >
                                        <option value="none">Normal</option>
                                        <option value="easy">Erleichtert</option>
                                        <option value="hard">Erschwert</option>
                                    </select>
                                    <input
                                        type="number"
                                        min="0"
                                        max="5"
                                        class="form-control form-control-sm"
                                        style="max-width: 90px;"
                                        :disabled="ensureModifierState(activeCharacter.user_id).type === 'none'"
                                        :value="ensureModifierState(activeCharacter.user_id).points"
                                        @input="ensureModifierState(activeCharacter.user_id).points = Math.min(5, Math.max(0, Number($event.target.value || 0)))"
                                    >
                                    <button type="button" class="btn btn-primary" @click="sendTalentRequest">
                                        Talentanforderung senden
                                    </button>
                                    <span class="small text-muted">
                                        Ausgewählt: {{ (selectedTalentsByUser[activeCharacter.user_id] ?? []).length }}
                                    </span>
                                </div>
                            </div>
                            </div>

                            <div class="col-12 col-xl-4">
                                <div class="card shadow-sm border-0 mb-4 eldoria-panel">
                                <div class="card-body p-3">
                                    <img
                                        v-if="displayCharacterImage"
                                        :src="displayCharacterImage"
                                        :alt="`Charakterbild von ${activeCharacter.name}`"
                                        class="img-fluid rounded border eldoria-portrait"
                                        @error="handleCharacterImageError"
                                    >
                                    <div v-else class="text-muted small">Kein Charakterbild verfügbar.</div>
                                </div>
                            </div>

                                <div class="card shadow-sm border-0 eldoria-panel">
                                <div class="card-body p-3">
                                    <div class="small text-uppercase text-muted mb-2 eldoria-kicker-soft">Anfragen für {{ activeCharacter.user.name }}</div>
                                    <div v-if="!latestActiveRequest" class="text-muted small">Keine Anfragen vorhanden.</div>
                                    <div v-else class="border rounded p-2 bg-light-subtle eldoria-subpanel">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="small">
                                                {{ latestActiveRequest.talents.map((t) => t.label).join(', ') }}
                                                <span class="text-muted"> · {{ modifierLabel(latestActiveRequest) }}</span>
                                            </div>
                                            <span class="badge" :class="requestResultClass(latestActiveRequest)">
                                                {{ requestResultText(latestActiveRequest) }}
                                            </span>
                                        </div>
                                        <div class="small mt-2 d-flex flex-column gap-1">
                                            <div
                                                v-for="talent in latestActiveRequest.talents"
                                                :key="`${latestActiveRequest.id}:${talent.key}`"
                                                class="d-flex justify-content-between align-items-center border rounded px-2 py-1 bg-white eldoria-row"
                                            >
                                                <span>{{ talent.label }}</span>
                                                <span class="d-flex align-items-center gap-2">
                                                    <span v-if="talent.rolledAt" class="text-muted">
                                                        {{ talent.rolledValue }} / {{ talent.targetValue }}
                                                    </span>
                                                    <span class="badge" :class="talentResultClass(talent)">
                                                        {{ talentResultText(talent) }}
                                                    </span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>

                        <div v-else-if="activeDetailTab === 'inventory'" class="card shadow-sm border-0 eldoria-panel">
                            <div class="card-body p-4 p-md-5">
                                <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Inventar</div>
                                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                                    <h3 class="h5 mb-0 eldoria-title">Inventar von {{ activeCharacter.name }}</h3>
                                    <div class="wallet-bag-pill" title="Charakterbeutel" role="button" tabindex="0" @click="walletModalOpen = true">
                                        <span class="wallet-bag-icon" aria-hidden="true">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M10.1 2h3.8l.5 2.2h-4.8L10.1 2zm-3 4.2h9.8c2.7 0 5 2.2 5 5v6.2c0 2.5-2 4.6-4.6 4.6H6.7c-2.5 0-4.6-2-4.6-4.6v-6.2c0-2.8 2.2-5 5-5zm1.2 4.1c0 .7.5 1.2 1.2 1.2h5c.7 0 1.2-.6 1.2-1.2s-.5-1.2-1.2-1.2h-5c-.7 0-1.2.5-1.2 1.2z"/>
                                            </svg>
                                        </span>
                                        <span>{{ activeWallet?.display ?? '0G 0S 0K' }}</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-uppercase text-muted mb-2 eldoria-kicker-soft">Kategorien</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button
                                            v-for="category in inventoryPresetCategories"
                                            :key="category"
                                            type="button"
                                            class="btn btn-sm"
                                            :class="inventoryForm.category === category ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="setInventoryCategory(category)"
                                        >
                                            {{ category }}
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="small text-uppercase text-muted mb-2 eldoria-kicker-soft">Standard-Items</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button
                                            v-for="(presetItem, idx) in (inventoryPresetItemsByCategory[inventoryForm.category] ?? [])"
                                            :key="`${inventoryForm.category}:${presetItem.name}:${idx}`"
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            :disabled="inventoryBusy"
                                            @click="quickAddPresetItem(presetItem, inventoryForm.category)"
                                        >
                                            + {{ presetItem.name }} <span class="opacity-75">x{{ presetItem.quantity }}</span>
                                        </button>
                                        <span
                                            v-if="!(inventoryPresetItemsByCategory[inventoryForm.category] ?? []).length"
                                            class="text-muted small"
                                        >
                                            Wähle oben eine Kategorie, um Vorschläge zu sehen.
                                        </span>
                                    </div>
                                </div>

                                <div class="row g-2 mb-4">
                                    <div class="col-12 col-md-5">
                                        <input v-model="inventoryForm.name" type="text" class="form-control" placeholder="Gegenstand">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <input v-model.number="inventoryForm.quantity" type="number" min="1" max="999" class="form-control" placeholder="Menge">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <input v-model="inventoryForm.category" type="text" class="form-control" placeholder="Kategorie">
                                    </div>
                                    <div class="col-12 col-md-2">
                                        <button type="button" class="btn btn-primary w-100" :disabled="inventoryBusy" @click="addInventoryItem">
                                            Hinzufügen
                                        </button>
                                    </div>
                                    <div class="col-12">
                                        <input v-model="inventoryForm.notes" type="text" class="form-control" placeholder="Notiz (optional)">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mb-4">
                                    <span class="small text-muted">Klicke auf die Wallet oben, um Transaktionen zu öffnen.</span>
                                </div>

                                <div v-if="walletModalOpen" class="wallet-modal-backdrop" @click.self="walletModalOpen = false">
                                    <div class="wallet-modal-card">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h4 class="h6 mb-0">Wallet verwalten</h4>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="walletModalOpen = false">
                                                Schließen
                                            </button>
                                        </div>
                                        <div class="small text-muted mb-3">1G = 10S = 100K</div>
                                        <div class="row g-2 align-items-end mb-3">
                                            <div class="col-12 col-md-3">
                                                <label class="form-label small mb-1">Typ</label>
                                                <select v-model="walletForm.type" class="form-select form-select-sm">
                                                    <option value="in">IN</option>
                                                    <option value="out">OUT</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-2">
                                                <label class="form-label small mb-1">Gold</label>
                                                <input v-model.number="walletForm.amountGold" type="number" min="0" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-12 col-md-2">
                                                <label class="form-label small mb-1">Silber</label>
                                                <input v-model.number="walletForm.amountSilver" type="number" min="0" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-12 col-md-2">
                                                <label class="form-label small mb-1">Kupfer</label>
                                                <input v-model.number="walletForm.amountCopper" type="number" min="0" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <label class="form-label small mb-1">Notiz</label>
                                                <input v-model="walletForm.note" type="text" class="form-control form-control-sm" placeholder="optional">
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                                    <div class="small text-muted">Gesamt: {{ formatCopper(walletFormTotalCopper) }}</div>
                                                    <button type="button" class="btn btn-sm btn-primary" :disabled="walletBusy || walletFormTotalCopper <= 0" @click="submitWalletTransaction">
                                                        Buchen
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <hr class="my-3">
                                        <div class="small text-uppercase text-muted mb-2 eldoria-kicker-soft">Transaktionen</div>
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

                                <div class="bag-area p-3 p-md-4">
                                    <div class="bag-mouth mb-3">Jutebeutel</div>
                                    <div v-if="(activeCharacter.inventoryItems ?? []).length === 0" class="text-muted small">
                                        Dieser Beutel ist leer.
                                    </div>
                                    <div v-else class="row g-2">
                                        <div v-for="item in activeCharacter.inventoryItems" :key="item.id" class="col-12 col-md-6">
                                            <div class="bag-item d-flex justify-content-between align-items-start gap-2">
                                                <div>
                                                    <div class="fw-semibold">{{ item.name }}</div>
                                                    <div class="small text-muted">
                                                        {{ item.category || 'Allgemein' }}<span v-if="item.notes"> · {{ item.notes }}</span>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center gap-1">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="updateInventoryQuantity(item, -1)">-</button>
                                                    <span class="px-2 small fw-semibold">{{ item.quantity }}</span>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="updateInventoryQuantity(item, 1)">+</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger ms-1" @click="removeInventoryItem(item)">x</button>
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
                            <p class="text-muted mb-0">Hier kannst du später Spielleiter-Notizen zum ausgewählten Spieler verwalten.</p>
                        </div>
                    </div>
                    </div>
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
    width: min(900px, 100%);
    max-height: 80vh;
    overflow: auto;
    background: #fff8ee;
    border: 1px solid rgba(110, 74, 35, 0.35);
    border-radius: 12px;
    padding: 1rem;
}

.trade-column {
    border: 1px solid rgba(110, 74, 35, 0.24);
    border-radius: 8px;
    background: rgba(255, 250, 241, 0.86);
}
</style>
