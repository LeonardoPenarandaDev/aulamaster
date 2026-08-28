<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    referrals: Object,
});

const page = usePage();

function money(value) {
    return Number(value).toLocaleString('es-CO');
}
</script>

<template>
    <Head title="Referidos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Referidos
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

                <div class="flex items-center justify-between gap-4">
                    <Link :href="route('promotions.index')" class="text-sm text-indigo-600 hover:text-indigo-900">
                        ← Volver a promociones
                    </Link>
                    <Link :href="route('referrals.create')">
                        <PrimaryButton>Nuevo referido</PrimaryButton>
                    </Link>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estudiante referente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Beneficio referente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estudiante nuevo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Beneficio nuevo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="referral in referrals.data" :key="referral.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ referral.referrer?.code }} - {{ referral.referrer?.name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">${{ money(referral.referrer_discount) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ referral.referred?.code }} - {{ referral.referred?.name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">${{ money(referral.referred_discount) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ referral.created_at?.slice(0, 10) }}</td>
                            </tr>
                            <tr v-if="referrals.data.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay referidos registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="referrals.links.length > 3" class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in referrals.links"
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
