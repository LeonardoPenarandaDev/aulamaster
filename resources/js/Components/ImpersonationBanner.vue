<script setup>
import { closeImpersonation } from '@/impersonation';
import { usePage } from '@inertiajs/vue3';

/**
 * Aviso fijo en la pestaña donde el administrador ve la plataforma como un
 * estudiante. Las demás pestañas siguen siendo del administrador.
 */
const page = usePage();
</script>

<template>
    <div
        v-if="page.props.impersonating"
        class="sticky top-0 z-50 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 bg-amber-400 px-4 py-2 text-sm font-medium text-amber-950"
        role="status"
    >
        <span>
            Estás viendo la plataforma como <strong>{{ page.props.impersonating.student }}</strong>
            · solo en esta pestaña
        </span>
        <button
            type="button"
            class="rounded-lg bg-amber-950 px-3 py-1 text-xs font-semibold text-amber-50 hover:bg-amber-900"
            @click="closeImpersonation"
        >
            Cerrar vista del estudiante
        </button>
    </div>
</template>
