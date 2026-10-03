<script setup>
/**
 * Tarjeta del portal de alumnos y docentes (parte 9 del plan de mejoras):
 * blanca, rounded-2xl, borde fino y sin sombras marcadas.
 */
defineProps({
    title: { type: String, default: null },
    subtitle: { type: String, default: null },
    padded: { type: Boolean, default: true },
});
</script>

<template>
    <section class="rounded-2xl border border-gray-200/80 bg-white" :class="{ 'p-5 sm:p-6': padded }">
        <header v-if="title || $slots.actions" class="mb-4 flex flex-wrap items-start justify-between gap-2" :class="{ 'px-5 pt-5 sm:px-6 sm:pt-6': !padded }">
            <div>
                <h2 v-if="title" class="text-base font-semibold text-gray-900">{{ title }}</h2>
                <p v-if="subtitle" class="mt-0.5 text-sm text-gray-500">{{ subtitle }}</p>
            </div>
            <slot name="actions" />
        </header>
        <slot />
    </section>
</template>
