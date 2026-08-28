<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StatCard from '@/Components/StatCard.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: { type: Object, default: null },
    teacherData: { type: Object, default: null },
    studentData: { type: Object, default: null },
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

const accountStatusLabels = { al_dia: 'Al día', pendiente: 'Pendiente', vencido: 'Vencido' };
const accountStatusClasses = {
    al_dia: 'bg-emerald-100 text-emerald-800',
    pendiente: 'bg-amber-100 text-amber-800',
    vencido: 'bg-rose-100 text-rose-800',
};

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
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
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

                <!-- Dashboard del profesor -->
                <template v-else-if="teacherData">
                    <div class="space-y-6">
                        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                            <div class="flex items-center gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                                    <Icon name="book" class="h-6 w-6" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-gray-500">Mis cursos</p>
                                    <p class="mt-0.5 text-sm font-medium text-gray-900">
                                        <span v-if="teacherData.my_levels.length === 0" class="font-normal text-gray-500">Sin clases asignadas todavía.</span>
                                        <span v-else>{{ teacherData.my_levels.join(', ') }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
                                <Icon name="calendar" class="h-4 w-4" />
                                Clases de hoy
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="session in teacherData.today_sessions"
                                    :key="session.id"
                                    class="flex flex-wrap items-center justify-between gap-4 overflow-hidden rounded-xl border border-gray-100 bg-white p-4 shadow-sm"
                                >
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                            <Icon name="clock" class="h-6 w-6" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ session.level }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ session.start_time }} - {{ session.end_time }} · {{ session.classroom }} ·
                                                {{ session.enrolled_count }} estudiantes
                                            </p>
                                            <p v-if="session.attendance_taken" class="mt-1 flex flex-wrap gap-2 text-xs">
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-800">{{ session.present_count }} presentes</span>
                                                <span class="rounded-full bg-rose-100 px-2 py-0.5 font-medium text-rose-800">{{ session.absent_count }} ausentes</span>
                                            </p>
                                        </div>
                                    </div>
                                    <Link :href="route('attendance.create', session.id)">
                                        <PrimaryButton>
                                            {{ session.attendance_taken ? 'Ver / completar asistencia' : 'Tomar asistencia' }}
                                        </PrimaryButton>
                                    </Link>
                                </div>
                                <div v-if="teacherData.today_sessions.length === 0" class="rounded-xl border border-gray-100 bg-white p-4 text-sm text-gray-500 shadow-sm">
                                    No tienes clases programadas para hoy.
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Dashboard del estudiante -->
                <template v-else-if="studentData">
                    <div class="space-y-6">
                        <div v-if="!studentData.has_enrollment" class="rounded-xl border border-gray-100 bg-white p-4 text-sm text-gray-600 shadow-sm">
                            Todavía no tienes una matrícula activa.
                        </div>

                        <div v-if="studentData.has_enrollment" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <StatCard
                                label="Curso"
                                :value="`${studentData.enrollment.course} ${studentData.enrollment.level}`"
                                icon="book"
                                color="violet"
                            />
                            <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                        <Icon name="clock" class="h-6 w-6" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-medium text-gray-500">Horas</p>
                                        <p class="mt-0.5 text-2xl font-bold tabular-nums text-gray-900">
                                            {{ studentData.enrollment.accumulated_hours }} / {{ studentData.enrollment.required_hours }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                                        <div
                                            class="h-full rounded-full bg-indigo-500"
                                            :style="{ width: `${Math.min(studentData.enrollment.progress_percentage, 100)}%` }"
                                        />
                                    </div>
                                    <p class="mt-1 text-xs text-gray-400">{{ studentData.enrollment.progress_percentage }}% completado</p>
                                </div>
                            </div>
                            <StatCard
                                label="Evaluaciones"
                                :value="`${studentData.evaluations.presented} / ${studentData.evaluations.total}`"
                                icon="check-circle"
                                color="emerald"
                            />
                        </div>

                        <div v-if="studentData.has_enrollment" class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                            <div class="flex items-center gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                                    <Icon name="calendar" class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Próxima clase</p>
                                    <template v-if="studentData.next_session">
                                        <p class="mt-0.5 text-sm font-medium text-gray-900">
                                            {{ studentData.next_session.date }}, {{ studentData.next_session.start_time }} -
                                            {{ studentData.next_session.end_time }} · {{ studentData.next_session.classroom }}
                                        </p>
                                        <p class="text-xs text-gray-500">Profesor: {{ studentData.next_session.teacher }}</p>
                                    </template>
                                    <p v-else class="mt-0.5 text-sm text-gray-500">No hay clases programadas próximamente.</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                            <div class="flex items-center gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                    <Icon name="wallet" class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Estado de cuenta</p>
                                    <span class="mt-0.5 inline-block rounded-full px-2 py-0.5 text-xs font-medium" :class="accountStatusClasses[studentData.account.status]">
                                        {{ accountStatusLabels[studentData.account.status] }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-3 gap-4 border-t border-gray-100 pt-4 text-sm">
                                <div>
                                    <p class="text-xs text-gray-400">Facturado</p>
                                    <p class="font-semibold tabular-nums text-gray-900">${{ money(studentData.account.total_billed) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Pagado</p>
                                    <p class="font-semibold tabular-nums text-gray-900">${{ money(studentData.account.total_paid) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Saldo</p>
                                    <p class="font-semibold tabular-nums text-gray-900">${{ money(studentData.account.balance) }}</p>
                                </div>
                            </div>

                            <div v-if="studentData.pending_payments?.length" class="mt-4 space-y-2 border-t border-gray-100 pt-4">
                                <div
                                    v-for="payment in studentData.pending_payments"
                                    :key="payment.id"
                                    class="flex items-center justify-between gap-3 rounded-lg bg-amber-50 px-3 py-2 text-sm"
                                >
                                    <div>
                                        <p class="font-medium text-gray-800">{{ payment.concept }}</p>
                                        <p class="text-xs text-gray-500">${{ money(payment.final_amount) }}</p>
                                    </div>
                                    <a
                                        :href="route('payments.pay-online', payment.id)"
                                        class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                                    >
                                        Pagar en línea
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-else class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                    <div class="p-6 text-gray-900">
                        You're logged in! Tu perfil todavía no está vinculado a un registro de estudiante o profesor, así que no
                        hay un dashboard específico para mostrar. Pide al administrador que te asocie desde su panel.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
