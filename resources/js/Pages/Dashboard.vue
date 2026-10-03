<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import Icon from '@/Components/Icon.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Panel del personal administrativo. Alumnos y docentes tienen su propio
// inicio en el portal (Portal/StudentDashboard y Portal/TeacherDashboard).
defineProps({
    stats: { type: Object, default: null },
});

const userName = computed(() => usePage().props.auth.user.name);
const today = computed(() =>
    new Intl.DateTimeFormat('es-CO', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date()),
);

const adminCards = [
    { key: 'active_students', label: 'Estudiantes activos', icon: 'users', color: 'indigo' },
    { key: 'active_courses', label: 'Cursos activos', icon: 'book', color: 'violet' },
    { key: 'teachers', label: 'Profesores', icon: 'teacher', color: 'sky' },
    { key: 'classrooms', label: 'Aulas', icon: 'building', color: 'sky' },
    { key: 'classes_today', label: 'Clases del día', icon: 'calendar', color: 'indigo' },
    { key: 'attendances_today', label: 'Asistencias de hoy', icon: 'check-circle', color: 'emerald' },
    { key: 'pending_evaluations', label: 'Evaluaciones pendientes', icon: 'clipboard', color: 'amber' },
    { key: 'students_in_recovery', label: 'Estudiantes en recuperación', icon: 'refresh', color: 'amber' },
    { key: 'students_with_overdue_payments', label: 'Estudiantes con mora', icon: 'alert-triangle', color: 'rose' },
];

function money(value) {
    return Number(value).toLocaleString('es-CO');
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-screen-2xl space-y-6 sm:px-6 lg:px-8">
                <div class="rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-6 py-6 text-white shadow-sm sm:px-8">
                    <p class="text-sm font-medium text-indigo-100 capitalize">{{ today }}</p>
                    <h3 class="mt-1 text-2xl font-bold">Hola, {{ userName }} 👋</h3>
                </div>

                <!-- Dashboard administrativo -->
                <template v-if="stats">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm sm:col-span-2 lg:col-span-1">
                            <div class="flex items-center gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                    <Icon name="coins" class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-emerald-700">Ingresos del periodo</p>
                                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-gray-900">${{ money(stats.income_this_month) }}</p>
                                </div>
                            </div>
                        </div>
                        <StatCard
                            v-for="card in adminCards"
                            :key="card.key"
                            :label="card.label"
                            :value="stats[card.key]"
                            :icon="card.icon"
                            :color="card.color"
                        />
                    </div>
                </template>

                <div v-else class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                    <div class="p-6 text-gray-900">
        Tu perfil todavía no está vinculado a un registro de estudiante o profesor, así que no
                        hay un panel para mostrar. Pide al administrador que te asocie desde su panel.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
