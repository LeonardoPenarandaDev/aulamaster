<script setup>
import InstitutionLogo from '@/Components/InstitutionLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center bg-gradient-to-b from-indigo-50 via-white to-white px-4 pt-10 dark:from-indigo-950/40 dark:via-zinc-950 dark:to-zinc-950 sm:justify-center sm:pt-0"
    >
        <ThemeToggle class="absolute right-4 top-4" />
        <Link href="/" class="flex flex-col items-center gap-3 text-center">
            <InstitutionLogo size="lg" />
            <span class="text-xl font-bold tracking-tight text-gray-800">{{ $page.props.institution.name }}</span>
        </Link>

        <div
            class="mt-8 w-full overflow-hidden rounded-2xl border border-gray-100 bg-white px-6 py-8 shadow-sm sm:max-w-md sm:px-8"
        >
            <slot />
        </div>
    </div>
</template>
