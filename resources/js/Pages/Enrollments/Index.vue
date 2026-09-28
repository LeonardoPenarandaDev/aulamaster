<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    enrollments: Object,
    filters: Object,
    students: Array,
    levels: Array,
});

const page = usePage();
const filters = ref({
    student_id: props.filters.student_id ?? '',
    level_id: props.filters.level_id ?? '',
    status: props.filters.status ?? '',
});

function applyFilters() {
    router.get(route('enrollments.index'), filters.value, {
        preserveState: true,
        replace: true,
    });
}

const statusLabels = {
    pendiente: 'Pendiente',
    activa: 'Activa',
    en_recuperacion: 'En recuperación',
    extendida: 'Extendida',
    finalizada: 'Finalizada',
    cancelada: 'Cancelada',
    aprobada: 'Aprobada',
    reprobada: 'Reprobada',
};

const statusClasses = {
    pendiente: 'bg-gray-100 text-gray-800',
    activa: 'bg-blue-100 text-blue-800',
    en_recuperacion: 'bg-yellow-100 text-yellow-800',
    extendida: 'bg-yellow-100 text-yellow-800',
    finalizada: 'bg-gray-100 text-gray-800',
    cancelada: 'bg-red-100 text-red-800',
    aprobada: 'bg-green-100 text-green-800',
    reprobada: 'bg-red-100 text-red-800',
};

function money(value) {
    return Number(value).toLocaleString('es-CO');
}
</script>

<template>
    <Head title="Matrículas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Matrículas
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-screen-2xl space-y-4 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-md bg-green-50 p-4 text-sm text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div class="flex flex-wrap gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Estudiante</label>
                            <select v-model="filters.student_id" @change="applyFilters" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="s in students" :key="s.id" :value="s.id">{{ s.code }} - {{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Nivel</label>
                            <select v-model="filters.level_id" @change="applyFilters" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Estado</label>
                            <select v-model="filters.status" @change="applyFilters" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </div>
                    </div>

                    <Link :href="route('enrollments.create')">
                        <PrimaryButton>Nueva matrícula</PrimaryButton>
                    </Link>
                </div>

                <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estudiante</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Curso / Nivel</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Horas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Progreso</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Precio final</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="enrollment in enrollments.data" :key="enrollment.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ enrollment.student?.code }} - {{ enrollment.student?.name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ enrollment.level?.course?.name }} {{ enrollment.level?.name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ enrollment.accumulated_hours }} / {{ enrollment.required_hours }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ enrollment.progress_percentage }}%</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ money(enrollment.final_price) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[enrollment.status]">
                                        {{ statusLabels[enrollment.status] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link :href="route('students.account-statement', enrollment.student_id)" class="text-gray-600 hover:text-gray-900">
                                        Cuenta
                                    </Link>
                                    <Link :href="route('enrollments.evaluation-results.create', enrollment.id)" class="ml-4 text-green-700 hover:text-green-900">
                                        Evaluar
                                    </Link>
                                    <Link :href="route('enrollments.edit', enrollment.id)" class="ml-4 text-indigo-600 hover:text-indigo-900">
                                        Editar
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="enrollments.data.length === 0">
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay matrículas registradas con estos filtros.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="enrollments.links.length > 3" class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in enrollments.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        class="rounded-md border px-3 py-1 text-sm"
                        :class="[
                            link.active ? 'border-indigo-500 bg-indigo-50 text-indigo-600' : 'border-gray-200 text-gray-600',
                            !link.url ? 'pointer-events-none opacity-50' : '',
                        ]"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
