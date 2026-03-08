<script setup>
import * as THREE from 'three';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const emit = defineEmits(['rolled', 'rolling-change']);

const props = defineProps({
    initialSides: {
        type: Number,
        default: 20,
        validator: (value) => [6, 20].includes(Number(value)),
    },
    showControls: {
        type: Boolean,
        default: true,
    },
    autoRollToken: {
        type: [Number, String],
        default: null,
    },
    autoRollSides: {
        type: Number,
        default: null,
    },
});

const viewportRef = ref(null);
const currentSides = ref([6, 20].includes(Number(props.initialSides)) ? Number(props.initialSides) : 20);
const isRolling = ref(false);
const lastResult = ref(null);
const isReady = ref(false);

let scene = null;
let camera = null;
let renderer = null;
let mesh = null;
let edgeLines = null;
let frameId = null;
let resizeObserver = null;

let rollStart = 0;
let settleStarted = false;
let spinVelocity = new THREE.Vector3(0, 0, 0);
let targetQuaternion = new THREE.Quaternion();
let pendingResult = null;

const rollDurationMs = 1100;

const createMaterial = (sides) => {
    return new THREE.MeshStandardMaterial({
        color: sides === 20 ? 0x3f8cff : 0xf2c14e,
        roughness: 0.32,
        metalness: 0.18,
    });
};

const createGeometry = (sides) => {
    if (sides === 6) {
        return new THREE.BoxGeometry(1.6, 1.6, 1.6);
    }
    return new THREE.IcosahedronGeometry(1.25, 0);
};

const createEdges = (geometry, sides) => {
    const edges = new THREE.EdgesGeometry(geometry);
    const lineMaterial = new THREE.LineBasicMaterial({
        color: sides === 20 ? 0xb8d4ff : 0x5b3f1d,
        transparent: true,
        opacity: 0.7,
    });
    return new THREE.LineSegments(edges, lineMaterial);
};

const disposeMesh = () => {
    if (!mesh || !scene) return;
    scene.remove(mesh);
    if (edgeLines) {
        scene.remove(edgeLines);
        edgeLines.geometry.dispose();
        edgeLines.material.dispose();
        edgeLines = null;
    }
    mesh.geometry.dispose();
    mesh.material.dispose();
    mesh = null;
};

const setDie = (sides) => {
    currentSides.value = sides;
    disposeMesh();

    const geometry = createGeometry(sides);
    const material = createMaterial(sides);
    mesh = new THREE.Mesh(geometry, material);
    mesh.castShadow = true;
    mesh.receiveShadow = true;
    mesh.rotation.set(0.25, 0.3, 0);
    scene.add(mesh);

    edgeLines = createEdges(geometry, sides);
    scene.add(edgeLines);
    edgeLines.rotation.copy(mesh.rotation);
};

const d6TopQuaternion = (value) => {
    const rotations = {
        1: new THREE.Euler(0, 0, 0),
        2: new THREE.Euler(0, 0, Math.PI / 2),
        3: new THREE.Euler(Math.PI / 2, 0, 0),
        4: new THREE.Euler(-Math.PI / 2, 0, 0),
        5: new THREE.Euler(0, 0, -Math.PI / 2),
        6: new THREE.Euler(Math.PI, 0, 0),
    };
    return new THREE.Quaternion().setFromEuler(rotations[value] ?? rotations[1]);
};

const randomQuaternion = () => {
    const euler = new THREE.Euler(
        Math.random() * Math.PI * 2,
        Math.random() * Math.PI * 2,
        Math.random() * Math.PI * 2,
    );
    return new THREE.Quaternion().setFromEuler(euler);
};

const finishRoll = () => {
    isRolling.value = false;
    settleStarted = false;
    lastResult.value = pendingResult;
    emit('rolling-change', false);
    emit('rolled', { sides: currentSides.value, result: pendingResult });
};

const roll = (sides) => {
    if (!mesh || !isReady.value || isRolling.value) return;
    if (currentSides.value !== sides) {
        setDie(sides);
    }

    pendingResult = Math.floor(Math.random() * sides) + 1;
    targetQuaternion = sides === 6 ? d6TopQuaternion(pendingResult) : randomQuaternion();

    rollStart = performance.now();
    settleStarted = false;
    isRolling.value = true;
    emit('rolling-change', true);
    spinVelocity = new THREE.Vector3(
        0.35 + Math.random() * 0.35,
        0.3 + Math.random() * 0.4,
        0.25 + Math.random() * 0.3,
    );
};

const animate = (timestamp) => {
    frameId = requestAnimationFrame(animate);
    if (!renderer || !scene || !camera || !mesh) return;

    if (isRolling.value) {
        const elapsed = timestamp - rollStart;
        if (elapsed < rollDurationMs) {
            mesh.rotation.x += spinVelocity.x;
            mesh.rotation.y += spinVelocity.y;
            mesh.rotation.z += spinVelocity.z;
        } else {
            settleStarted = true;
            spinVelocity.multiplyScalar(0.92);
            mesh.rotation.x += spinVelocity.x;
            mesh.rotation.y += spinVelocity.y;
            mesh.rotation.z += spinVelocity.z;
            mesh.quaternion.slerp(targetQuaternion, 0.13);

            const angleDiff = mesh.quaternion.angleTo(targetQuaternion);
            if (angleDiff < 0.02) {
                mesh.quaternion.copy(targetQuaternion);
                finishRoll();
            }
        }
    } else if (!settleStarted) {
        mesh.rotation.y += 0.004;
    }

    if (edgeLines) {
        edgeLines.position.copy(mesh.position);
        edgeLines.quaternion.copy(mesh.quaternion);
    }

    renderer.render(scene, camera);
};

const resizeRenderer = () => {
    if (!viewportRef.value || !renderer || !camera) return;
    const width = viewportRef.value.clientWidth;
    const height = Math.max(220, viewportRef.value.clientHeight);
    renderer.setSize(width, height, false);
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
};

onMounted(() => {
    if (!viewportRef.value) return;

    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
    camera.position.set(0, 0.2, 5);

    renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true,
    });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    viewportRef.value.appendChild(renderer.domElement);

    const ambient = new THREE.AmbientLight(0xffffff, 0.7);
    scene.add(ambient);

    const keyLight = new THREE.DirectionalLight(0xffffff, 1.05);
    keyLight.position.set(3, 4, 5);
    scene.add(keyLight);

    const rimLight = new THREE.DirectionalLight(0xaad4ff, 0.45);
    rimLight.position.set(-3, -2, 2);
    scene.add(rimLight);

    setDie(currentSides.value);
    resizeRenderer();
    isReady.value = true;

    resizeObserver = new ResizeObserver(() => resizeRenderer());
    resizeObserver.observe(viewportRef.value);

    frameId = requestAnimationFrame(animate);
});

onBeforeUnmount(() => {
    if (frameId) cancelAnimationFrame(frameId);
    if (resizeObserver && viewportRef.value) {
        resizeObserver.unobserve(viewportRef.value);
    }
    disposeMesh();
    if (renderer) {
        renderer.dispose();
        if (renderer.domElement?.parentNode) {
            renderer.domElement.parentNode.removeChild(renderer.domElement);
        }
    }
    scene = null;
    camera = null;
    renderer = null;
});

watch(
    () => props.autoRollToken,
    (next, prev) => {
        if (next === null || next === undefined || next === prev) return;
        const requestedSides = [6, 20].includes(Number(props.autoRollSides))
            ? Number(props.autoRollSides)
            : currentSides.value;
        roll(requestedSides);
    },
);
</script>

<template>
    <div class="dice3d-card">
        <div ref="viewportRef" class="dice3d-viewport"></div>

        <div v-if="showControls" class="dice3d-controls">
            <button type="button" class="btn btn-sm btn-outline-secondary" :disabled="isRolling || !isReady" @click="roll(6)">
                W6 rollen
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" :disabled="isRolling || !isReady" @click="roll(20)">
                W20 rollen
            </button>
            <span class="dice3d-status small">
                <template v-if="isRolling">W{{ currentSides }} rollt...</template>
                <template v-else-if="lastResult != null">Ergebnis: W{{ currentSides }} = {{ lastResult }}</template>
                <template v-else>Bereit</template>
            </span>
        </div>
    </div>
</template>

<style scoped>
.dice3d-card {
    border: 1px solid rgba(120, 120, 120, 0.25);
    border-radius: 14px;
    background: radial-gradient(circle at 30% 20%, rgba(255, 255, 255, 0.18), rgba(255, 255, 255, 0.04));
    overflow: hidden;
}

.dice3d-viewport {
    width: 100%;
    min-height: 280px;
    background:
        radial-gradient(circle at 50% 20%, rgba(255, 255, 255, 0.22), transparent 45%),
        linear-gradient(180deg, rgba(9, 18, 29, 0.14), rgba(14, 24, 34, 0.28));
}

.dice3d-controls {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.72);
}

.dice3d-status {
    color: #4b5965;
    font-weight: 600;
}
</style>
