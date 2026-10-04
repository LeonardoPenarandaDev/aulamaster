<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    teacher: Object,
});

const page = usePage();

const form = useForm({
    code: props.teacher.code,
    name: props.teacher.name,
    document: props.teacher.document,
    email: props.teacher.email,
    phone: props.teacher.phone,
    status: props.teacher.status,
});

function submit() {
    form.put(route('teachers.update', props.teacher.id));
}

const accessForm = useForm({});

function grantAccess() {
    accessForm.post(route('teachers.portal-access.store', props.teacher.id));
}
</script>

<template>
    <Head title="Editar profesor" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar profesor
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="code" value="Código" />
                                <TextInput id="code" v-model="form.code" class="mt-1 block w-full" required autofocus />
                                <InputError class="mt-2" :message="form.errors.code" />
                            </div>

                            <div>
                                <InputLabel for="status" value="Estado" />
                                <select id="status" v-model="form.status" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
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
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('teachers.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200/80 bg-white p-6">
                    <h3 class="text-sm font-medium text-gray-900">Acceso al portal</h3>

                    <div
                        v-if="page.props.flash?.success"
                        class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50 p-3 text-sm text-emerald-700"
                    >
                        {{ page.props.flash.success }}
                    </div>

                    <p v-if="teacher.user" class="mt-2 text-sm text-gray-600">
                        Ya tiene acceso con el usuario <strong>{{ teacher.user.email }}</strong>.
                    </p>
                    <div v-else class="mt-2">
                        <p class="text-sm text-gray-600">
                            Este profesor todavía no puede iniciar sesión. Crear el acceso genera una cuenta con el correo
                            registrado ({{ teacher.email || 'no hay correo registrado' }}) y una contraseña temporal.
                        </p>
                        <PrimaryButton class="mt-3" :disabled="accessForm.processing || !teacher.email" @click="grantAccess">
                            Crear acceso
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
