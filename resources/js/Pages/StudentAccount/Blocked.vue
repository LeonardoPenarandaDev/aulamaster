<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    payments: Array,
});

function money(value) {
    return Number(value).toLocaleString('es-CO');
}
</script>

<template>
    <Head title="Pagos pendientes" />

    <AppLayout>
        <div>
            <div class="space-y-6">
                <div class="rounded-2xl border border-red-100 bg-white p-6 text-center">
                    <h1 class="text-xl font-semibold text-gray-900">Tu cuenta tiene pagos pendientes</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Para seguir usando el portal y asistir a clase, ponte al día con tus pagos. Apenas pagues, el acceso se
                        habilita de inmediato. Si necesitas un acuerdo de pago, comunícate con la institución.
                    </p>
                </div>

                <ul class="divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-200/80 bg-white">
                    <li v-for="payment in payments" :key="payment.id" class="flex flex-wrap items-center justify-between gap-3 p-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ payment.concept }}</p>
                            <p class="text-sm" :class="payment.status === 'vencido' ? 'text-red-600' : 'text-gray-500'">
                                ${{ money(payment.final_amount) }}
                                <template v-if="payment.due_date"> · {{ payment.status === 'vencido' ? 'venció' : 'vence' }} el {{ payment.due_date }}</template>
                            </p>
                        </div>
                        <a
                            :href="route('payments.pay-online', payment.id)"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            Pagar en línea
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
