<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    reports: Object,
});

const moduleLabels = {
    estudiantes: 'Estudiantes',
    asistencia: 'Asistencia',
    evaluaciones: 'Evaluaciones',
    recuperaciones: 'Recuperaciones',
    pagos: 'Pagos',
    promociones: 'Promociones',
    referidos: 'Referidos',
    aulas: 'Aulas',
    programacion_clases: 'Programación de clases',
    auditoria: 'Auditoría',
};
</script>

<template>
    <Head title="Reportes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Reportes
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
                <div v-for="(items, moduleKey) in reports" :key="moduleKey">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
                        {{ moduleLabels[moduleKey] ?? moduleKey }}
                    </h3>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="item in items"
                            :key="item.key"
                            :href="route('reports.show', item.key)"
                            class="rounded-lg bg-white p-4 text-sm font-medium text-gray-900 shadow-sm hover:bg-gray-50"
                        >
                            {{ item.label }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
