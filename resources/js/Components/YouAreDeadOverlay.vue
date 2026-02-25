<script setup>
defineProps({
    show: { type: Boolean, default: false },
    message: { type: String, default: 'Du bist Tot' },
});
</script>

<template>
    <div v-if="show" class="dead-overlay" role="alert" aria-live="assertive" aria-label="Charakter ist tot">
        <div class="dead-overlay-panel">
            <div class="dead-overlay-kicker">Charakterstatus</div>
            <div class="dead-overlay-text">{{ message }}</div>
            <div class="dead-overlay-subtext">Du kannst erst wieder handeln, wenn deine HP über 0 liegen.</div>
        </div>
    </div>
</template>

<style scoped>
.dead-overlay {
    position: fixed;
    inset: 0;
    z-index: 3000;
    background:
        radial-gradient(circle at 50% 18%, rgba(170, 24, 24, 0.18), transparent 40%),
        rgba(13, 6, 6, 0.82);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.dead-overlay-panel {
    text-align: center;
    color: #f8dede;
    text-shadow: 0 6px 24px rgba(0, 0, 0, 0.65);
    max-width: 760px;
}

.dead-overlay-kicker {
    text-transform: uppercase;
    letter-spacing: 0.16em;
    font-size: 0.8rem;
    opacity: 0.82;
    margin-bottom: 0.5rem;
}

.dead-overlay-text {
    font-size: clamp(2.8rem, 8vw, 7rem);
    font-weight: 800;
    letter-spacing: 0.04em;
    line-height: 0.95;
    color: #ffb6b6;
}

.dead-overlay-subtext {
    margin-top: 0.8rem;
    font-size: 0.95rem;
    opacity: 0.9;
}
</style>
