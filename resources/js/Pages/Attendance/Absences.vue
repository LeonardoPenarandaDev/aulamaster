<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    students: Array,
    filters: Object,
    levels: Array,
});

const filters = ref({
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    status: props.filters.status ?? 'todos',
    level_id: props.filters.level_id ?? '',
});

function applyFilters() {
    router.get(route('attendance.absences'), filters.value, {
        preserveState: true,
        replace: true,
    });
}

const statusLabels = { ausente: 'Ausente', excusado: 'Excusado' };
const statusClasses = {
    ausente: 'bg-red-100 text-red-800',
    excusado: 'bg-yellow-100 text-yellow-800',
};

const expandedStudentId = ref(null);

function toggleDetails(studentId) {
    expandedStudentId.value = expandedStudentId.value === studentId ? null : studentId;
}

/**
 * wa.me necesita el número con indicativo de país: a los celulares locales
 * de 10 dígitos se les antepone el 57 (Colombia).
 */
function whatsappUrl(phone) {
    let digits = (phone ?? '').replace(/\D/g, '');

    if (digits.length === 10) {
        digits = `57${digits}`;
    }

    return digits ? `https://wa.me/${digits}` : null;
}
</script>

<template>
    <Head title="Inasistencias" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Inasistencias por contactar
                </h2>
                <Link :href="route('attendance.index')" class="text-sm text-indigo-600 hover:text-indigo-900">
                    Ver todas las asistencias
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Desde</label>
                        <input type="date" v-model="filters.from" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Hasta</label>
                        <input type="date" v-model="filters.to" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Tipo</label>
                        <select v-model="filters.status" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="todos">Ausentes y excusados</option>
                            <option value="ausente">Solo ausentes</option>
                            <option value="excusado">Solo excusados</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Nivel</label>
                        <select v-model="filters.level_id" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.course?.name }} {{ l.name }}</option>
                        </select>
                    </div>
                </div>

                <p class="text-sm text-gray-500">
                    {{ students.length }} estudiante{{ students.length === 1 ? '' : 's' }} con inasistencias en el periodo.
                </p>

                <div class="overflow-x-auto rounded-2xl border border-slate-200/80 bg-white">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estudiante</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Curso / Nivel</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Ausencias</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Excusas</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Última falta</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Última vez presente</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Contacto</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template v-for="row in students" :key="row.student.id">
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        {{ row.student.code }} - {{ row.student.name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ row.levels.join(', ') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-center text-sm font-semibold text-red-700">{{ row.absent_count }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-yellow-700">{{ row.excused_count }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ row.last_missed_date }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ row.last_present_date ?? 'Nunca' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <div v-if="row.student.phone" class="flex items-center gap-2">
                                            <a :href="`tel:${row.student.phone}`" class="text-gray-900 hover:text-indigo-600">{{ row.student.phone }}</a>
                                            <a :href="whatsappUrl(row.student.phone)" target="_blank" rel="noopener" class="rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 hover:bg-green-200">WhatsApp</a>
                                        </div>
                                        <a v-if="row.student.email" :href="`mailto:${row.student.email}`" class="block text-indigo-600 hover:text-indigo-900">{{ row.student.email }}</a>
                                        <span v-if="!row.student.phone && !row.student.email" class="text-gray-400">Sin datos de contacto</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <button type="button" class="text-indigo-600 hover:text-indigo-900" @click="toggleDetails(row.student.id)">
                                            {{ expandedStudentId === row.student.id ? 'Ocultar' : 'Ver fechas' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="expandedStudentId === row.student.id" class="bg-gray-50">
                                    <td colspan="8" class="px-6 py-3">
                                        <ul class="flex flex-wrap gap-2">
                                            <li v-for="(missed, index) in row.missed" :key="index" class="rounded-md border border-gray-200 bg-white px-3 py-1 text-xs text-gray-700">
                                                {{ missed.date }} · {{ missed.level }}
                                                <span class="ml-1 rounded-full px-2 py-0.5 font-medium" :class="statusClasses[missed.status]">
                                                    {{ statusLabels[missed.status] }}
                                                </span>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="students.length === 0">
                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay inasistencias en este periodo.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
