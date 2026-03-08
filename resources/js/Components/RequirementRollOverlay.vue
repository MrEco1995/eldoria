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
    overflow: hidden;
}

.roll-overlay-die {
    width: min(360px, 72vw);
    margin-left: -25vw;
    animation: rollAcross 1.5s cubic-bezier(0.12, 0.8, 0.22, 1) forwards;
    transform-origin: center;
}

.roll-overlay-label {
    text-align: center;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #f4e8d1;
}

@keyframes rollAcross {
    0% {
        transform: translateX(0) rotate(0deg) scale(0.8);
    }
    50% {
        transform: translateX(60vw) rotate(380deg) scale(1);
    }
    100% {
        transform: translateX(120vw) rotate(760deg) scale(0.92);
    }
}
</style>
