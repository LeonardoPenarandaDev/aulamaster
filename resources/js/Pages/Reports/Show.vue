<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    report: Object,
    headings: Array,
    rows: Array,
    filters: Object,
    courses: Array,
    levels: Array,
    teachers: Array,
    classrooms: Array,
});

const filters = ref({
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    course_id: props.filters.course_id ?? '',
    level_id: props.filters.level_id ?? '',
    teacher_id: props.filters.teacher_id ?? '',
    classroom_id: props.filters.classroom_id ?? '',
});

function applyFilters() {
    const query = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== ''));
    router.get(route('reports.show', props.report.key), query, { preserveState: true, replace: true });
}

function exportUrl(format) {
    const query = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== ''));
    const params = new URLSearchParams({ ...query, format });

    return `${route('reports.show', props.report.key)}?${params.toString()}`;
}
</script>

<template>
    <Head :title="report.label" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ report.label }}
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-4 px-4 sm:px-6 lg:px-8">
                <Link :href="route('reports.index')" class="text-sm text-indigo-600 hover:text-indigo-900">
                    ← Todos los reportes
                </Link>

                <div class="flex flex-wrap items-end justify-between gap-4">
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
                            <label class="block text-xs font-medium text-gray-600">Curso</label>
                            <select v-model="filters.course_id" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }}</option>
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
                            <label class="block text-xs font-medium text-gray-600">Profesor</label>
                            <select v-model="filters.teacher_id" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Aula</label>
                            <select v-model="filters.classroom_id" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todas</option>
                                <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <a :href="exportUrl('pdf')" class="rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-500">PDF</a>
                        <a :href="exportUrl('xlsx')" class="rounded-md bg-green-700 px-3 py-2 text-xs font-semibold text-white hover:bg-green-600">Excel</a>
                        <a :href="exportUrl('csv')" class="rounded-md bg-gray-600 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-500">CSV</a>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200/80 bg-white">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th v-for="heading in headings" :key="heading" class="whitespace-nowrap px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    {{ heading }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="(row, index) in rows" :key="index" class="transition hover:bg-slate-50/70">
                                <td v-for="(cell, cellIndex) in row" :key="cellIndex" class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    {{ cell }}
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td :colspan="headings.length" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay datos para este reporte con los filtros aplicados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
