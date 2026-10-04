<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    payment: Object,
});

const form = useForm({
    status: props.payment.status,
    paid_at: props.payment.paid_at,
    payment_method: props.payment.payment_method,
    receipt_reference: props.payment.receipt_reference,
    final_amount: null,
});

// Una mensualidad sin pagar se puede corregir (parte 8 del plan de mejoras).
const canCorrectAmount = props.payment.type === 'mensualidad' && ['pendiente', 'vencido'].includes(props.payment.status);

function submit() {
    form.put(route('payments.update', props.payment.id));
}

function money(value) {
    return Number(value).toLocaleString('es-CO');
}
</script>

<template>
    <Head title="Editar pago" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar pago
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                    <div class="mb-6 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                        <p><strong>Concepto:</strong> {{ payment.concept }}</p>
                        <p><strong>Valor base:</strong> ${{ money(payment.base_amount) }}</p>
                        <p><strong>Descuento:</strong> ${{ money(payment.discount_amount) }}</p>
                        <p><strong>Valor final:</strong> ${{ money(payment.final_amount) }}</p>
                        <p v-if="payment.due_date" class="mt-1"><strong>Vence:</strong> {{ payment.due_date.slice(0, 10) }}</p>
                        <p v-if="!canCorrectAmount" class="mt-2 text-xs text-gray-500">
                            Los montos no se pueden editar aquí (son el valor históricamente facturado). Si el pago fue un error, anúlalo cambiando el estado.
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div v-if="canCorrectAmount">
                            <InputLabel for="final_amount" value="Corregir el valor de la mensualidad (opcional)" />
                            <TextInput id="final_amount" type="number" step="1000" min="0" v-model="form.final_amount" class="mt-1 block w-full max-w-xs" :placeholder="String(Number(payment.final_amount))" />
                            <InputError class="mt-2" :message="form.errors.final_amount" />
                            <p class="mt-1 text-xs text-gray-500">La corrección queda registrada en la auditoría.</p>
                        </div>

                        <div>
                            <InputLabel for="status" value="Estado" />
                            <select id="status" v-model="form.status" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="pendiente">Pendiente</option>
                                <option value="pagado">Pagado</option>
                                <option value="vencido">Vencido</option>
                                <option value="anulado">Anulado</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="paid_at" value="Fecha de pago" />
                                <TextInput id="paid_at" type="date" v-model="form.paid_at" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.paid_at" />
                            </div>

                            <div>
                                <InputLabel for="payment_method" value="Método de pago" />
                                <TextInput id="payment_method" v-model="form.payment_method" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.payment_method" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="receipt_reference" value="Comprobante" />
                            <TextInput id="receipt_reference" v-model="form.receipt_reference" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.receipt_reference" />
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('payments.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
