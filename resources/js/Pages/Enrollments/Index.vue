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
const isAdmin = page.props.auth.roles?.includes('admin');
// Asistente con contratos (parte 6.3): admin y secretaria.
const canUseWizard = page.props.auth.roles?.some((role) => ['admin', 'secretaria'].includes(role));
const filters = ref({
    student_id: props.filters.student_id ?? '',
    level_id: props.filters.level_id ?? '',
    status: props.filters.status ?? '',
    contracts: props.filters.contracts ?? '',
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

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div class="flex flex-wrap gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Estudiante</label>
                            <select v-model="filters.student_id" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="s in students" :key="s.id" :value="s.id">{{ s.code }} - {{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Nivel</label>
                            <select v-model="filters.level_id" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Estado</label>
                            <select v-model="filters.status" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Contratos</label>
                            <select v-model="filters.contracts" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option value="pendientes">Contratos pendientes</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link v-if="canUseWizard" :href="route('enrollments.create')" class="text-sm text-gray-600 hover:text-gray-900">Formulario rápido</Link>
                        <Link :href="canUseWizard ? route('enrollments.wizard.create') : route('enrollments.create')">
                            <PrimaryButton>Nueva matrícula</PrimaryButton>
                        </Link>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200/80 bg-white">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estudiante</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Curso / Nivel</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Horas</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Progreso</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Precio final</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="enrollment in enrollments.data" :key="enrollment.id" class="transition hover:bg-slate-50/70">
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
                                    <span
                                        v-if="enrollment.open_contracts_count > 0"
                                        class="ml-1 rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800"
                                        :title="`${enrollment.open_contracts_count} contratos sin firmar`"
                                    >
                                        {{ enrollment.open_contracts_count }} por firmar
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link :href="route('students.account-statement', enrollment.student_id)" class="text-gray-600 hover:text-gray-900">
                                        Cuenta
                                    </Link>
                                    <Link v-if="isAdmin" :href="route('enrollments.evaluation-results.create', enrollment.id)" class="ml-4 text-green-700 hover:text-green-900">
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
                        class="rounded-lg border px-3 py-1.5 text-sm"
                        :class="[
                            link.active ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                            !link.url ? 'pointer-events-none opacity-50' : '',
                        ]"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
