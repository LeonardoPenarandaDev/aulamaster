<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
});

const page = usePage();

const form = useForm({
    free_attempts: props.settings.free_attempts,
    max_paid_attempts: props.settings.max_paid_attempts,
    recovery_price: props.settings.recovery_price,
    recovery_period_days: props.settings.recovery_period_days,
});

function submit() {
    form.put(route('recovery-settings.update'));
}
</script>

<template>
    <Head title="Configuración de recuperaciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Configuración de recuperaciones
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="free_attempts" value="Recuperaciones gratuitas" />
                                <TextInput id="free_attempts" type="number" v-model="form.free_attempts" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.free_attempts" />
                            </div>

                            <div>
                                <InputLabel for="max_paid_attempts" value="Recuperaciones pagadas máximas" />
                                <TextInput id="max_paid_attempts" type="number" v-model="form.max_paid_attempts" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.max_paid_attempts" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="recovery_price" value="Valor de la recuperación pagada" />
                                <TextInput id="recovery_price" type="number" step="0.01" v-model="form.recovery_price" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.recovery_price" />
                            </div>

                            <div>
                                <InputLabel for="recovery_period_days" value="Plazo para presentar (días)" />
                                <TextInput id="recovery_period_days" type="number" v-model="form.recovery_period_days" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.recovery_period_days" />
                            </div>
                        </div>

                        <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
