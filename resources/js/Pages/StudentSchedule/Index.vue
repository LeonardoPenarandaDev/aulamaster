<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    enrollment: { type: Object, default: null },
    current_week: { type: Object, default: null },
    catch_up: { type: Object, default: null },
    sessions: { type: Array, default: () => [] },
});

const attendanceLabels = { presente: 'Asististe', ausente: 'Ausente', excusado: 'Excusado' };
const attendanceClasses = {
    presente: 'bg-emerald-100 text-emerald-800',
    ausente: 'bg-rose-100 text-rose-800',
    excusado: 'bg-amber-100 text-amber-800',
};

const shifts = [
    { key: 'manana', label: 'Jornada de la mañana', icon: '🌞', matches: (time) => time < '12:00' },
    { key: 'tarde', label: 'Jornada de la tarde', icon: '⛅', matches: (time) => time >= '12:00' && time < '18:00' },
    { key: 'noche', label: 'Jornada de la noche', icon: '🌙', matches: (time) => time >= '18:00' },
];

/**
 * Semanas → jornadas → días → clases, en el mismo orden en que la
 * institución publica los horarios por WhatsApp.
 */
const weeks = computed(() => {
    const groups = [];

    for (const session of props.sessions) {
        let week = groups.find((w) => w.start === session.week_start);
        if (!week) {
            week = { start: session.week_start, lastDate: session.date, shifts: shifts.map((s) => ({ ...s, days: [] })) };
            groups.push(week);
        }
        week.lastDate = session.date > week.lastDate ? session.date : week.lastDate;

        const shift = week.shifts.find((s) => s.matches(session.start_time));
        let day = shift.days.find((d) => d.date === session.date);
        if (!day) {
            day = { date: session.date, sessions: [] };
            shift.days.push(day);
        }

        day.sessions.push(session);
    }

    for (const week of groups) {
        week.shifts = week.shifts.filter((s) => s.days.length > 0);
    }

    return groups;
});

const currentWeekStart = computed(() => props.current_week?.week_start);

function parse(date) {
    return new Date(`${date}T00:00:00`);
}

function weekdayName(date) {
    return new Intl.DateTimeFormat('es-CO', { weekday: 'long' }).format(parse(date));
}

function dayNumber(date) {
    return new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'short' }).format(parse(date));
}

/**
 * Semana de lunes a sábado (o hasta el domingo si hay clases ese día).
 */
function weekLabel(week) {
    const end = parse(week.start);
    end.setDate(end.getDate() + (parse(week.lastDate).getDay() === 0 ? 6 : 5));
    const format = new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'long' });

    return `Semana del ${format.format(parse(week.start))} al ${format.format(end)}`;
}

function time12(time) {
    const [hour, minute] = time.split(':').map(Number);
    const suffix = hour >= 12 ? 'PM' : 'AM';

    return `${hour % 12 || 12}:${String(minute).padStart(2, '0')} ${suffix}`;
}

function hours(value) {
    return Number(value).toLocaleString('es-CO', { maximumFractionDigits: 1 });
}
</script>

<template>
    <Head title="Horarios disponibles" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Horarios disponibles
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <div v-if="!enrollment" class="rounded-xl border border-gray-100 bg-white p-4 text-sm text-gray-600 shadow-sm">
                    Todavía no tienes una matrícula activa.
                </div>

                <template v-else>
                    <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
                        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                            <p class="text-gray-500">Vistas esta semana</p>
                            <p class="text-xl font-bold tabular-nums text-gray-900">
                                {{ hours(current_week?.attended_hours ?? 0) }} / {{ hours(enrollment.weekly_hours) }} h
                            </p>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                            <p class="text-gray-500">Por recuperar (semanas anteriores)</p>
                            <p class="text-xl font-bold tabular-nums" :class="catch_up.backlog_hours > 0 ? 'text-amber-700' : 'text-gray-900'">
                                {{ hours(catch_up.backlog_hours) }} h
                            </p>
                        </div>
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4 shadow-sm">
                            <p class="text-indigo-700">Te faltan esta semana para estar al día</p>
                            <p class="text-xl font-bold tabular-nums text-indigo-900">{{ hours(catch_up.needed_this_week) }} h</p>
                        </div>
                    </div>

                    <div v-if="sessions.length === 0" class="rounded-xl border border-gray-100 bg-white p-4 text-sm text-gray-600 shadow-sm">
                        No hay clases programadas para tu nivel en estas dos semanas.
                    </div>

                    <section v-for="week in weeks" :key="week.start" class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                        <header class="border-b border-gray-100 bg-indigo-600 px-5 py-4 text-white">
                            <p class="text-xs font-medium uppercase tracking-wider text-indigo-100">
                                {{ week.start === currentWeekStart ? 'Esta semana' : 'Próxima semana' }}
                            </p>
                            <h3 class="mt-0.5 text-base font-bold uppercase">
                                🕐 Horarios {{ enrollment.course }} {{ enrollment.level }}
                            </h3>
                            <p class="text-sm font-medium">{{ weekLabel(week) }} 📚</p>
                        </header>

                        <div v-for="shift in week.shifts" :key="shift.key" class="px-5 py-4">
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-700">
                                {{ shift.label }} {{ shift.icon }}
                            </h4>

                            <ul class="mt-3 space-y-2.5">
                                <li v-for="day in shift.days" :key="day.date" class="flex flex-col gap-1 sm:flex-row sm:gap-3">
                                    <p class="w-32 shrink-0 text-sm">
                                        <span class="font-bold uppercase text-gray-900">{{ weekdayName(day.date) }}</span>
                                        <span class="ml-1 text-xs text-gray-400">{{ dayNumber(day.date) }}</span>
                                    </p>
                                    <div class="flex-1 space-y-1.5">
                                        <div
                                            v-for="session in day.sessions"
                                            :key="session.id"
                                            class="text-sm"
                                            :class="{ 'opacity-50': session.status === 'cancelada' }"
                                        >
                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                <span class="tabular-nums text-gray-900" :class="{ 'line-through': session.status === 'cancelada' }">
                                                    {{ time12(session.start_time) }} a {{ time12(session.end_time) }}
                                                </span>
                                                <span
                                                    v-if="session.notes"
                                                    class="rounded bg-amber-100 px-1.5 py-0.5 text-xs font-bold uppercase text-amber-900"
                                                >
                                                    {{ session.notes }} 📝
                                                </span>
                                                <span v-if="session.status === 'cancelada'" class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-700">
                                                    Cancelada
                                                </span>
                                                <span
                                                    v-if="session.attendance_status"
                                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                                    :class="attendanceClasses[session.attendance_status]"
                                                >
                                                    {{ attendanceLabels[session.attendance_status] }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-500">
                                                {{ hours(session.hours) }} h ·
                                                <template v-if="session.modality === 'virtual'">
                                                    <span class="font-medium text-violet-700">Virtual 💻</span>
                                                    <a
                                                        v-if="session.meeting_url && session.status !== 'cancelada'"
                                                        :href="session.meeting_url"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="ml-2 inline-block rounded-md bg-indigo-600 px-2 py-0.5 text-xs font-medium text-white hover:bg-indigo-700"
                                                    >
                                                        Unirse a la clase
                                                    </a>
                                                    <span v-else-if="session.status !== 'cancelada'"> · el enlace se publica el día de la clase</span>
                                                </template>
                                                <template v-else>{{ session.classroom }}</template>
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <footer class="border-t border-gray-100 bg-gray-50 px-5 py-3 text-sm text-gray-600">
                            Recuerda programar tus clases acorde a tu intensidad horaria
                            (<span class="font-medium text-gray-900">{{ hours(enrollment.weekly_hours) }} h semanales</span>) 📝.
                            Puedes asistir a cualquier horario de tu nivel; al final de la clase dale tu código al profesor.
                        </footer>
                    </section>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
