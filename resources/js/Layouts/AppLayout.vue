<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Pantallas que comparten el personal y los portales (asistencia, material
 * de clase, calendario, perfil): los alumnos y docentes ven el portal
 * nuevo; admin, coordinador, cajero y secretaria mantienen el estilo actual
 * (parte 9 del plan de mejoras).
 */
const page = usePage();
const STAFF_ROLES = ['admin', 'coordinador', 'cajero', 'secretaria'];

const layout = computed(() => {
    const roles = page.props.auth.roles ?? [];
    const isPortalUser = roles.some((role) => ['estudiante', 'profesor'].includes(role));

    return isPortalUser && !roles.some((role) => STAFF_ROLES.includes(role)) ? PortalLayout : AuthenticatedLayout;
});
</script>

<template>
    <component :is="layout">
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>
        <slot />
    </component>
</template>
