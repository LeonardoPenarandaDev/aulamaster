<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    classroom: Object,
});

const form = useForm({
    name: props.classroom.name,
    code: props.classroom.code,
    capacity: props.classroom.capacity,
    location: props.classroom.location,
    floor: props.classroom.floor,
    status: props.classroom.status,
    notes: props.classroom.notes,
});

function submit() {
    form.put(route('classrooms.update', props.classroom.id));
}
</script>

<template>
    <Head title="Editar aula" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar aula
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="name" value="Nombre" />
                                <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required autofocus />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="code" value="Código" />
                                <TextInput id="code" v-model="form.code" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.code" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <InputLabel for="capacity" value="Capacidad" />
                                <TextInput id="capacity" type="number" v-model="form.capacity" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.capacity" />
                            </div>

                            <div>
                                <InputLabel for="location" value="Ubicación" />
                                <TextInput id="location" v-model="form.location" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.location" />
                            </div>

                            <div>
                                <InputLabel for="floor" value="Piso" />
                                <TextInput id="floor" v-model="form.floor" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.floor" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="status" value="Estado" />
                            <select id="status" v-model="form.status" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="disponible">Disponible</option>
                                <option value="no_disponible">No disponible</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>

                        <div>
                            <InputLabel for="notes" value="Observaciones" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('classrooms.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
