<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    courses: Object,
    filters: Object,
});

const page = usePage();
const search = ref(props.filters.search ?? '');

function applySearch() {
    router.get(
        route('courses.index'),
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function destroy(course) {
    if (confirm(`¿Eliminar el curso ${course.name}? Esto también eliminará sus niveles.`)) {
        router.delete(route('courses.destroy', course.id));
    }
}
</script>

<template>
    <Head title="Cursos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Cursos
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
                    <TextInput
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nombre..."
                        class="w-full max-w-sm"
                        @keyup.enter="applySearch"
                    />

                    <Link :href="route('courses.create')">
                        <PrimaryButton>Nuevo curso</PrimaryButton>
                    </Link>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Niveles</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="course in courses.data" :key="course.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ course.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ course.levels_count }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="course.status === 'activo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                    >
                                        {{ course.status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link
                                        :href="route('courses.edit', course.id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        type="button"
                                        class="ml-4 text-red-600 hover:text-red-900"
                                        @click="destroy(course)"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="courses.data.length === 0">
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay cursos registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="courses.links.length > 3" class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in courses.links"
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
