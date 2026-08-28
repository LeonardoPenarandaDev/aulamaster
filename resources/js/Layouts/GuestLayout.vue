<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gradient-to-b from-indigo-50 via-white to-white pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/" class="flex flex-col items-center gap-2">
                <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-600 shadow-lg shadow-indigo-200">
                    <ApplicationLogo class="h-9 w-9 fill-current text-white" />
                </span>
                <span class="text-xl font-bold tracking-tight text-gray-800">AulaMaster</span>
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
