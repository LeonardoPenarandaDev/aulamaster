<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineProps({
    classSchedules: Object,
});

const page = usePage();

const dayNames = { 1: 'Lun', 2: 'Mar', 3: 'Mié', 4: 'Jue', 5: 'Vie', 6: 'Sáb', 7: 'Dom' };

function destroy(schedule) {
    if (confirm('¿Eliminar este horario recurrente? Las clases ya generadas se conservan.')) {
        router.delete(route('class-schedules.destroy', schedule.id));
    }
}
</script>

<template>
    <Head title="Horarios recurrentes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Horarios recurrentes
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
                    <Link :href="route('class-sessions.index')" class="text-sm text-indigo-600 hover:text-indigo-900">
                        ← Volver al calendario
                    </Link>

                    <Link :href="route('class-schedules.create')">
                        <PrimaryButton>Nuevo horario recurrente</PrimaryButton>
                    </Link>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Curso / Nivel</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Profesor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Aula</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Días</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Hora</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Clases generadas</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="schedule in classSchedules.data" :key="schedule.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ schedule.level?.course?.name }} {{ schedule.level?.name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ schedule.teacher?.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ schedule.classroom?.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ schedule.days_of_week.map((d) => dayNames[d]).join(', ') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ schedule.start_time?.slice(0, 5) }} - {{ schedule.end_time?.slice(0, 5) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ schedule.class_sessions_count }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <button type="button" class="text-red-600 hover:text-red-900" @click="destroy(schedule)">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="classSchedules.data.length === 0">
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay horarios recurrentes configurados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
