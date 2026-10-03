<script setup>
import Icon from '@/Components/Icon.vue';

defineProps({
    icon: { type: String, default: 'calendar' },
    title: { type: String, required: true },
    text: { type: String, default: null },
});
</script>

<template>
    <div class="flex flex-col items-center rounded-2xl border border-dashed border-gray-300 bg-white/60 px-6 py-10 text-center">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-500">
            <Icon :name="icon" class="h-6 w-6" />
        </span>
        <p class="mt-3 text-sm font-semibold text-gray-900">{{ title }}</p>
        <p v-if="text" class="mt-1 max-w-sm text-sm text-gray-500">{{ text }}</p>
        <div v-if="$slots.default" class="mt-4"><slot /></div>
    </div>
</template>
