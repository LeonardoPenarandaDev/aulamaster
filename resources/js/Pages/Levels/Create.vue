<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import LevelColorPicker from '@/Components/LevelColorPicker.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    courses: Array,
    levelOptions: Array,
});

const form = useForm({
    course_id: props.courses[0]?.id ?? '',
    name: '',
    color: '#DBEAFE',
    next_level_id: null,
    code: '',
    duration_months: 4,
    weekly_hours: '',
    monthly_hours: '',
    required_hours: '',
    minimum_grade: 70,
    price: '',
    monthly_fee: '',
    start_date: '',
    end_date: '',
    status: 'activo',
});

const nextLevelOptions = computed(() =>
    props.levelOptions.filter((option) => option.course_id === Number(form.course_id)),
);

function submit() {
    form.post(route('levels.store'));
}
</script>

<template>
    <Head title="Nuevo nivel" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Nuevo nivel
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="course_id" value="Curso" />
                                <select id="course_id" v-model="form.course_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.course_id" />
                            </div>

                            <div>
                                <InputLabel for="status" value="Estado" />
                                <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="name" value="Nombre" />
                                <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required autofocus placeholder="A1" />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="code" value="Código" />
                                <TextInput id="code" v-model="form.code" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.code" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Color del nivel" />
                            <p class="mt-1 text-xs text-gray-500">Se usa como fondo del portal de los estudiantes de este nivel.</p>
                            <LevelColorPicker v-model="form.color" class="mt-2" />
                            <InputError class="mt-2" :message="form.errors.color" />
                        </div>

                        <div>
                            <InputLabel for="next_level_id" value="Nivel siguiente" />
                            <select id="next_level_id" v-model="form.next_level_id" class="mt-1 block w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option :value="null">Ninguno (último nivel de la ruta)</option>
                                <option v-for="option in nextLevelOptions" :key="option.id" :value="option.id">{{ option.name }} ({{ option.code }})</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Al aprobar este nivel, el estudiante queda matriculado automáticamente en el siguiente como pendiente.</p>
                            <InputError class="mt-2" :message="form.errors.next_level_id" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <InputLabel for="duration_months" value="Duración (meses)" />
                                <TextInput id="duration_months" type="number" v-model="form.duration_months" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.duration_months" />
                            </div>

                            <div>
                                <InputLabel for="weekly_hours" value="Horas semanales" />
                                <TextInput id="weekly_hours" type="number" step="0.5" v-model="form.weekly_hours" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.weekly_hours" />
                            </div>

                            <div>
                                <InputLabel for="monthly_hours" value="Horas mensuales" />
                                <TextInput id="monthly_hours" type="number" step="0.5" v-model="form.monthly_hours" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.monthly_hours" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="required_hours" value="Horas totales requeridas" />
                                <TextInput id="required_hours" type="number" step="0.5" v-model="form.required_hours" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.required_hours" />
                            </div>

                            <div>
                                <InputLabel for="minimum_grade" value="Nota mínima" />
                                <TextInput id="minimum_grade" type="number" step="0.1" v-model="form.minimum_grade" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.minimum_grade" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="price" value="Precio de la matrícula" />
                                <TextInput id="price" type="number" step="0.01" v-model="form.price" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.price" />
                            </div>

                            <div>
                                <InputLabel for="monthly_fee" value="Mensualidad (opcional)" />
                                <TextInput id="monthly_fee" type="number" step="1000" min="0" v-model="form.monthly_fee" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.monthly_fee" />
                                <p class="mt-1 text-xs text-gray-500">Valor de referencia; se puede ajustar en cada matrícula. Vacío: el nivel no cobra mensualidad.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="start_date" value="Fecha de inicio" />
                                <TextInput id="start_date" type="date" v-model="form.start_date" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.start_date" />
                            </div>

                            <div>
                                <InputLabel for="end_date" value="Fecha de finalización" />
                                <TextInput id="end_date" type="date" v-model="form.end_date" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.end_date" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('levels.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
