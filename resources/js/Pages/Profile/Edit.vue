<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    managesOwnCredentials: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AppLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Profile
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <template v-if="managesOwnCredentials">
                    <div
                        class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                    >
                        <UpdatePasswordForm class="max-w-xl" />
                    </div>

                    <div
                        class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                    >
                        <DeleteUserForm class="max-w-xl" />
                    </div>
                </template>

                <div
                    v-else
                    class="bg-white p-4 text-sm text-gray-600 shadow sm:rounded-lg sm:p-8"
                >
                    Tu contraseña la administra el instituto. Si la olvidaste o necesitas cambiarla, escríbenos y te asignamos una nueva.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
