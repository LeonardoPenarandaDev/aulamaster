<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    size: {
        type: String,
        default: 'md',
    },
});

const page = usePage();
const institution = computed(() => page.props.institution ?? { name: 'AulaMaster', logo_url: null });

const sizes = {
    md: { box: 'h-8 w-8 rounded-lg', icon: 'h-5 w-5' },
    lg: { box: 'h-16 w-16 rounded-2xl', icon: 'h-9 w-9' },
};

const sizeClasses = computed(() => sizes[props.size] ?? sizes.md);
</script>

<template>
    <img
        v-if="institution.logo_url"
        :src="institution.logo_url"
        :alt="institution.name"
        class="object-contain"
        :class="sizeClasses.box"
    />
    <span
        v-else
        class="flex items-center justify-center bg-indigo-600"
        :class="[sizeClasses.box, size === 'lg' ? 'shadow-lg shadow-indigo-200' : '']"
    >
        <ApplicationLogo class="fill-current text-white" :class="sizeClasses.icon" />
    </span>
</template>
