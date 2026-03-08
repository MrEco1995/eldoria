<script setup>
const clampHp = (value, max) => {
    const safeMax = Math.max(1, Number(max ?? 1));
    const safeValue = Math.max(0, Math.min(safeMax, Number(value ?? 0)));
    return { safeValue, safeMax };
};

const hpPercentWidth = (value, max) => {
    const { safeValue, safeMax } = clampHp(value, max);
    return `${((safeValue / safeMax) * 100).toFixed(2)}%`;
};

const hpPercentLabel = (value, max) => {
    const { safeValue, safeMax } = clampHp(value, max);
    return Math.round((safeValue / safeMax) * 100);
};

defineProps({
    character: { type: Object, required: true },
    displayCharacterImage: { type: String, default: null },
    talentGroups: { type: Array, default: () => [] },
    getTalentValue: { type: Function, required: true },
    latestMyRequest: { type: Object, default: null },
    difficultyLabel: { type: Function, required: true },
    modifierLabel: { type: Function, required: true },
    rollBreakdown: { type: Function, required: true },
    requestResultClass: { type: Function, required: true },
    requestResultText: { type: Function, required: true },
    isRolled: { type: Function, required: true },
    resultClass: { type: Function, required: true },
    resultText: { type: Function, required: true },
    rollingKeys: { type: Object, default: () => ({}) },
    isGlobalRolling: { type: Boolean, default: false },
    onRollTalent: { type: Function, required: true },
    handleCharacterImageError: { type: Function, required: true },
});
</script>

<template>
    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card shadow-sm border-0 eldoria-panel">
                <div class="card-body p-4 p-md-5">
                    <div class="text-uppercase small text-muted mb-2 eldoria-kicker">Charakterbogen</div>
                    <h3 class="h4 mb-3 eldoria-title">{{ character.name }}</h3>

                    <div class="text-muted mb-3">{{ character.race }} · {{ character.class_name }} · {{ character.gender }}</div>
                    <div class="text-muted mb-4">{{ character.age }} Jahre · {{ character.height_cm }} cm · {{ character.weight_kg }} kg</div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between small mb-1">
                            <strong>Temp HP</strong>
                            <span>+{{ character.hpTemp ?? 0 }}</span>
                        </div>
                        <div class="progress mb-2" style="height: 0.55rem;">
                            <div
                                class="progress-bar bg-info"
                                role="progressbar"
                                :style="{ width: hpPercentWidth(character.hpTemp, character.hpMax) }"
                            ></div>
                        </div>
                        <div class="d-flex justify-content-between small mb-1">
                            <strong>HP {{ character.hpCurrent ?? 0 }} / {{ character.hpMax ?? 0 }}</strong>
                            <span>{{ hpPercentLabel(character.hpCurrent, character.hpMax) }}%</span>
                        </div>
                        <div class="progress" style="height: 0.75rem;">
                            <div
                                class="progress-bar"
                                :class="(character.hpMax ?? 0) > 0 && ((character.hpCurrent ?? 0) / (character.hpMax ?? 1)) <= 0.3
                                    ? 'bg-danger'
                                    : ((character.hpMax ?? 0) > 0 && ((character.hpCurrent ?? 0) / (character.hpMax ?? 1)) <= 0.6 ? 'bg-warning' : 'bg-success')"
                                role="progressbar"
                                :style="{ width: hpPercentWidth(character.hpCurrent, character.hpMax) }"
                            ></div>
                        </div>
                    </div>

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
                    <div v-else class="text-muted small">Kein Charakterbild verfuegbar.</div>
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
                                <span class="text-muted"> / {{ difficultyLabel(latestMyRequest) }} / {{ modifierLabel(latestMyRequest) }}</span>
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
                                        <div v-if="isRolled(talent)" class="text-muted d-inline-flex align-items-center gap-1">
                                            <span>Gesamt: {{ talent.rolledValue }} / SG: {{ talent.targetValue }}</span>
                                            <span
                                                class="roll-breakdown-trigger"
                                                :title="rollBreakdown(talent, latestMyRequest)"
                                                aria-label="Wurfdetails"
                                            >
                                                i
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge" :class="resultClass(talent)">
                                            {{ resultText(talent) }}
                                        </span>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary"
                                            :disabled="isGlobalRolling || isRolled(talent) || rollingKeys[`${latestMyRequest.id}:${talent.key}`]"
                                            @click="onRollTalent(latestMyRequest, talent)"
                                        >
                                            W20
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
</template>

<style scoped>
.roll-breakdown-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    border-radius: 999px;
    font-size: 0.7rem;
    line-height: 1;
    font-weight: 700;
    cursor: help;
    color: #5f3f1d;
    background: #f5deb5;
    border: 1px solid #b9894f;
}
</style>
