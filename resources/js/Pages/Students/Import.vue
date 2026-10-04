<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    columns: Array,
    maxRows: Number,
});

const form = useForm({
    file: null,
});

const rowErrors = computed(() =>
    Object.entries(form.errors)
        .filter(([key]) => key.startsWith('row_'))
        .sort(([a], [b]) => Number(a.slice(4)) - Number(b.slice(4)))
        .map(([, message]) => message),
);

const columnHelp = {
    codigo: 'Obligatorio. Único por estudiante (máx. 20 caracteres).',
    nombre: 'Obligatorio.',
    tipo_documento: 'Opcional: CC, TI, RC, CE, PA o PPT.',
    documento: 'Opcional.',
    fecha_nacimiento: 'Opcional: AAAA-MM-DD o DD/MM/AAAA. Necesaria para enviar contratos.',
    correo: 'Opcional. Obligatorio si se indica contraseña.',
    telefono: 'Opcional.',
    direccion: 'Opcional.',
    estado: 'Opcional: activo o inactivo (por defecto activo).',
    contrasena: 'Opcional. Si se indica, se crea el acceso al portal con el correo.',
    acudiente_nombre: 'Obligatorio para enviar contratos si el estudiante es menor de edad.',
    acudiente_tipo_documento: 'Opcional: CC, CE, PA o PPT.',
    acudiente_documento: 'Opcional.',
    acudiente_parentesco: 'Opcional (madre, padre, tío…).',
    acudiente_correo: 'Obligatorio para enviar contratos si el estudiante es menor de edad.',
    acudiente_telefono: 'Opcional. Con indicativo, p. ej. +57 300 123 4567.',
};

function submit() {
    form.post(route('students.import.store'), {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Importar estudiantes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Importar estudiantes
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                    <h3 class="text-sm font-medium text-gray-900">Formato del archivo</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Sube un archivo CSV o Excel (.xlsx) con una fila de encabezados y un estudiante por fila
                        (máximo {{ maxRows }}). Si alguna fila tiene errores no se registra ningún estudiante,
                        así puedes corregir el archivo y volver a subirlo.
                    </p>

                    <table class="mt-4 min-w-full divide-y divide-slate-100 text-sm">
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="column in columns" :key="column" class="transition hover:bg-slate-50/70">
                                <td class="whitespace-nowrap py-2 pr-4 font-mono text-gray-900">{{ column }}</td>
                                <td class="py-2 text-gray-500">{{ columnHelp[column] }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <a
                        :href="route('students.import.template')"
                        class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-900"
                    >
                        Descargar plantilla CSV
                    </a>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="file" value="Archivo" />
                            <input
                                id="file"
                                type="file"
                                accept=".csv,.xlsx,.xls"
                                required
                                @change="form.file = $event.target.files[0] ?? null"
                                class="mt-2 block text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                            />
                            <InputError class="mt-2" :message="form.errors.file" />
                        </div>

                        <div v-if="rowErrors.length" class="rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-700">
                            <p class="font-medium">
                                No se registró ningún estudiante. Corrige {{ rowErrors.length === 1 ? 'la siguiente fila' : `las siguientes ${rowErrors.length} filas` }} y vuelve a subir el archivo:
                            </p>
                            <ul class="mt-2 max-h-80 list-disc space-y-1 overflow-y-auto pl-5">
                                <li v-for="message in rowErrors" :key="message">{{ message }}</li>
                            </ul>
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">
                                {{ form.processing ? 'Importando...' : 'Importar' }}
                            </PrimaryButton>
                            <Link :href="route('students.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
