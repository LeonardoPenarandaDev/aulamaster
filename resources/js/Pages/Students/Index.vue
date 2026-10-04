<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    students: Object,
    filters: Object,
});

const page = usePage();
const isAdmin = page.props.auth.roles?.includes('admin');
const search = ref(props.filters.search ?? '');

function applySearch() {
    router.get(
        route('students.index'),
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function destroy(student) {
    if (confirm(`¿Eliminar al estudiante ${student.name}?`)) {
        router.delete(route('students.destroy', student.id));
    }
}
</script>

<template>
    <Head title="Estudiantes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Estudiantes
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

                    <div class="flex items-center gap-2">
                        <Link v-if="isAdmin" :href="route('students.import.create')">
                            <SecondaryButton>Importar CSV/Excel</SecondaryButton>
                        </Link>
                        <Link :href="route('students.create')">
                            <PrimaryButton>Nuevo estudiante</PrimaryButton>
                        </Link>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200/80 bg-white">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Correo</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="student in students.data" :key="student.id" class="transition hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ student.code }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    <div class="flex items-center gap-3">
                                    <StudentAvatar :name="student.name" :photo-url="student.photo_url" size="sm" />
                                    <span>{{ student.name }}</span>
                                    <span v-if="student.is_minor" class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Menor</span>
                                    <span
                                        v-if="student.missing_contract_data.length"
                                        class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700"
                                        :title="`Falta: ${student.missing_contract_data.join(', ')}`"
                                    >
                                        Datos incompletos
                                    </span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ student.email }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="student.status === 'activo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                    >
                                        {{ student.status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link
                                        :href="route('students.edit', student.id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        v-if="isAdmin"
                                        type="button"
                                        class="ml-4 text-red-600 hover:text-red-900"
                                        @click="destroy(student)"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="students.data.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay estudiantes registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="students.links.length > 3" class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in students.links"
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
