<script setup>
import { Link, usePage } from '@inertiajs/vue3';

/**
 * Aviso fijo mientras el administrador está dentro de la cuenta de un
 * estudiante, con el botón para volver a su propia cuenta.
 */
const page = usePage();
</script>

<template>
    <div
        v-if="page.props.impersonating"
        class="sticky top-0 z-50 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 bg-amber-400 px-4 py-2 text-sm font-medium text-amber-950"
        role="status"
    >
        <span>Estás viendo la plataforma como <strong>{{ page.props.impersonating.student }}</strong>.</span>
        <Link
            :href="route('impersonation.leave')"
            method="post"
            as="button"
            class="rounded-lg bg-amber-950 px-3 py-1 text-xs font-semibold text-amber-50 hover:bg-amber-900"
        >
            Volver a mi cuenta
        </Link>
    </div>
</template>
