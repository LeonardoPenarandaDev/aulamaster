<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    payments: Object,
    filters: Object,
    students: Array,
});

const page = usePage();
const filters = ref({
    student_id: props.filters.student_id ?? '',
    status: props.filters.status ?? '',
});

function applyFilters() {
    router.get(route('payments.index'), filters.value, {
        preserveState: true,
        replace: true,
    });
}

const statusLabels = { pendiente: 'Pendiente', pagado: 'Pagado', vencido: 'Vencido', anulado: 'Anulado' };
const statusClasses = {
    pendiente: 'bg-yellow-100 text-yellow-800',
    pagado: 'bg-green-100 text-green-800',
    vencido: 'bg-red-100 text-red-800',
    anulado: 'bg-gray-100 text-gray-800',
};

function money(value) {
    return Number(value).toLocaleString('es-CO');
}
</script>

<template>
    <Head title="Pagos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Pagos
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-md bg-green-50 p-4 text-sm text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div class="flex flex-wrap gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Estudiante</label>
                            <select v-model="filters.student_id" @change="applyFilters" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="s in students" :key="s.id" :value="s.id">{{ s.code }} - {{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Estado</label>
                            <select v-model="filters.status" @change="applyFilters" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </div>
                    </div>

                    <Link :href="route('payments.create')">
                        <PrimaryButton>Registrar pago</PrimaryButton>
                    </Link>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estudiante</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Concepto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Valor final</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fecha de pago</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="payment in payments.data" :key="payment.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    <Link :href="route('students.account-statement', payment.student_id)" class="hover:underline">
                                        {{ payment.student?.code }} - {{ payment.student?.name }}
                                    </Link>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ payment.concept }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">${{ money(payment.final_amount) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ payment.paid_at ?? '—' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[payment.status]">
                                        {{ statusLabels[payment.status] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link :href="route('payments.edit', payment.id)" class="text-indigo-600 hover:text-indigo-900">
                                        Editar
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="payments.data.length === 0">
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay pagos registrados con estos filtros.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="payments.links.length > 3" class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in payments.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        class="rounded-md border px-3 py-1 text-sm"
                        :class="[
                            link.active ? 'border-indigo-500 bg-indigo-50 text-indigo-600' : 'border-gray-200 text-gray-600',
                            !link.url ? 'pointer-events-none opacity-50' : '',
                        ]"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
