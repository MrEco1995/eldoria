<script setup>
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};
</script>

<template>
    <teleport to="body">
        <div
            v-if="show"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            style="background-color: rgba(0, 0, 0, 0.5);"
            @click.self="close"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <slot />
                </div>
            </div>
        </div>
    </teleport>
</template>
