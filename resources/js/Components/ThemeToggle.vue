<script setup>
import Icon from '@/Components/Icon.vue';
import { useTheme } from '@/theme';
import { computed } from 'vue';

/**
 * Cambia entre tema claro, oscuro y el del sistema.
 */
const { theme, setTheme } = useTheme();

const options = {
    light: { icon: 'sun', label: 'Tema claro', next: 'dark' },
    dark: { icon: 'moon', label: 'Tema oscuro', next: 'system' },
    system: { icon: 'monitor', label: 'Tema del sistema', next: 'light' },
};

const current = computed(() => options[theme.value] ?? options.system);
</script>

<template>
    <button
        type="button"
        class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
        :title="`${current.label} (clic para cambiar)`"
        :aria-label="current.label"
        @click="setTheme(current.next)"
    >
        <Icon :name="current.icon" class="h-5 w-5" />
    </button>
</template>
