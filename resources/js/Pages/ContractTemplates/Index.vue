<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    templates: Array,
    types: Object,
});

const page = usePage();

const statusClasses = {
    borrador: 'bg-amber-100 text-amber-800',
    publicado: 'bg-green-100 text-green-800',
    archivado: 'bg-gray-100 text-gray-700',
};
</script>

<template>
    <Head title="Plantillas de contrato" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Plantillas de contrato
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

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <p class="max-w-2xl text-sm text-gray-500">
                        Solo las plantillas publicadas se asignan a las matrículas. Una plantilla publicada no se edita: se crea
                        una versión nueva, y los contratos ya firmados conservan la versión con la que se firmaron.
                    </p>

                    <Link :href="route('contract-templates.create')">
                        <PrimaryButton>Nueva plantilla</PrimaryButton>
                    </Link>
                </div>

                <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Plantilla</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reglas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Versión vigente</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="template in templates" :key="template.code">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ template.current.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ types[template.current.type] }}</td>
                                <td class="px-6 py-4 text-xs text-gray-600">
                                    <div>{{ template.current.acceptance_mode === 'obligatorio' ? 'Obligatorio' : 'Opcional (Sí/No)' }}</div>
                                    <div>{{ template.current.scope === 'matricula' ? 'Por matrícula' : 'Una vez por alumno' }}</div>
                                    <div v-if="template.current.requires_guardian">Solo menores de edad</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[template.current.status]">
                                        v{{ template.current.version }} · {{ template.current.status }}
                                    </span>
                                    <span v-if="template.draft && template.draft.id !== template.current.id" class="ml-2 rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses.borrador">
                                        v{{ template.draft.version }} en borrador
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link
                                        v-if="template.draft"
                                        :href="route('contract-templates.edit', template.draft.id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Editar borrador
                                    </Link>
                                    <Link
                                        v-if="template.current.status !== 'borrador'"
                                        :href="route('contract-templates.edit', template.current.id)"
                                        class="ml-4 text-gray-600 hover:text-gray-900"
                                    >
                                        Ver
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="templates.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Todavía no hay plantillas. Crea una para cada contrato del abogado (matrícula, uso de imágenes, tratamiento de datos…).
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
