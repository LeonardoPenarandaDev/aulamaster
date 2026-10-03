<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Botón del portal. Con `href` es un enlace de Inertia; con `external`, un
 * enlace normal (por ejemplo, la clase virtual).
 */
const props = defineProps({
    href: { type: String, default: null },
    external: { type: Boolean, default: false },
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
});

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
    {
        primary: 'bg-indigo-600 text-white hover:bg-indigo-700',
        secondary: 'border border-gray-200 bg-white text-gray-800 hover:bg-gray-50',
        ghost: 'text-indigo-600 hover:bg-indigo-50',
    }[props.variant],
    { sm: 'px-3 py-1.5 text-sm', md: 'px-4 py-2.5 text-sm', lg: 'px-5 py-3.5 text-base' }[props.size],
]);
</script>

<template>
    <a v-if="href && external" :href="href" target="_blank" rel="noopener noreferrer" :class="classes"><slot /></a>
    <Link v-else-if="href" :href="href" :class="classes"><slot /></Link>
    <button v-else :type="type" :disabled="disabled" :class="classes"><slot /></button>
</template>
