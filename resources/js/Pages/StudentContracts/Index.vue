<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Head, router, usePage } from '@inertiajs/vue3';

defineProps({
    contracts: Array,
    imageConsent: Object,
});

const page = usePage();

function revoke() {
    if (confirm('¿Revocar la autorización de uso de imágenes? La institución ya no podrá publicar fotos o videos tuyos.')) {
        router.post(route('student-contracts.revoke-image-consent'), {}, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Mis contratos" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Mis contratos</h2>
        </template>

        <div>
            <div class="space-y-4">
                <div v-if="page.props.flash?.success" class="rounded-2xl bg-green-50 p-4 text-sm text-green-700">
                    {{ page.props.flash.success }}
                </div>

                <div class="rounded-2xl border border-gray-200/80 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-900">Uso de imágenes</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        <template v-if="imageConsent.granted">
                            Autorizaste a la institución a usar fotos y videos en los que apareces<template v-if="imageConsent.updated_at"> (desde el {{ imageConsent.updated_at }})</template>.
                        </template>
                        <template v-else>
                            La institución no tiene tu autorización para usar fotos o videos en los que apareces.
                        </template>
                    </p>
                    <DangerButton v-if="imageConsent.granted" type="button" class="mt-3" @click="revoke">Revocar autorización</DangerButton>
                </div>

                <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white">
                    <ul class="divide-y divide-gray-100">
                        <li v-for="contract in contracts" :key="contract.id" class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                            <div>
                                <p class="font-medium text-gray-900">{{ contract.name }}</p>
                                <p class="text-gray-500">
                                    <template v-if="contract.status === 'firmado'">
                                        {{ contract.signer_role === 'acudiente' ? `Firmado por tu acudiente (${contract.signer_name})` : 'Firmado por ti' }}
                                        el {{ contract.signed_at }}
                                        <template v-if="contract.is_optional"> · {{ contract.decision === 'acepta' ? 'Aceptado' : 'No aceptado' }}</template>
                                    </template>
                                    <template v-else>Pendiente de firma</template>
                                </p>
                            </div>
                            <a
                                v-if="contract.status === 'firmado'"
                                :href="route('contract-signatures.pdf', contract.id)"
                                target="_blank"
                                class="font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                Ver PDF
                            </a>
                        </li>
                        <li v-if="contracts.length === 0" class="p-4 text-sm text-gray-500">Todavía no tienes contratos.</li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
