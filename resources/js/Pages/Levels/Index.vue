<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    levels: Object,
    routes: Array,
    filters: Object,
});

const page = usePage();
const search = ref(props.filters.search ?? '');

function applySearch() {
    router.get(
        route('levels.index'),
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function destroy(level) {
    if (confirm(`¿Eliminar el nivel ${level.name}?`)) {
        router.delete(route('levels.destroy', level.id));
    }
}
</script>

<template>
    <Head title="Niveles" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Niveles
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

                <div v-if="routes.length" class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-sm font-medium text-gray-900">Rutas de niveles</h3>
                    <p class="mt-1 text-xs text-gray-500">
                        Al aprobar un nivel, el estudiante queda matriculado en el siguiente. Configúralo con "Nivel siguiente" al editar cada nivel.
                    </p>
                    <div class="mt-4 space-y-4">
                        <div v-for="courseRoute in routes" :key="courseRoute.course">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">{{ courseRoute.course }}</p>
                            <div class="mt-2 space-y-2">
                                <ol
                                    v-for="(path, pathIndex) in courseRoute.paths"
                                    :key="pathIndex"
                                    class="flex flex-wrap items-center gap-2"
                                >
                                    <template v-for="(step, index) in path" :key="step.id">
                                        <li>
                                            <Link
                                                :href="route('levels.edit', step.id)"
                                                class="block rounded-full border border-gray-200 px-3 py-1 text-sm text-gray-800 hover:border-gray-400"
                                                :style="{ backgroundColor: step.color }"
                                            >
                                                {{ step.name }}
                                            </Link>
                                        </li>
                                        <li v-if="index < path.length - 1" aria-hidden="true" class="text-gray-400">→</li>
                                    </template>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-4">
                    <TextInput
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nombre o código..."
                        class="w-full max-w-sm"
                        @keyup.enter="applySearch"
                    />

                    <Link :href="route('levels.create')">
                        <PrimaryButton>Nuevo nivel</PrimaryButton>
                    </Link>
                </div>

                <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Curso</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nivel siguiente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Horas requeridas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Precio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="level in levels.data" :key="level.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ level.code }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="inline-block h-4 w-4 shrink-0 rounded-full border border-gray-300"
                                            :style="{ backgroundColor: level.color }"
                                            :title="level.color"
                                        />
                                        {{ level.name }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ level.course?.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ level.next_level?.name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ level.required_hours }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ Number(level.price).toLocaleString('es-CO') }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="level.status === 'activo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                    >
                                        {{ level.status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link
                                        :href="route('levels.edit', level.id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        type="button"
                                        class="ml-4 text-red-600 hover:text-red-900"
                                        @click="destroy(level)"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="levels.data.length === 0">
                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay niveles registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="levels.links.length > 3" class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in levels.links"
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
