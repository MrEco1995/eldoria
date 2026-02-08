<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'list-group-item list-group-item-action active fw-semibold'
        : 'list-group-item list-group-item-action',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
