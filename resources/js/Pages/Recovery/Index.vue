<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    pending: Array,
});

const page = usePage();

function money(value) {
    return Number(value).toLocaleString('es-CO');
}
</script>

<template>
    <Head title="Recuperaciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Recuperaciones pendientes
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-md bg-green-50 p-4 text-sm text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="flex items-center justify-between gap-4">
                    <p class="text-sm text-gray-600">
                        Evaluaciones reprobadas cuya matrícula sigue en estado "en recuperación".
                    </p>
                    <Link :href="route('recovery-settings.edit')" class="text-sm text-indigo-600 hover:text-indigo-900">
                        Configurar reglas de recuperación →
                    </Link>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estudiante</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Curso / Nivel</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Evaluación</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Último intento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Próximo intento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fecha límite</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="item in pending" :key="`${item.enrollment_id}-${item.evaluation.id}`">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ item.student.code }} - {{ item.student.name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ item.level.course }} {{ item.level.name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ item.evaluation.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    Intento #{{ item.last_attempt.attempt_number }}: {{ item.last_attempt.grade }} ({{ item.last_attempt.evaluated_at }})
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    <span v-if="item.terms.blocked" class="text-red-600">{{ item.terms.block_reason }}</span>
                                    <span v-else-if="item.terms.cost > 0">Pagada — ${{ money(item.terms.cost) }}</span>
                                    <span v-else>Gratuita</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span :class="item.overdue ? 'font-medium text-red-600' : 'text-gray-500'">
                                        {{ item.deadline }}<span v-if="item.overdue"> (vencida)</span>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link :href="route('enrollments.evaluation-results.create', item.enrollment_id)" class="text-indigo-600 hover:text-indigo-900">
                                        Registrar recuperación
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="pending.length === 0">
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay recuperaciones pendientes.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
