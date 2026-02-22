<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    src: {
        type: String,
        required: true,
    },
    alt: {
        type: String,
        default: 'Karte',
    },
    locations: {
        type: Array,
        default: () => [],
    },
    viewportMinHeight: {
        type: String,
        default: '70vh',
    },
    showControls: {
        type: Boolean,
        default: true,
    },
    showDetails: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['select-location']);

const containerRef = ref(null);
const scale = ref(1);
const minScale = 1;
const maxScale = 4;
const translateX = ref(0);
const translateY = ref(0);
const isDragging = ref(false);
const selectedLocationId = ref(null);
const pointerState = ref({
    startX: 0,
    startY: 0,
    originX: 0,
    originY: 0,
});

const transformStyle = computed(() => ({
    transform: `translate(${translateX.value}px, ${translateY.value}px) scale(${scale.value})`,
}));

const viewportStyle = computed(() => ({
    minHeight: props.viewportMinHeight,
}));

const canvasStyle = computed(() => ({
    minHeight: props.viewportMinHeight,
}));

const visibleLocations = computed(() => {
    return props.locations.filter((entry) => Number(entry?.minZoom ?? 1) <= scale.value);
});

const hiddenSettlementCount = computed(() => {
    return props.locations.filter((entry) => Number(entry?.minZoom ?? 1) > scale.value).length;
});

const selectedLocation = computed(() => {
    return props.locations.find((entry) => String(entry.id) === String(selectedLocationId.value)) ?? null;
});

const clamp = (value, minValue, maxValue) => {
    return Math.min(maxValue, Math.max(minValue, value));
};

const zoomBy = (delta) => {
    scale.value = clamp(scale.value + delta, minScale, maxScale);
};

const resetView = () => {
    scale.value = 1;
    translateX.value = 0;
    translateY.value = 0;
};

const onWheel = (event) => {
    event.preventDefault();
    const direction = event.deltaY > 0 ? -0.15 : 0.15;
    zoomBy(direction);
};

const onPointerDown = (event) => {
    if (event.pointerType === 'mouse' && event.button !== 0) {
        return;
    }
    pointerState.value = {
        startX: event.clientX,
        startY: event.clientY,
        originX: translateX.value,
        originY: translateY.value,
    };
    isDragging.value = true;
    containerRef.value?.setPointerCapture?.(event.pointerId);
};

const onPointerMove = (event) => {
    if (!isDragging.value) {
        return;
    }
    const offsetX = event.clientX - pointerState.value.startX;
    const offsetY = event.clientY - pointerState.value.startY;
    translateX.value = pointerState.value.originX + offsetX;
    translateY.value = pointerState.value.originY + offsetY;
};

const onPointerEnd = (event) => {
    isDragging.value = false;
    containerRef.value?.releasePointerCapture?.(event.pointerId);
};

const selectLocation = (location) => {
    selectedLocationId.value = location.id;
    emit('select-location', location);
};
</script>

<template>
    <div class="interactive-map">
        <div v-if="showControls" class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
            <div class="small text-muted">
                Scrollen zum Zoomen, ziehen zum Bewegen. Zoom: x{{ scale.toFixed(1) }}
                <span v-if="hiddenSettlementCount" class="ms-2">
                    (weitere Orte ab höherem Zoom)
                </span>
            </div>
            <div class="btn-group btn-group-sm" role="group" aria-label="Kartensteuerung">
                <button type="button" class="btn btn-outline-secondary" @click="zoomBy(0.2)">+</button>
                <button type="button" class="btn btn-outline-secondary" @click="zoomBy(-0.2)">-</button>
                <button type="button" class="btn btn-outline-secondary" @click="resetView">Reset</button>
            </div>
        </div>

        <div
            ref="containerRef"
            class="map-viewport border rounded-3 overflow-hidden position-relative"
            :style="viewportStyle"
            @wheel="onWheel"
            @pointerdown="onPointerDown"
            @pointermove="onPointerMove"
            @pointerup="onPointerEnd"
            @pointercancel="onPointerEnd"
            @pointerleave="onPointerEnd"
        >
            <div class="map-canvas" :style="[transformStyle, canvasStyle]">
                <img :src="src" :alt="alt" class="map-image" draggable="false" />

                <button
                    v-for="location in visibleLocations"
                    :key="location.id"
                    type="button"
                    class="map-marker"
                    :class="{
                        active: String(location.id) === String(selectedLocationId),
                        village: location.type === 'village',
                    }"
                    :style="{ left: `${location.x}%`, top: `${location.y}%` }"
                    :title="location.name"
                    @click.stop="selectLocation(location)"
                >
                    <span class="marker-dot"></span>
                    <span class="marker-label">{{ location.name }}</span>
                </button>
            </div>
        </div>

        <div v-if="showDetails" class="mt-3 p-3 rounded border bg-light-subtle">
            <div v-if="selectedLocation">
                <div class="fw-semibold">{{ selectedLocation.name }}</div>
                <div class="small text-muted">
                    {{ selectedLocation.description || 'Keine Beschreibung hinterlegt.' }}
                </div>
            </div>
            <div v-else class="small text-muted">
                Wähle einen Marker auf der Karte.
            </div>
        </div>
    </div>
</template>

<style scoped>
.map-viewport {
    background: #0e1626;
    touch-action: none;
    cursor: grab;
}

.map-viewport:active {
    cursor: grabbing;
}

.map-canvas {
    width: 100%;
    height: 100%;
    min-height: inherit;
    position: relative;
    transform-origin: center center;
    user-select: none;
}

.map-image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    pointer-events: none;
}

.map-marker {
    position: absolute;
    transform: translate(-50%, -100%);
    border: 0;
    background: transparent;
    padding: 0;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: 0.2rem;
}

.marker-dot {
    width: 0.9rem;
    height: 0.9rem;
    border-radius: 999px;
    background: #ef4444;
    border: 2px solid #ffffff;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25);
}

.map-marker.village .marker-dot {
    width: 0.7rem;
    height: 0.7rem;
    background: #22c55e;
    box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.24);
}

.map-marker.active .marker-dot {
    background: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.32);
}

.marker-label {
    padding: 0.15rem 0.4rem;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.85);
    color: #fff;
    font-size: 0.7rem;
    line-height: 1.1;
    white-space: nowrap;
}
</style>
