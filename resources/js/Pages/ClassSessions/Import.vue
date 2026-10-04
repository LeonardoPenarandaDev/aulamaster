<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    columns: Array,
    nextMonday: String,
});

const columnHelp = {
    dia: 'lunes a domingo',
    hora_inicio: 'por ejemplo 08:00',
    hora_fin: 'por ejemplo 10:00',
    nivel: 'código del nivel',
    aula: 'nombre del aula',
    modalidad: 'presencial o virtual (por defecto presencial)',
    docente: 'correo o documento; vacío = "Sin docente"',
    enlace_virtual: 'opcional',
    notas: 'opcional',
};

const form = useForm({
    file: null,
    week: props.nextMonday,
    repeat_weeks: 1,
});

function submit() {
    form.post(route('class-sessions.import.preview'), { forceFormData: true });
}
</script>

<template>
    <Head title="Importar horarios" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Programar la semana desde CSV / Excel</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                    <h3 class="text-sm font-medium text-gray-900">Formato del archivo</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Una clase por fila. Descarga la plantilla: trae ejemplos, instrucciones y listas desplegables con los niveles, aulas y docentes.
                    </p>
                    <table class="mt-4 min-w-full divide-y divide-slate-100 text-sm">
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="column in columns" :key="column" class="transition hover:bg-slate-50/70">
                                <td class="whitespace-nowrap py-2 pr-4 font-mono text-gray-900">{{ column }}</td>
                                <td class="py-2 text-gray-500">{{ columnHelp[column] }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <a :href="route('class-sessions.import.template')" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-900">
                        Descargar plantilla (.xlsx)
                    </a>
                </div>

                <form class="space-y-6 rounded-2xl border border-slate-200/80 bg-white p-6" @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="week" value="Semana (cualquier día de la semana)" />
                            <TextInput id="week" type="date" v-model="form.week" class="mt-1 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.week" />
                        </div>
                        <div>
                            <InputLabel for="repeat_weeks" value="Repetir durante (semanas)" />
                            <TextInput id="repeat_weeks" type="number" min="1" max="12" v-model="form.repeat_weeks" class="mt-1 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.repeat_weeks" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="file" value="Archivo" />
                        <input
                            id="file"
                            type="file"
                            accept=".csv,.xlsx,.xls"
                            required
                            class="mt-2 block text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                            @change="form.file = $event.target.files[0] ?? null"
                        />
                        <InputError class="mt-2" :message="form.errors.file" />
                    </div>

                    <div class="flex items-center gap-4">
                        <PrimaryButton :disabled="form.processing || !form.file">Ver vista previa</PrimaryButton>
                        <Link :href="route('calendar.index')" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
