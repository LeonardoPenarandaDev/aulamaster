<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Icon from '@/Components/Icon.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { sidebarTheme } from '@/sidebarThemes';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Apariencia de la aplicación: colores de la institución y estilo del menú
 * lateral, con vista previa en vivo.
 */
const props = defineProps({
    appearance: Object,
    sidebarStyles: Object,
    defaults: Object,
});

const page = usePage();

const form = useForm({ ...props.appearance });

const presets = [
    { name: 'Active English', primary: props.defaults.primary_color, accent: props.defaults.accent_color },
    { name: 'Azul océano', primary: '#0369A1', accent: '#14B8A6' },
    { name: 'Esmeralda', primary: '#047857', accent: '#F59E0B' },
    { name: 'Violeta', primary: '#6D28D9', accent: '#EC4899' },
    { name: 'Rojo', primary: '#B91C1C', accent: '#F59E0B' },
    { name: 'Grafito', primary: '#334155', accent: '#F97316' },
];

const colorFields = [
    { key: 'primary_color', label: 'Color principal', help: 'Menú, botones, enlaces y campos seleccionados.' },
    { key: 'accent_color', label: 'Color de acento', help: 'Degradados, avatares e íconos activos del menú.' },
];

const styleDescriptions = {
    color: 'El menú toma el color principal, con un degradado.',
    oscuro: 'Menú negro, elegante y de alto contraste.',
    claro: 'Menú blanco con el color principal en la opción activa.',
};

function applyPreset(preset) {
    form.primary_color = preset.primary;
    form.accent_color = preset.accent;
}

const isValidColor = (value) => /^#[0-9A-Fa-f]{6}$/.test(value ?? '');

/**
 * Misma mezcla que InstitutionSetting::palette(): el color elegido es el
 * tono 600; los demás se aclaran con blanco o se oscurecen con negro.
 */
const SHADES = { 50: 0.95, 100: 0.88, 200: 0.76, 300: 0.58, 400: 0.36, 500: 0.16, 600: 0, 700: -0.16, 800: -0.3, 900: -0.44, 950: -0.6 };

function palette(hex) {
    const value = parseInt(hex.slice(1), 16);
    const channels = [(value >> 16) & 255, (value >> 8) & 255, value & 255];

    return Object.fromEntries(Object.entries(SHADES).map(([shade, amount]) => [
        shade,
        channels.map((channel) => Math.round(amount >= 0 ? channel + (255 - channel) * amount : channel * (1 + amount))).join(' '),
    ]));
}

// Las variables CSS se aplican solo a la vista previa hasta guardar.
const previewVariables = computed(() => {
    const variables = {};
    const colors = { primary: form.primary_color, accent: form.accent_color };

    for (const [name, hex] of Object.entries(colors)) {
        if (!isValidColor(hex)) {
            continue;
        }
        for (const [shade, rgb] of Object.entries(palette(hex))) {
            variables[`--c-${name}-${shade}`] = rgb;
        }
    }

    return variables;
});

const previewTheme = computed(() => sidebarTheme(form.sidebar_style));

const previewMenu = [
    { label: 'Inicio', icon: 'home', active: true },
    { label: 'Calendario', icon: 'calendar' },
    { label: 'Estudiantes', icon: 'users' },
    { label: 'Pagos', icon: 'wallet' },
];

function submit() {
    form.put(route('appearance.update'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Apariencia" />

    <AuthenticatedLayout>
        <template #header>
            <h2>Apariencia</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-screen-xl grid-cols-1 gap-6 px-4 sm:px-6 lg:px-8 xl:grid-cols-5">
                <form class="space-y-6 xl:col-span-2" @submit.prevent="submit">
                    <div v-if="page.props.flash?.success" class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-700">
                        {{ page.props.flash.success }}
                    </div>

                    <section class="rounded-2xl border border-slate-200/80 bg-white p-6">
                        <h3 class="text-base font-semibold text-slate-900">Combinaciones</h3>
                        <p class="mt-1 text-sm text-slate-500">Elige una para empezar y ajústala si quieres.</p>
                        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
                            <button
                                v-for="preset in presets"
                                :key="preset.name"
                                type="button"
                                class="flex items-center gap-2 rounded-xl border px-3 py-2.5 text-left text-sm transition"
                                :class="form.primary_color.toUpperCase() === preset.primary.toUpperCase() && form.accent_color.toUpperCase() === preset.accent.toUpperCase()
                                    ? 'border-indigo-500 bg-indigo-50 font-semibold text-indigo-700'
                                    : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                @click="applyPreset(preset)"
                            >
                                <span class="flex shrink-0 -space-x-1.5">
                                    <span class="h-5 w-5 rounded-full ring-2 ring-white" :style="{ backgroundColor: preset.primary }" />
                                    <span class="h-5 w-5 rounded-full ring-2 ring-white" :style="{ backgroundColor: preset.accent }" />
                                </span>
                                <span class="truncate">{{ preset.name }}</span>
                            </button>
                        </div>
                    </section>

                    <section class="space-y-5 rounded-2xl border border-slate-200/80 bg-white p-6">
                        <h3 class="text-base font-semibold text-slate-900">Colores</h3>
                        <div v-for="field in colorFields" :key="field.key">
                            <InputLabel :for="field.key" :value="field.label" />
                            <div class="mt-1 flex items-center gap-3">
                                <input
                                    :id="field.key"
                                    v-model="form[field.key]"
                                    type="color"
                                    class="h-11 w-14 cursor-pointer rounded-xl border border-slate-200 bg-white p-1"
                                />
                                <TextInput v-model="form[field.key]" class="block w-32 font-mono uppercase" maxlength="7" />
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ field.help }}</p>
                            <InputError class="mt-2" :message="form.errors[field.key]" />
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200/80 bg-white p-6">
                        <h3 class="text-base font-semibold text-slate-900">Menú lateral</h3>
                        <div class="mt-4 space-y-2">
                            <label
                                v-for="(label, value) in sidebarStyles"
                                :key="value"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition"
                                :class="form.sidebar_style === value ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 hover:bg-slate-50'"
                            >
                                <input v-model="form.sidebar_style" type="radio" :value="value" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">{{ label }}</span>
                                    <span class="block text-sm text-slate-500">{{ styleDescriptions[value] }}</span>
                                </span>
                            </label>
                        </div>
                        <InputError class="mt-2" :message="form.errors.sidebar_style" />
                    </section>

                    <div class="flex items-center gap-3">
                        <PrimaryButton :disabled="form.processing || !isValidColor(form.primary_color) || !isValidColor(form.accent_color)">
                            Guardar apariencia
                        </PrimaryButton>
                        <span v-if="form.isDirty" class="text-sm text-slate-500">Hay cambios sin guardar.</span>
                    </div>
                </form>

                <!-- Vista previa -->
                <div class="xl:col-span-3">
                    <div class="sticky top-24">
                        <p class="mb-2 text-sm font-medium text-slate-500">Vista previa</p>
                        <div class="overflow-hidden rounded-2xl border border-slate-200 shadow-xl shadow-slate-900/5" :style="previewVariables">
                            <div class="flex h-[460px]">
                                <aside class="flex w-52 shrink-0 flex-col border-r" :class="previewTheme.aside">
                                    <div class="flex h-14 items-center gap-2 border-b px-4" :class="previewTheme.divider">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-accent-500 text-white">
                                            <Icon name="book" class="h-4 w-4" />
                                        </span>
                                        <span class="truncate text-sm font-semibold" :class="previewTheme.name">{{ page.props.institution.name }}</span>
                                    </div>
                                    <div class="flex-1 space-y-0.5 p-2">
                                        <p class="px-3 pb-1 pt-2 text-[10px] font-semibold uppercase tracking-wider" :class="previewTheme.groupLabel">Menú</p>
                                        <div
                                            v-for="item in previewMenu"
                                            :key="item.label"
                                            class="group flex items-center gap-2.5 rounded-xl px-3 py-2 text-[13px] font-medium"
                                            :class="item.active ? previewTheme.itemActive : previewTheme.item"
                                        >
                                            <Icon :name="item.icon" class="h-4 w-4" :class="item.active ? previewTheme.iconActive : previewTheme.icon" />
                                            {{ item.label }}
                                        </div>
                                    </div>
                                </aside>

                                <div class="flex min-w-0 flex-1 flex-col bg-slate-50">
                                    <div class="flex h-14 items-center justify-between border-b border-slate-200/70 bg-white/80 px-5">
                                        <span class="text-sm font-semibold text-slate-900">Inicio</span>
                                        <span class="h-7 w-7 rounded-full bg-gradient-to-br from-indigo-600 to-accent-500" />
                                    </div>
                                    <div class="space-y-4 p-5">
                                        <div class="rounded-2xl bg-gradient-to-br from-indigo-700 via-indigo-600 to-accent-500 p-5 text-white">
                                            <p class="text-xs text-white/80">Hoy</p>
                                            <p class="text-lg font-bold">Hola 👋</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="rounded-2xl border border-slate-200/80 bg-white p-4">
                                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><Icon name="users" class="h-4 w-4" /></span>
                                                <p class="mt-2 text-xs text-slate-500">Estudiantes</p>
                                                <p class="text-lg font-semibold text-slate-900">128</p>
                                            </div>
                                            <div class="rounded-2xl border border-slate-200/80 bg-white p-4">
                                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-accent-50 text-accent-600"><Icon name="wallet" class="h-4 w-4" /></span>
                                                <p class="mt-2 text-xs text-slate-500">Pagos del mes</p>
                                                <p class="text-lg font-semibold text-slate-900">$4.250.000</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white">Botón principal</span>
                                            <span class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700">Secundario</span>
                                            <span class="text-xs font-semibold text-indigo-600">Enlace</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
