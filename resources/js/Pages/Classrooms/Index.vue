<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    classrooms: Object,
    filters: Object,
});

const page = usePage();
const search = ref(props.filters.search ?? '');

const columns = [
    { key: 'code', label: 'Código' },
    { key: 'name', label: 'Nombre' },
    { key: 'capacity', label: 'Capacidad' },
    { key: 'status', label: 'Estado' },
];

function reload(params) {
    router.get(
        route('classrooms.index'),
        {
            search: search.value || undefined,
            sort: props.filters.sort,
            direction: props.filters.direction,
            ...params,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function applySearch() {
    reload({});
}

/**
 * Clic en el encabezado: ordena por esa columna; otro clic invierte el orden.
 */
function sortBy(column) {
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc';
    reload({ sort: column, direction });
}

function sortIcon(column) {
    if (props.filters.sort !== column) {
        return '↕';
    }

    return props.filters.direction === 'asc' ? '↑' : '↓';
}

function destroy(classroom) {
    if (confirm(`¿Eliminar el aula ${classroom.name}?`)) {
        router.delete(route('classrooms.destroy', classroom.id));
    }
}
</script>

<template>
    <Head title="Aulas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Aulas
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
                    <TextInput
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nombre o código..."
                        class="w-full max-w-sm"
                        @keyup.enter="applySearch"
                    />

                    <Link :href="route('classrooms.create')">
                        <PrimaryButton>Nueva aula</PrimaryButton>
                    </Link>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200/80 bg-white">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th
                                    v-for="column in columns"
                                    :key="column.key"
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                    :aria-sort="filters.sort === column.key ? (filters.direction === 'asc' ? 'ascending' : 'descending') : 'none'"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 uppercase tracking-wider hover:text-slate-800"
                                        :class="{ 'text-slate-800': filters.sort === column.key }"
                                        @click="sortBy(column.key)"
                                    >
                                        {{ column.label }}
                                        <span aria-hidden="true" :class="filters.sort === column.key ? 'text-indigo-600' : 'text-slate-300'">{{ sortIcon(column.key) }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="classroom in classrooms.data" :key="classroom.id" class="transition hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ classroom.code }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ classroom.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ classroom.capacity }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="classroom.status === 'disponible' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                    >
                                        {{ classroom.status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link
                                        :href="route('classrooms.edit', classroom.id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        type="button"
                                        class="ml-4 text-red-600 hover:text-red-900"
                                        @click="destroy(classroom)"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="classrooms.data.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay aulas registradas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="classrooms.links.length > 3" class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in classrooms.links"
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
