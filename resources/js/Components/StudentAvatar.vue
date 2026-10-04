<script setup>
import { computed } from 'vue';

/**
 * Foto de perfil del estudiante o, si no tiene, sus iniciales.
 */
const props = defineProps({
    name: { type: String, default: '' },
    photoUrl: { type: String, default: null },
    size: { type: String, default: 'md' },
});

const initials = computed(() => props.name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word[0].toUpperCase())
    .join(''));

const sizes = {
    sm: 'h-8 w-8 text-xs',
    md: 'h-10 w-10 text-sm',
    lg: 'h-16 w-16 text-lg',
    xl: 'h-28 w-28 text-3xl',
};
</script>

<template>
    <img
        v-if="photoUrl"
        :src="photoUrl"
        :alt="name"
        loading="lazy"
        class="shrink-0 rounded-full object-cover ring-2 ring-white"
        :class="sizes[size] ?? sizes.md"
    />
    <span
        v-else
        class="flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-600 to-accent-500 font-semibold text-white ring-2 ring-white"
        :class="sizes[size] ?? sizes.md"
        :aria-label="name"
    >
        {{ initials }}
    </span>
</template>
