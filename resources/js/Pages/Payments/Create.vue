<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    students: Array,
    enrollments: Array,
    promotions: Array,
    referrals: Array,
});

const form = useForm({
    student_id: props.students[0]?.id ?? '',
    enrollment_id: '',
    concept: 'Matrícula',
    base_amount: '',
    discount_amount: 0,
    final_amount: '',
    promotion_id: '',
    referral_id: '',
    paid_at: '',
    payment_method: '',
    receipt_reference: '',
    status: 'pendiente',
});

const studentEnrollments = computed(() => props.enrollments.filter((e) => e.student_id === Number(form.student_id)));

watch(() => form.student_id, () => {
    form.enrollment_id = '';
});

watch([() => form.base_amount, () => form.discount_amount], () => {
    const base = Number(form.base_amount) || 0;
    const discount = Number(form.discount_amount) || 0;
    form.final_amount = Math.max(0, base - discount);
});

function submit() {
    form.post(route('payments.store'));
}
</script>

<template>
    <Head title="Registrar pago" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Registrar pago
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="student_id" value="Estudiante" />
                                <select id="student_id" v-model="form.student_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.code }} - {{ student.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.student_id" />
                            </div>

                            <div>
                                <InputLabel for="enrollment_id" value="Matrícula (opcional)" />
                                <select id="enrollment_id" v-model="form.enrollment_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Sin matrícula asociada</option>
                                    <option v-for="enrollment in studentEnrollments" :key="enrollment.id" :value="enrollment.id">{{ enrollment.level?.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.enrollment_id" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="concept" value="Concepto" />
                            <TextInput id="concept" v-model="form.concept" class="mt-1 block w-full" required placeholder="Matrícula, Mensualidad, Recuperación..." />
                            <InputError class="mt-2" :message="form.errors.concept" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <InputLabel for="base_amount" value="Valor base" />
                                <TextInput id="base_amount" type="number" step="0.01" v-model="form.base_amount" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.base_amount" />
                            </div>

                            <div>
                                <InputLabel for="discount_amount" value="Descuento" />
                                <TextInput id="discount_amount" type="number" step="0.01" v-model="form.discount_amount" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.discount_amount" />
                            </div>

                            <div>
                                <InputLabel for="final_amount" value="Valor final" />
                                <TextInput id="final_amount" type="number" step="0.01" v-model="form.final_amount" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.final_amount" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="promotion_id" value="Promoción" />
                                <select id="promotion_id" v-model="form.promotion_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Ninguna</option>
                                    <option v-for="promotion in promotions" :key="promotion.id" :value="promotion.id">{{ promotion.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.promotion_id" />
                            </div>

                            <div>
                                <InputLabel for="referral_id" value="Referido" />
                                <select id="referral_id" v-model="form.referral_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Ninguno</option>
                                    <option v-for="referral in referrals" :key="referral.id" :value="referral.id">
                                        {{ referral.referrer?.name }} → {{ referral.referred?.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.referral_id" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <InputLabel for="paid_at" value="Fecha de pago" />
                                <TextInput id="paid_at" type="date" v-model="form.paid_at" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.paid_at" />
                            </div>

                            <div>
                                <InputLabel for="payment_method" value="Método de pago" />
                                <TextInput id="payment_method" v-model="form.payment_method" class="mt-1 block w-full" placeholder="Efectivo, transferencia..." />
                                <InputError class="mt-2" :message="form.errors.payment_method" />
                            </div>

                            <div>
                                <InputLabel for="receipt_reference" value="Comprobante" />
                                <TextInput id="receipt_reference" v-model="form.receipt_reference" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.receipt_reference" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="status" value="Estado" />
                            <select id="status" v-model="form.status" class="mt-1 block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="pendiente">Pendiente</option>
                                <option value="pagado">Pagado</option>
                                <option value="vencido">Vencido</option>
                                <option value="anulado">Anulado</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
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
