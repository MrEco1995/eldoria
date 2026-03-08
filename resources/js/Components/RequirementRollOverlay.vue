<script setup>
import Dice3D from '@/Components/Dice3D.vue';

defineProps({
    show: { type: Boolean, default: false },
    rollToken: { type: [Number, String], default: null },
    sides: {
        type: Number,
        default: 20,
        validator: (value) => [6, 20].includes(Number(value)),
    },
});

const emit = defineEmits(['rolled']);

const handleRolled = (payload) => {
    emit('rolled', payload);
};
</script>

<template>
    <div v-if="show" class="roll-overlay" role="status" aria-live="polite">
        <div class="roll-overlay-track">
            <div class="roll-overlay-die">
                <Dice3D
                    :show-controls="false"
                    :cinematic="true"
                    :auto-roll-token="rollToken"
                    :auto-roll-sides="sides"
                    :initial-sides="sides"
                    @rolled="handleRolled"
                />
            </div>
        </div>
        <div class="roll-overlay-label">W{{ sides }} rollt...</div>
    </div>
</template>

<style scoped>
.roll-overlay {
    position: fixed;
    inset: 0;
    z-index: 2100;
    background: rgba(10, 14, 20, 0.46);
    backdrop-filter: blur(1px);
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 1rem;
    pointer-events: auto;
}

.roll-overlay-track {
    width: 100%;
    max-width: 1100px;
    margin: 0 auto;
}

.roll-overlay-die {
    width: min(980px, 94vw);
    margin: 0 auto;
}

.roll-overlay-label {
    text-align: center;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #f4e8d1;
}
</style>
