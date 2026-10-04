<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    evaluations: Object,
    filters: Object,
    levels: Array,
});

const page = usePage();
const levelId = ref(props.filters.level_id ?? '');

function applyFilter() {
    router.get(route('evaluations.index'), { level_id: levelId.value || undefined }, {
        preserveState: true,
        replace: true,
    });
}

function destroy(evaluation) {
    if (confirm(`¿Eliminar la evaluación ${evaluation.name}?`)) {
        router.delete(route('evaluations.destroy', evaluation.id));
    }
}
</script>

<template>
    <Head title="Evaluaciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Evaluaciones
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
                    <select v-model="levelId" @change="applyFilter" class="rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todos los niveles</option>
                        <option v-for="level in levels" :key="level.id" :value="level.id">{{ level.name }}</option>
                    </select>

                    <Link :href="route('evaluations.create')">
                        <PrimaryButton>Nueva evaluación</PrimaryButton>
                    </Link>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200/80 bg-white">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Competencia</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nivel</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nota mínima</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="evaluation in evaluations.data" :key="evaluation.id" class="transition hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ evaluation.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ evaluation.competency }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ evaluation.level?.course?.name }} {{ evaluation.level?.name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ evaluation.minimum_grade }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="evaluation.status === 'activo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                    >
                                        {{ evaluation.status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link :href="route('evaluations.edit', evaluation.id)" class="text-indigo-600 hover:text-indigo-900">
                                        Editar
                                    </Link>
                                    <button type="button" class="ml-4 text-red-600 hover:text-red-900" @click="destroy(evaluation)">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="evaluations.data.length === 0">
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay evaluaciones configuradas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="evaluations.links.length > 3" class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in evaluations.links"
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
