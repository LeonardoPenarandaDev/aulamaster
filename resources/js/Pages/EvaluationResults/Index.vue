<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    results: Object,
    filters: Object,
    students: Array,
    levels: Array,
});

const page = usePage();
const filters = ref({
    student_id: props.filters.student_id ?? '',
    level_id: props.filters.level_id ?? '',
});

function applyFilters() {
    router.get(route('evaluation-results.index'), filters.value, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Resultados de evaluaciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Resultados de evaluaciones
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

                <div class="flex items-center justify-between gap-4">
                    <Link :href="route('evaluations.index')" class="text-sm text-indigo-600 hover:text-indigo-900">
                        Configurar evaluaciones por nivel →
                    </Link>
                </div>

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
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200/80 bg-white">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estudiante</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nivel</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Evaluación</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Intento</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nota</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Resultado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="result in results.data" :key="result.id" class="transition hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ result.evaluated_at }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ result.enrollment?.student?.code }} - {{ result.enrollment?.student?.name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ result.enrollment?.level?.course?.name }} {{ result.enrollment?.level?.name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ result.evaluation?.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ result.attempt_number }}<span v-if="result.is_recovery"> (recuperación)</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ result.grade }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="result.result === 'aprobado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    >
                                        {{ result.result }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="results.data.length === 0">
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay resultados registrados con estos filtros.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="results.links.length > 3" class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in results.links"
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
