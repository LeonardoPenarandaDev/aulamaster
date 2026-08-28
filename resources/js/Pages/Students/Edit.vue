<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    student: Object,
});

const page = usePage();

const form = useForm({
    code: props.student.code,
    name: props.student.name,
    document: props.student.document,
    email: props.student.email,
    phone: props.student.phone,
    address: props.student.address,
    status: props.student.status,
});

function submit() {
    form.put(route('students.update', props.student.id));
}

const accessForm = useForm({});

function grantAccess() {
    accessForm.post(route('students.portal-access.store', props.student.id));
}
</script>

<template>
    <Head title="Editar estudiante" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar estudiante
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="code" value="Código" />
                                <TextInput id="code" v-model="form.code" class="mt-1 block w-full" required autofocus />
                                <InputError class="mt-2" :message="form.errors.code" />
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

                        <div>
                            <InputLabel for="name" value="Nombre" />
                            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="document" value="Documento" />
                                <TextInput id="document" v-model="form.document" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.document" />
                            </div>

                            <div>
                                <InputLabel for="email" value="Correo" />
                                <TextInput id="email" type="email" v-model="form.email" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div>
                                <InputLabel for="phone" value="Teléfono" />
                                <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.phone" />
                            </div>

                            <div>
                                <InputLabel for="address" value="Dirección" />
                                <TextInput id="address" v-model="form.address" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.address" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('students.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>

                <div class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-sm font-medium text-gray-900">Acceso al portal</h3>

                    <div
                        v-if="page.props.flash?.success"
                        class="mt-3 rounded-md bg-green-50 p-3 text-sm text-green-700"
                    >
                        {{ page.props.flash.success }}
                    </div>

                    <p v-if="student.user" class="mt-2 text-sm text-gray-600">
                        Ya tiene acceso con el usuario <strong>{{ student.user.email }}</strong>.
                    </p>
                    <div v-else class="mt-2">
                        <p class="text-sm text-gray-600">
                            Este estudiante todavía no puede iniciar sesión. Crear el acceso genera una cuenta con el correo
                            registrado ({{ student.email || 'no hay correo registrado' }}) y una contraseña temporal.
                        </p>
                        <PrimaryButton class="mt-3" :disabled="accessForm.processing || !student.email" @click="grantAccess">
                            Crear acceso
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
