<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    staffUser: Object,
    roleLabels: Object,
});

const roleHelp = {
    admin: 'Acceso completo al sistema.',
    coordinador: 'Gestiona cursos, niveles, aulas y la programación de clases.',
    cajero: 'Registra pagos, consulta estados de cuenta, matrículas y reportes financieros.',
};

const form = useForm({
    name: props.staffUser.name,
    email: props.staffUser.email,
    role: props.staffUser.role,
    is_active: props.staffUser.is_active,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.put(route('staff-users.update', props.staffUser.id));
}
</script>

<template>
    <Head title="Editar usuario" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar usuario del personal
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
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
                                <select
                                    id="role"
                                    v-model="form.role"
                                    :disabled="staffUser.is_self"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                                >
                                    <option v-for="(label, role) in roleLabels" :key="role" :value="role">{{ label }}</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">{{ roleHelp[form.role] }}</p>
                                <InputError class="mt-2" :message="form.errors.role" />
                            </div>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input
                                    type="checkbox"
                                    v-model="form.is_active"
                                    :disabled="staffUser.is_self"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />
                                Cuenta activa
                            </label>
                            <p class="mt-1 text-xs text-gray-500">
                                <template v-if="staffUser.is_self">No puedes desactivar ni cambiar el rol de tu propia cuenta.</template>
                                <template v-else>Si la desactivas, la persona no podrá iniciar sesión y se cerrará su sesión abierta. Sus registros se conservan.</template>
                            </p>
                            <InputError class="mt-2" :message="form.errors.is_active" />
                        </div>

                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-sm font-medium text-gray-900">Cambiar contraseña (opcional)</h3>
                            <p class="mt-1 text-sm text-gray-500">Déjala vacía para mantener la contraseña actual.</p>

                            <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="password" value="Nueva contraseña" />
                                    <TextInput id="password" type="password" v-model="form.password" class="mt-1 block w-full" autocomplete="new-password" />
                                    <InputError class="mt-2" :message="form.errors.password" />
                                </div>

                                <div>
                                    <InputLabel for="password_confirmation" value="Confirmar contraseña" />
                                    <TextInput id="password_confirmation" type="password" v-model="form.password_confirmation" class="mt-1 block w-full" autocomplete="new-password" />
                                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                                </div>
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
