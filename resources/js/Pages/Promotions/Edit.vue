<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    promotion: Object,
    courses: Array,
    levels: Array,
});

const page = usePage();

const form = useForm({
    name: props.promotion.name,
    description: props.promotion.description,
    discount_type: props.promotion.discount_type,
    value: props.promotion.value,
    start_date: props.promotion.start_date,
    end_date: props.promotion.end_date,
    course_id: props.promotion.course_id ?? '',
    level_id: props.promotion.level_id ?? '',
    max_uses: props.promotion.max_uses,
    status: props.promotion.status,
});

function submit() {
    form.put(route('promotions.update', props.promotion.id));
}

const notifyForm = useForm({});

function notifyStudents() {
    if (confirm('¿Enviar una notificación a los estudiantes activos sobre esta promoción?')) {
        notifyForm.post(route('promotions.notify', props.promotion.id));
    }
}
</script>

<template>
    <Head title="Editar promoción" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar promoción
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-md bg-green-50 p-4 text-sm text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="name" value="Nombre" />
                            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required autofocus />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Descripción" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="discount_type" value="Tipo de descuento" />
                                <select id="discount_type" v-model="form.discount_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="porcentaje">Porcentaje</option>
                                    <option value="fijo">Valor fijo</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.discount_type" />
                            </div>

                            <div>
                                <InputLabel for="value" value="Valor" />
                                <TextInput id="value" type="number" step="0.01" v-model="form.value" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.value" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="start_date" value="Fecha de inicio" />
                                <TextInput id="start_date" type="date" v-model="form.start_date" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.start_date" />
                            </div>

                            <div>
                                <InputLabel for="end_date" value="Fecha de finalización" />
                                <TextInput id="end_date" type="date" v-model="form.end_date" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.end_date" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="course_id" value="Curso aplicable" />
                                <select id="course_id" v-model="form.course_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Todos los cursos</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.course_id" />
                            </div>

                            <div>
                                <InputLabel for="level_id" value="Nivel aplicable" />
                                <select id="level_id" v-model="form.level_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Todos los niveles</option>
                                    <option v-for="level in levels" :key="level.id" :value="level.id">{{ level.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.level_id" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="max_uses" value="Cantidad máxima de usos" />
                                <TextInput id="max_uses" type="number" v-model="form.max_uses" class="mt-1 block w-full" placeholder="Sin límite" />
                                <InputError class="mt-2" :message="form.errors.max_uses" />
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

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('promotions.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-sm font-medium text-gray-900">Avisar a estudiantes</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Envía una notificación (portal y correo) a los estudiantes activos a los que aplica esta promoción.
                    </p>
                    <SecondaryButton class="mt-3" :disabled="notifyForm.processing" @click="notifyStudents">
                        Notificar
                    </SecondaryButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
