<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import GlobalLevelCheckboxes from '@/Components/GlobalLevelCheckboxes.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    globalLevels: { type: Array, default: () => [] },
});

const form = useForm({
    global_levels: [],
    code: '',
    name: '',
    document: '',
    email: '',
    phone: '',
    status: 'activo',
    portal_access: true,
});

function submit() {
    form.post(route('teachers.store'));
}
</script>

<template>
    <Head title="Nuevo profesor" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Nuevo profesor
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

                        <div>
                            <InputLabel value="Niveles que dicta" />
                            <p class="mb-2 text-xs text-gray-500">Al programar una clase se proponen solo los docentes de ese nivel.</p>
                            <GlobalLevelCheckboxes v-model="form.global_levels" :options="globalLevels" />
                            <InputError class="mt-2" :message="form.errors.global_levels || form.errors['global_levels.0']" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="document" value="Documento" />
                                <TextInput id="document" v-model="form.document" class="mt-1 block w-full" :required="form.portal_access" />
                                <InputError class="mt-2" :message="form.errors.document" />
                            </div>

                            <div>
                                <InputLabel for="email" value="Correo" />
                                <TextInput id="email" type="email" v-model="form.email" class="mt-1 block w-full" :required="form.portal_access" />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div>
                                <InputLabel for="phone" value="Teléfono" />
                                <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.phone" />
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-6">
                            <label class="flex items-center gap-2">
                                <Checkbox name="portal_access" v-model:checked="form.portal_access" />
                                <span class="text-sm font-medium text-gray-900">Crear acceso a la app</span>
                            </label>
                            <p class="mt-1 text-sm text-gray-500">
                                El profesor ingresará con su <strong>correo</strong> como usuario y su <strong>número de documento</strong> como contraseña.
                                Luego puedes cambiarle la contraseña desde la edición del profesor.
                            </p>
                            <InputError class="mt-2" :message="form.errors.portal_access" />
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('teachers.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
