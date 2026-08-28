<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    levels: Array,
    teachers: Array,
    classrooms: Array,
});

const days = [
    { value: 1, label: 'Lunes' },
    { value: 2, label: 'Martes' },
    { value: 3, label: 'Miércoles' },
    { value: 4, label: 'Jueves' },
    { value: 5, label: 'Viernes' },
    { value: 6, label: 'Sábado' },
    { value: 7, label: 'Domingo' },
];

const form = useForm({
    level_id: props.levels[0]?.id ?? '',
    teacher_id: props.teachers[0]?.id ?? '',
    classroom_id: props.classrooms[0]?.id ?? '',
    days_of_week: [],
    start_time: '',
    end_time: '',
    start_date: '',
    end_date: '',
    status: 'activo',
    notes: '',
});

function submit() {
    form.post(route('class-schedules.store'));
}
</script>

<template>
    <Head title="Nuevo horario recurrente" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Nuevo horario recurrente
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="mb-6 text-sm text-gray-600">
                        Se generará una clase independiente por cada día seleccionado dentro del rango de fechas.
                        Si una fecha genera un conflicto de aula o profesor con una clase existente, esa fecha se omitirá.
                    </p>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="level_id" value="Curso / Nivel" />
                            <select id="level_id" v-model="form.level_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="level in levels" :key="level.id" :value="level.id">{{ level.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.level_id" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="teacher_id" value="Profesor" />
                                <select id="teacher_id" v-model="form.teacher_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.teacher_id" />
                            </div>

                            <div>
                                <InputLabel for="classroom_id" value="Aula" />
                                <select id="classroom_id" v-model="form.classroom_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">{{ classroom.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.classroom_id" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Días de la semana" />
                            <div class="mt-2 flex flex-wrap gap-4">
                                <label v-for="day in days" :key="day.value" class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" :value="day.value" v-model="form.days_of_week" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                    {{ day.label }}
                                </label>
                            </div>
                            <InputError class="mt-2" :message="form.errors.days_of_week" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="start_time" value="Hora de inicio" />
                                <TextInput id="start_time" type="time" v-model="form.start_time" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.start_time" />
                            </div>

                            <div>
                                <InputLabel for="end_time" value="Hora de finalización" />
                                <TextInput id="end_time" type="time" v-model="form.end_time" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.end_time" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="start_date" value="Fecha de inicio del periodo" />
                                <TextInput id="start_date" type="date" v-model="form.start_date" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.start_date" />
                            </div>

                            <div>
                                <InputLabel for="end_date" value="Fecha de finalización del periodo" />
                                <TextInput id="end_date" type="date" v-model="form.end_date" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.end_date" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="notes" value="Observaciones" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Generar clases</PrimaryButton>
                            <Link :href="route('class-schedules.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
