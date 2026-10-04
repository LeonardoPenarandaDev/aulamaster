<script setup>
import { computed } from 'vue';

const model = defineModel({ type: String, default: '#DBEAFE' });

const palette = [
    { value: '#DBEAFE', label: 'Azul' },
    { value: '#DCFCE7', label: 'Verde' },
    { value: '#FEF9C3', label: 'Amarillo' },
    { value: '#FFEDD5', label: 'Naranja' },
    { value: '#FCE7F3', label: 'Rosa' },
    { value: '#EDE9FE', label: 'Lila' },
    { value: '#CCFBF1', label: 'Turquesa' },
    { value: '#F3F4F6', label: 'Gris' },
];

const normalized = computed(() => (model.value ?? '').toUpperCase());
const isCustom = computed(() => !palette.some((color) => color.value === normalized.value));
</script>

<template>
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <button
                v-for="color in palette"
                :key="color.value"
                type="button"
                class="h-9 w-9 rounded-full border border-gray-300 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                :class="{ 'ring-2 ring-indigo-600 ring-offset-2': normalized === color.value }"
                :style="{ backgroundColor: color.value }"
                :title="color.label"
                :aria-label="color.label"
                :aria-pressed="normalized === color.value"
                @click="model = color.value"
            />

            <label
                class="flex cursor-pointer items-center gap-2 rounded-full border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
                :class="{ 'ring-2 ring-indigo-600 ring-offset-2': isCustom }"
            >
                <input v-model="model" type="color" class="h-5 w-5 cursor-pointer rounded border-0 p-0" />
                Personalizado
            </label>
        </div>

        <div
            class="mt-3 flex h-20 items-center justify-center rounded-lg border border-gray-200 text-xs text-gray-500"
            :style="{ backgroundImage: `linear-gradient(to bottom, ${model}, #ffffff)` }"
        >
            <span class="on-level-color rounded-md bg-white px-3 py-1.5 shadow-sm">Así se verá el fondo del portal del estudiante</span>
        </div>
    </div>
</template>
