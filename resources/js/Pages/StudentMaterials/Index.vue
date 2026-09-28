<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    sessions: { type: Array, default: () => [] },
});

function dayLabel(date) {
    return new Intl.DateTimeFormat('es-CO', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date(`${date}T00:00:00`));
}
</script>

<template>
    <Head title="Material de clase" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Material de clase
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-600">
                    Material de repaso que compartieron tus profesores en las clases a las que asististe.
                </p>

                <div v-if="sessions.length === 0" class="rounded-xl border border-gray-100 bg-white p-4 text-sm text-gray-600 shadow-sm">
                    Todavía no hay material disponible. Aparecerá aquí cuando un profesor comparta material en una clase a la que asististe.
                </div>

                <div v-for="session in sessions" :key="session.id" class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                    <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
                        <p class="text-sm font-semibold text-gray-900 first-letter:uppercase">{{ dayLabel(session.date) }}</p>
                        <p class="text-xs text-gray-500">
                            {{ session.level }} · {{ session.start_time }} – {{ session.end_time }}
                        </p>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        <li v-for="material in session.materials" :key="material.id" class="px-5 py-3">
                            <a :href="material.url" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:text-indigo-800">
                                {{ material.title }}
                            </a>
                            <p v-if="material.description" class="mt-0.5 text-sm text-gray-600">{{ material.description }}</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
