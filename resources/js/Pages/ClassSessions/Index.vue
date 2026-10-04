<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    classSessions: Object,
    calendarSessions: Array,
    calendarMonth: String,
    calendarGridStart: String,
    calendarGridEnd: String,
    filters: Object,
    levels: Array,
    teachers: Array,
    classrooms: Array,
});

const page = usePage();
const view = ref('calendar');
const filters = ref({
    date: props.filters.date ?? '',
    classroom_id: props.filters.classroom_id ?? '',
    teacher_id: props.filters.teacher_id ?? '',
    level_id: props.filters.level_id ?? '',
});

function applyFilters(extra = {}) {
    router.get(route('class-sessions.index'), { ...filters.value, month: props.calendarMonth, ...extra }, {
        preserveState: true,
        replace: true,
    });
}

function changeMonth(delta) {
    const [year, month] = props.calendarMonth.split('-').map(Number);
    const next = new Date(year, month - 1 + delta, 1);
    const nextMonth = `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}`;
    applyFilters({ month: nextMonth });
}

const statusLabels = {
    programada: 'Programada',
    dictada: 'Dictada',
    cancelada: 'Cancelada',
    reprogramada: 'Reprogramada',
};

const statusClasses = {
    programada: 'bg-blue-100 text-blue-800',
    dictada: 'bg-green-100 text-green-800',
    cancelada: 'bg-red-100 text-red-800',
    reprogramada: 'bg-yellow-100 text-yellow-800',
};

const calendarPillClasses = {
    programada: 'bg-indigo-100 text-indigo-800',
    dictada: 'bg-emerald-100 text-emerald-800',
    cancelada: 'bg-rose-100 text-rose-800 line-through decoration-rose-400',
    reprogramada: 'bg-amber-100 text-amber-800',
};

const weekdayLabels = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

const monthLabel = computed(() => {
    const [year, month] = props.calendarMonth.split('-').map(Number);
    return new Intl.DateTimeFormat('es-CO', { month: 'long', year: 'numeric' }).format(new Date(year, month - 1, 1));
});

const gridDays = computed(() => {
    const days = [];
    const cursor = new Date(`${props.calendarGridStart}T00:00:00`);
    const end = new Date(`${props.calendarGridEnd}T00:00:00`);
    while (cursor <= end) {
        const iso = cursor.toISOString().slice(0, 10);
        days.push({
            iso,
            day: cursor.getDate(),
            inCurrentMonth: iso.slice(0, 7) === props.calendarMonth,
            isToday: iso === new Date().toISOString().slice(0, 10),
        });
        cursor.setDate(cursor.getDate() + 1);
    }
    return days;
});

const sessionsByDate = computed(() => {
    const map = {};
    for (const session of props.calendarSessions) {
        (map[session.date] ??= []).push(session);
    }
    return map;
});

function destroy(classSession) {
    if (confirm('¿Eliminar esta clase del calendario?')) {
        router.delete(route('class-sessions.destroy', classSession.id));
    }
}
</script>

<template>
    <Head title="Calendario académico" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Calendario académico
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="flex rounded-md border border-gray-200 bg-white p-1">
                            <button
                                type="button"
                                class="rounded px-3 py-1 text-sm font-medium"
                                :class="view === 'calendar' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:text-gray-900'"
                                @click="view = 'calendar'"
                            >
                                Calendario
                            </button>
                            <button
                                type="button"
                                class="rounded px-3 py-1 text-sm font-medium"
                                :class="view === 'list' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:text-gray-900'"
                                @click="view = 'list'"
                            >
                                Lista
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600">Aula</label>
                            <select v-model="filters.classroom_id" @change="applyFilters()" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todas</option>
                                <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Profesor</label>
                            <select v-model="filters.teacher_id" @change="applyFilters()" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Nivel</label>
                            <select v-model="filters.level_id" @change="applyFilters()" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                            </select>
                        </div>
                        <div v-if="view === 'list'">
                            <label class="block text-xs font-medium text-gray-600">Fecha</label>
                            <input type="date" v-model="filters.date" @change="applyFilters()" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <Link :href="route('class-schedules.index')">
                            <PrimaryButton class="bg-gray-700 hover:bg-gray-600">Horarios recurrentes</PrimaryButton>
                        </Link>
                        <Link :href="route('class-sessions.create')">
                            <PrimaryButton>Nueva clase</PrimaryButton>
                        </Link>
                    </div>
                </div>

                <!-- Vista de calendario -->
                <div v-if="view === 'calendar'" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white">
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                        <button type="button" class="rounded-md px-2 py-1 text-gray-500 hover:bg-gray-100" @click="changeMonth(-1)">‹ Anterior</button>
                        <h3 class="text-sm font-semibold capitalize text-gray-800">{{ monthLabel }}</h3>
                        <button type="button" class="rounded-md px-2 py-1 text-gray-500 hover:bg-gray-100" @click="changeMonth(1)">Siguiente ›</button>
                    </div>

                    <div class="grid grid-cols-7 border-b border-gray-100 bg-gray-50 text-center text-xs font-medium uppercase tracking-wide text-gray-500">
                        <div v-for="label in weekdayLabels" :key="label" class="py-2">{{ label }}</div>
                    </div>

                    <div class="grid grid-cols-7">
                        <div
                            v-for="cell in gridDays"
                            :key="cell.iso"
                            class="min-h-[110px] border-b border-r border-gray-100 p-1.5 last:border-r-0"
                            :class="!cell.inCurrentMonth ? 'bg-gray-50/60' : ''"
                        >
                            <div
                                class="mb-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-xs"
                                :class="[
                                    cell.isToday ? 'bg-indigo-600 font-semibold text-white' : 'text-gray-500',
                                    !cell.inCurrentMonth && !cell.isToday ? 'text-gray-300' : '',
                                ]"
                            >
                                {{ cell.day }}
                            </div>
                            <div class="space-y-1">
                                <Link
                                    v-for="session in (sessionsByDate[cell.iso] ?? [])"
                                    :key="session.id"
                                    :href="route('class-sessions.edit', session.id)"
                                    class="block truncate rounded px-1.5 py-0.5 text-[11px] leading-tight"
                                    :class="calendarPillClasses[session.status]"
                                    :title="`${session.start_time?.slice(0, 5)} · ${session.level?.course?.name} ${session.level?.name} · ${session.teacher?.name}`"
                                >
                                    {{ session.start_time?.slice(0, 5) }} {{ session.level?.name }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vista de lista -->
                <template v-else>
                    <div class="overflow-x-auto rounded-2xl border border-slate-200/80 bg-white">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Hora</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Curso / Nivel</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Profesor</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Aula</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="session in classSessions.data" :key="session.id" class="transition hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ session.date }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ session.start_time?.slice(0, 5) }} - {{ session.end_time?.slice(0, 5) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        {{ session.level?.course?.name }} {{ session.level?.name }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ session.teacher?.name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ session.classroom?.name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[session.status]">
                                            {{ statusLabels[session.status] }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <Link :href="route('attendance.create', session.id)" class="text-green-700 hover:text-green-900">
                                            Tomar asistencia
                                        </Link>
                                        <Link :href="route('class-sessions.edit', session.id)" class="ml-4 text-indigo-600 hover:text-indigo-900">
                                            Editar
                                        </Link>
                                        <button type="button" class="ml-4 text-red-600 hover:text-red-900" @click="destroy(session)">
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="classSessions.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No hay clases programadas con estos filtros.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="classSessions.links.length > 3" class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in classSessions.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            class="rounded-lg border px-3 py-1.5 text-sm"
                            :class="[
                                link.active ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                                !link.url ? 'pointer-events-none opacity-50' : '',
                            ]"
                        />
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
