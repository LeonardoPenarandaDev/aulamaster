<script setup>
import { computed } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
});

const triggerClasses = computed(() =>
    props.active
        ? 'inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium bg-indigo-50 text-indigo-700 transition duration-150 ease-in-out'
        : 'inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition duration-150 ease-in-out',
);
</script>

<template>
    <Dropdown align="left" width="56">
        <template #trigger>
            <button type="button" :class="triggerClasses">
                {{ label }}
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </template>

        <template #content>
            <slot />
        </template>
    </Dropdown>
</template>
