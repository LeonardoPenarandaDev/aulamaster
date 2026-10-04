<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    roleLabels: Object,
});

const roleHelp = {
    admin: 'Acceso completo al sistema.',
    coordinador: 'Gestiona cursos, niveles, aulas y la programación de clases.',
    cajero: 'Registra pagos, consulta estados de cuenta, matrículas y reportes financieros.',
};

const form = useForm({
    name: '',
    email: '',
    role: 'cajero',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('staff-users.store'));
}
</script>

<template>
    <Head title="Nuevo usuario" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Nuevo usuario del personal
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="name" value="Nombre" />
                            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required autofocus />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="email" value="Correo (usuario de acceso)" />
                                <TextInput id="email" type="email" v-model="form.email" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div>
                                <InputLabel for="role" value="Rol" />
                                <select id="role" v-model="form.role" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="(label, role) in roleLabels" :key="role" :value="role">{{ label }}</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">{{ roleHelp[form.role] }}</p>
                                <InputError class="mt-2" :message="form.errors.role" />
                            </div>

                            <div>
                                <InputLabel for="password" value="Contraseña" />
                                <TextInput id="password" type="password" v-model="form.password" class="mt-1 block w-full" required autocomplete="new-password" />
                                <InputError class="mt-2" :message="form.errors.password" />
                            </div>

                            <div>
                                <InputLabel for="password_confirmation" value="Confirmar contraseña" />
                                <TextInput id="password_confirmation" type="password" v-model="form.password_confirmation" class="mt-1 block w-full" required autocomplete="new-password" />
                                <InputError class="mt-2" :message="form.errors.password_confirmation" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('staff-users.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
