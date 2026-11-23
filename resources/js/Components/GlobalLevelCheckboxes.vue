<script setup>
/**
 * Casillas de los niveles globales (Elementary 1, Elementary 2…): marcar uno
 * incluye todas sus intensidades (Standard, Semi-Intensivo, Intensivo).
 */
const model = defineModel({ type: Array, default: () => [] });

defineProps({
    options: { type: Array, default: () => [] },
});
</script>

<template>
    <div>
        <p v-if="options.length === 0" class="text-sm text-gray-500">Todavía no hay niveles creados.</p>
        <div v-else class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label
                v-for="option in options"
                :key="option.key"
                class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm transition hover:bg-slate-50"
                :class="{ 'border-indigo-300 bg-indigo-50/60': model.includes(option.key) }"
            >
                <input
                    v-model="model"
                    type="checkbox"
                    :value="option.key"
                    class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span>
                    <span class="block font-medium text-gray-900">{{ option.label }}</span>
                    <span class="block text-xs text-gray-500">{{ option.levels.join(', ') }}</span>
                </span>
            </label>
        </div>
    </div>
</template>
