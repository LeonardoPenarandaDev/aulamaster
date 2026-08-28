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

const form = useForm({
    level_id: props.levels[0]?.id ?? '',
    teacher_id: props.teachers[0]?.id ?? '',
    classroom_id: props.classrooms[0]?.id ?? '',
    date: '',
    start_time: '',
    end_time: '',
    status: 'programada',
    notes: '',
});

function submit() {
    form.post(route('class-sessions.store'));
}
</script>

<template>
    <Head title="Nueva clase" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Nueva clase
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
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

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <InputLabel for="date" value="Fecha" />
                                <TextInput id="date" type="date" v-model="form.date" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.date" />
                            </div>

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

                        <div>
                            <InputLabel for="status" value="Estado" />
                            <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="programada">Programada</option>
                                <option value="dictada">Dictada</option>
                                <option value="cancelada">Cancelada</option>
                                <option value="reprogramada">Reprogramada</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
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
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('class-sessions.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
