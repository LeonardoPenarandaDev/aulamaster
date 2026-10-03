<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import MaterialViewer from '@/Components/MaterialViewer.vue';
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

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Material de clase
            </h2>
        </template>

        <div>
            <div class="space-y-6">
                <p class="text-sm text-gray-600">
                    Material de repaso que compartieron tus profesores en las clases a las que asististe.
                </p>

                <div v-if="sessions.length === 0" class="rounded-2xl border border-gray-200/80 bg-white p-4 text-sm text-gray-600">
                    Todavía no hay material disponible. Aparecerá aquí cuando un profesor comparta material en una clase a la que asististe.
                </div>

                <div v-for="session in sessions" :key="session.id" class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white">
                    <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
                        <p class="text-sm font-semibold text-gray-900 first-letter:uppercase">{{ dayLabel(session.date) }}</p>
                        <p class="text-xs text-gray-500">
                            {{ session.level }} · {{ session.start_time }} – {{ session.end_time }}
                        </p>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        <li v-for="material in session.materials" :key="material.id" class="space-y-2 px-5 py-4">
                            <p class="font-medium text-gray-900">{{ material.title }}</p>
                            <p v-if="material.description" class="text-sm text-gray-600">{{ material.description }}</p>
                            <MaterialViewer :material="material" />
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
