<script setup>
import Icon from '@/Components/Icon.vue';

/**
 * Indicador del portal, con barra de progreso opcional.
 */
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    icon: { type: String, default: null },
    hint: { type: String, default: null },
    progress: { type: Number, default: null },
});
</script>

<template>
    <div class="rounded-2xl border border-gray-200/80 bg-white p-5">
        <div class="flex items-center gap-3">
            <span v-if="icon" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                <Icon :name="icon" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
                <p class="text-xs font-medium text-gray-500">{{ label }}</p>
                <p class="truncate text-xl font-semibold tabular-nums text-gray-900">{{ value }}</p>
            </div>
        </div>
        <div v-if="progress !== null" class="mt-4 h-1.5 overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full bg-indigo-600" :style="{ width: `${Math.min(100, Math.max(0, progress))}%` }" />
        </div>
        <p v-if="hint" class="mt-2 text-xs text-gray-500">{{ hint }}</p>
    </div>
</template>
