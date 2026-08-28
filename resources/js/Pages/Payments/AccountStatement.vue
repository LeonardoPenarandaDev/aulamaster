<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    student: Object,
    statement: Object,
    payments: Array,
});

const statusLabels = { pendiente: 'Pendiente', pagado: 'Pagado', vencido: 'Vencido', anulado: 'Anulado' };
const statusClasses = {
    pendiente: 'bg-yellow-100 text-yellow-800',
    pagado: 'bg-green-100 text-green-800',
    vencido: 'bg-red-100 text-red-800',
    anulado: 'bg-gray-100 text-gray-800',
};

const accountStatusLabels = { al_dia: 'Al día', pendiente: 'Pendiente', vencido: 'Vencido' };
const accountStatusClasses = {
    al_dia: 'bg-green-100 text-green-800',
    pendiente: 'bg-yellow-100 text-yellow-800',
    vencido: 'bg-red-100 text-red-800',
};

function money(value) {
    return Number(value).toLocaleString('es-CO');
}
</script>

<template>
    <Head :title="`Estado de cuenta - ${student.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Estado de cuenta — {{ student.name }} ({{ student.code }})
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <Link :href="route('payments.index')" class="text-sm text-indigo-600 hover:text-indigo-900">
                    ← Volver a pagos
                </Link>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <p class="text-xs text-gray-500">Total facturado</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">${{ money(statement.total_billed) }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <p class="text-xs text-gray-500">Total pagado</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">${{ money(statement.total_paid) }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <p class="text-xs text-gray-500">Saldo pendiente</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">${{ money(statement.balance) }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <p class="text-xs text-gray-500">Estado</p>
                        <span class="mt-1 inline-block rounded-full px-2 py-1 text-xs font-medium" :class="accountStatusClasses[statement.status]">
                            {{ accountStatusLabels[statement.status] }}
                        </span>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Concepto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Valor final</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Método</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="payment in payments" :key="payment.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ payment.concept }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">${{ money(payment.final_amount) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ payment.paid_at ?? '—' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ payment.payment_method ?? '—' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[payment.status]">
                                        {{ statusLabels[payment.status] }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="payments.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Este estudiante no tiene pagos registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
