<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    status: String,
    message: String,
});

const statusMeta = computed(() => ({
    approved: { icon: 'check-circle', color: 'text-emerald-600', bg: 'bg-emerald-50' },
    declined: { icon: 'alert-triangle', color: 'text-rose-600', bg: 'bg-rose-50' },
    pending: { icon: 'clock', color: 'text-amber-600', bg: 'bg-amber-50' },
}[props.status] ?? { icon: 'wallet', color: 'text-gray-500', bg: 'bg-gray-50' }));
</script>

<template>
    <Head title="Resultado del pago" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Resultado del pago
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-lg sm:px-6 lg:px-8">
                <div class="rounded-xl border border-gray-100 bg-white p-8 text-center shadow-sm">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full" :class="statusMeta.bg">
                        <Icon :name="statusMeta.icon" class="h-8 w-8" :class="statusMeta.color" />
                    </div>
                    <p class="mt-4 text-sm text-gray-700">{{ message }}</p>
                    <Link :href="route('dashboard')" class="mt-6 inline-block">
                        <PrimaryButton>Volver al dashboard</PrimaryButton>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
