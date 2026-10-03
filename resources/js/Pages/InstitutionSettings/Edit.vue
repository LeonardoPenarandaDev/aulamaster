<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    settings: Object,
});

const page = usePage();

const form = useForm({
    name: props.settings.name,
    tax_id: props.settings.tax_id,
    address: props.settings.address,
    phone: props.settings.phone,
    email: props.settings.email,
    signer_name: props.settings.signer_name,
    signer_title: props.settings.signer_title,
    payment_due_day: props.settings.payment_due_day ?? 5,
    payment_reminder_days: props.settings.payment_reminder_days ?? 2,
    overdue_alert_days: props.settings.overdue_alert_days ?? 10,
    logo: null,
});

const logoPreview = ref(props.settings.logo_url);

function onLogoChange(event) {
    const file = event.target.files[0] ?? null;
    form.logo = file;
    logoPreview.value = file ? URL.createObjectURL(file) : props.settings.logo_url;
}

function submit() {
    form.post(route('institution-settings.update'), { forceFormData: true });
}
</script>

<template>
    <Head title="Configuración institucional" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Configuración institucional
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

                <p class="mb-4 text-sm text-gray-500">
                    Estos datos se usan como membrete de los certificados de nivel aprobado (logo, nombre, contacto y firma).
                </p>

                <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel value="Logo" />
                            <div class="mt-2 flex items-center gap-4">
                                <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                                    <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="h-full w-full object-contain" />
                                    <span v-else class="text-xs text-gray-400">Sin logo</span>
                                </div>
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="onLogoChange"
                                    class="block text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                                />
                            </div>
                            <InputError class="mt-2" :message="form.errors.logo" />
                        </div>

                        <div>
                            <InputLabel for="name" value="Nombre del instituto" />
                            <TextInput id="name" type="text" v-model="form.name" class="mt-1 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="tax_id" value="NIT / identificación" />
                                <TextInput id="tax_id" type="text" v-model="form.tax_id" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.tax_id" />
                            </div>
                            <div>
                                <InputLabel for="phone" value="Teléfono" />
                                <TextInput id="phone" type="text" v-model="form.phone" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.phone" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="address" value="Dirección" />
                            <TextInput id="address" type="text" v-model="form.address" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.address" />
                        </div>

                        <div>
                            <InputLabel for="email" value="Correo" />
                            <TextInput id="email" type="email" v-model="form.email" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 border-t border-gray-100 pt-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="signer_name" value="Nombre de quien firma" />
                                <TextInput id="signer_name" type="text" v-model="form.signer_name" class="mt-1 block w-full" placeholder="Ej: María López" />
                                <InputError class="mt-2" :message="form.errors.signer_name" />
                            </div>
                            <div>
                                <InputLabel for="signer_title" value="Cargo de quien firma" />
                                <TextInput id="signer_title" type="text" v-model="form.signer_title" class="mt-1 block w-full" placeholder="Ej: Directora académica" />
                                <InputError class="mt-2" :message="form.errors.signer_title" />
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-6">
                            <h3 class="text-sm font-medium text-gray-900">Mensualidades y mora</h3>
                            <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-3">
                                <div>
                                    <InputLabel for="payment_due_day" value="Día límite de pago" />
                                    <TextInput id="payment_due_day" type="number" min="1" max="28" v-model="form.payment_due_day" class="mt-1 block w-full" required />
                                    <InputError class="mt-2" :message="form.errors.payment_due_day" />
                                </div>
                                <div>
                                    <InputLabel for="payment_reminder_days" value="Recordatorio (días antes)" />
                                    <TextInput id="payment_reminder_days" type="number" min="0" max="10" v-model="form.payment_reminder_days" class="mt-1 block w-full" required />
                                    <InputError class="mt-2" :message="form.errors.payment_reminder_days" />
                                </div>
                                <div>
                                    <InputLabel for="overdue_alert_days" value="Alerta al admin (días de mora)" />
                                    <TextInput id="overdue_alert_days" type="number" min="1" max="60" v-model="form.overdue_alert_days" class="mt-1 block w-full" required />
                                    <InputError class="mt-2" :message="form.errors.overdue_alert_days" />
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">
                                La mensualidad se genera el día 1 y vence el día límite. Al día siguiente pasa a "vencido" y el estudiante queda
                                bloqueado hasta pagar o tener un acuerdo de pago.
                            </p>
                        </div>

                        <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
