<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

/**
 * Calendario propio, sin dependencias (parte 10 del plan de mejoras):
 * vistas semana, día, mes y agenda; botones Hoy / ◀ / ▶; línea de la hora
 * actual; color de cada nivel; clases que se cruzan, lado a lado.
 *
 * Emite "range" cuando la vista necesita clases fuera del rango cargado y
 * "select" al tocar una clase.
 */
const props = defineProps({
    sessions: { type: Array, default: () => [] },
    loadedRange: { type: Object, required: true },
    initialDate: { type: String, required: true },
});

const emit = defineEmits(['range', 'select']);

const HOUR_HEIGHT = 56;
const VIEWS = [
    { value: 'week', label: 'Semana' },
    { value: 'day', label: 'Día' },
    { value: 'month', label: 'Mes' },
    { value: 'agenda', label: 'Agenda' },
];

/* ---------- fechas (siempre como "AAAA-MM-DD" en hora local) ---------- */
function parseDate(value) {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
}

function formatDate(date) {
    const pad = (number) => String(number).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function addDays(value, days) {
    const date = parseDate(value);
    date.setDate(date.getDate() + days);
    return formatDate(date);
}

function startOfWeek(value) {
    const date = parseDate(value);
    const weekday = (date.getDay() + 6) % 7;
    date.setDate(date.getDate() - weekday);
    return formatDate(date);
}

function minutes(time) {
    const [hours, mins] = time.split(':').map(Number);
    return hours * 60 + mins;
}

const today = ref(formatDate(new Date()));
const isMobile = typeof window !== 'undefined' && window.matchMedia('(max-width: 639px)').matches;
const view = ref(isMobile ? 'day' : 'week');
const cursor = ref(props.initialDate);

/* ---------- rango visible ---------- */
const visibleRange = computed(() => {
    if (view.value === 'day') {
        return { from: cursor.value, to: cursor.value };
    }
    if (view.value === 'week') {
        const from = startOfWeek(cursor.value);
        return { from, to: addDays(from, 6) };
    }
    if (view.value === 'agenda') {
        return { from: cursor.value, to: addDays(cursor.value, 13) };
    }

    const first = parseDate(cursor.value);
    first.setDate(1);
    const from = startOfWeek(formatDate(first));
    return { from, to: addDays(from, 41) };
});

watch(visibleRange, (range) => {
    if (range.from < props.loadedRange.from || range.to > props.loadedRange.to) {
        emit('range', range);
    }
}, { immediate: true });

const title = computed(() => {
    const formatter = (options) => new Intl.DateTimeFormat('es-CO', options);

    if (view.value === 'day') {
        return formatter({ weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(parseDate(cursor.value));
    }
    if (view.value === 'month') {
        return formatter({ month: 'long', year: 'numeric' }).format(parseDate(cursor.value));
    }

    const { from, to } = visibleRange.value;
    return `${formatter({ day: 'numeric', month: 'short' }).format(parseDate(from))} – ${formatter({ day: 'numeric', month: 'short', year: 'numeric' }).format(parseDate(to))}`;
});

function move(direction) {
    if (view.value === 'month') {
        const date = parseDate(cursor.value);
        date.setDate(1);
        date.setMonth(date.getMonth() + direction);
        cursor.value = formatDate(date);
        return;
    }

    cursor.value = addDays(cursor.value, direction * ({ day: 1, week: 7, agenda: 14 }[view.value]));
}

function goToday() {
    cursor.value = today.value;
}

function openDay(date) {
    cursor.value = date;
    view.value = 'day';
}

/* ---------- clases por día ---------- */
const sessionsByDate = computed(() => {
    const map = {};
    for (const session of props.sessions) {
        (map[session.date] ??= []).push(session);
    }
    Object.values(map).forEach((list) => list.sort((a, b) => a.start.localeCompare(b.start)));
    return map;
});

const columns = computed(() => {
    if (view.value === 'day') {
        return [cursor.value];
    }
    const from = visibleRange.value.from;
    return Array.from({ length: 7 }, (_, index) => addDays(from, index));
});

const hourRange = computed(() => {
    let first = 7;
    let last = 21;
    for (const date of columns.value) {
        for (const session of sessionsByDate.value[date] ?? []) {
            first = Math.min(first, Math.floor(minutes(session.start) / 60));
            last = Math.max(last, Math.ceil(minutes(session.end) / 60));
        }
    }
    return { first, last };
});

const hours = computed(() => Array.from({ length: hourRange.value.last - hourRange.value.first }, (_, index) => hourRange.value.first + index));

/**
 * Las clases que se cruzan se reparten en columnas, lado a lado.
 */
function layoutDay(date) {
    const list = sessionsByDate.value[date] ?? [];
    const placed = [];
    let group = [];
    let groupEnd = -1;

    const flush = () => {
        const lanes = [];
        for (const item of group) {
            let lane = lanes.findIndex((laneEnd) => laneEnd <= item.startMin);
            if (lane === -1) {
                lane = lanes.length;
                lanes.push(item.endMin);
            } else {
                lanes[lane] = item.endMin;
            }
            item.lane = lane;
        }
        group.forEach((item) => placed.push({ ...item, lanes: lanes.length }));
        group = [];
    };

    for (const session of list) {
        const item = { session, startMin: minutes(session.start), endMin: minutes(session.end) };
        if (group.length && item.startMin >= groupEnd) {
            flush();
        }
        group.push(item);
        groupEnd = Math.max(groupEnd, item.endMin);
    }
    flush();

    const base = hourRange.value.first * 60;
    return placed.map((item) => ({
        session: item.session,
        style: {
            top: `${((item.startMin - base) / 60) * HOUR_HEIGHT}px`,
            height: `${Math.max(((item.endMin - item.startMin) / 60) * HOUR_HEIGHT - 2, 22)}px`,
            left: `calc(${(item.lane / item.lanes) * 100}% + 2px)`,
            width: `calc(${100 / item.lanes}% - 4px)`,
        },
    }));
}

/* ---------- línea de la hora actual ---------- */
const nowMinutes = ref(new Date().getHours() * 60 + new Date().getMinutes());
let timer = null;
onMounted(() => {
    timer = setInterval(() => {
        const now = new Date();
        nowMinutes.value = now.getHours() * 60 + now.getMinutes();
        today.value = formatDate(now);
    }, 60_000);
});
onBeforeUnmount(() => clearInterval(timer));

const nowLineTop = computed(() => {
    const base = hourRange.value.first * 60;
    if (nowMinutes.value < base || nowMinutes.value > hourRange.value.last * 60) {
        return null;
    }
    return `${((nowMinutes.value - base) / 60) * HOUR_HEIGHT}px`;
});

/* ---------- mes y agenda ---------- */
const monthDays = computed(() => Array.from({ length: 42 }, (_, index) => addDays(visibleRange.value.from, index)));
const currentMonth = computed(() => cursor.value.slice(0, 7));

const agendaDays = computed(() => Array.from({ length: 14 }, (_, index) => addDays(visibleRange.value.from, index))
    .filter((date) => (sessionsByDate.value[date] ?? []).length));

function dayHeader(date, options = { weekday: 'short', day: 'numeric' }) {
    return new Intl.DateTimeFormat('es-CO', options).format(parseDate(date));
}

const statusBadges = {
    asistio: { label: 'Asistió', class: 'bg-green-100 text-green-800' },
    falto: { label: 'Faltó', class: 'bg-red-100 text-red-800' },
    proxima: { label: 'Próxima', class: 'bg-indigo-100 text-indigo-800' },
    cancelada: { label: 'Cancelada', class: 'bg-gray-200 text-gray-600' },
};

function eventClasses(session) {
    return session.status === 'cancelada' ? 'opacity-50 line-through' : '';
}
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white">
        <!-- Barra de navegación -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-3 sm:p-4">
            <div class="flex items-center gap-2">
                <button type="button" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="goToday">Hoy</button>
                <button type="button" class="rounded-md px-2 py-1.5 text-gray-600 hover:bg-gray-100" aria-label="Anterior" @click="move(-1)">◀</button>
                <button type="button" class="rounded-md px-2 py-1.5 text-gray-600 hover:bg-gray-100" aria-label="Siguiente" @click="move(1)">▶</button>
                <h3 class="ml-1 text-base font-semibold capitalize text-gray-900 sm:text-lg">{{ title }}</h3>
            </div>
            <div class="flex rounded-lg border border-gray-200 p-0.5 text-sm">
                <button
                    v-for="option in VIEWS"
                    :key="option.value"
                    type="button"
                    class="rounded-md px-3 py-1"
                    :class="view === option.value ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50'"
                    @click="view = option.value"
                >
                    {{ option.label }}
                </button>
            </div>
        </div>

        <!-- Semana / día -->
        <div v-if="view === 'week' || view === 'day'" class="overflow-x-auto">
            <div :class="view === 'week' ? 'min-w-[760px]' : ''">
                <div class="grid border-b border-gray-100" :style="{ gridTemplateColumns: `56px repeat(${columns.length}, minmax(0, 1fr))` }">
                    <div />
                    <button
                        v-for="date in columns"
                        :key="date"
                        type="button"
                        class="py-2 text-center text-xs capitalize"
                        :class="date === today ? 'font-bold text-indigo-600' : 'text-gray-600'"
                        @click="openDay(date)"
                    >
                        {{ dayHeader(date) }}
                    </button>
                </div>

                <div class="relative grid" :style="{ gridTemplateColumns: `56px repeat(${columns.length}, minmax(0, 1fr))` }">
                    <div>
                        <div v-for="hour in hours" :key="hour" class="relative border-t border-gray-100 pr-2 text-right text-[11px] text-gray-400" :style="{ height: `${HOUR_HEIGHT}px` }">
                            <span class="-mt-2 block">{{ String(hour).padStart(2, '0') }}:00</span>
                        </div>
                    </div>

                    <div v-for="date in columns" :key="date" class="relative border-l border-gray-100" :class="{ 'bg-indigo-50/40': date === today }">
                        <div v-for="hour in hours" :key="hour" class="border-t border-gray-100" :style="{ height: `${HOUR_HEIGHT}px` }" />

                        <button
                            v-for="item in layoutDay(date)"
                            :key="item.session.id"
                            type="button"
                            class="on-level-color absolute overflow-hidden rounded-md border border-black/10 px-1.5 py-1 text-left text-[11px] leading-tight text-gray-900 shadow-sm hover:z-10 hover:ring-2 hover:ring-indigo-400"
                            :class="eventClasses(item.session)"
                            :style="{ ...item.style, backgroundColor: item.session.color }"
                            @click="emit('select', item.session)"
                        >
                            <span class="block font-semibold">{{ item.session.start }} {{ item.session.title }}</span>
                            <span class="block truncate text-gray-600">
                                {{ item.session.modality === 'virtual' ? 'Virtual' : item.session.classroom }}
                                <template v-if="item.session.teacher"> · {{ item.session.teacher }}</template>
                            </span>
                            <span v-if="statusBadges[item.session.student_status]" class="mt-0.5 inline-block rounded px-1 text-[10px] font-medium" :class="statusBadges[item.session.student_status].class">
                                {{ statusBadges[item.session.student_status].label }}
                            </span>
                        </button>

                        <div v-if="date === today && nowLineTop" class="pointer-events-none absolute inset-x-0 z-20 flex items-center" :style="{ top: nowLineTop }">
                            <span class="-ml-1 h-2.5 w-2.5 rounded-full bg-red-500" />
                            <span class="h-0.5 flex-1 bg-red-500" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mes -->
        <div v-else-if="view === 'month'" class="grid grid-cols-7 text-xs">
            <div v-for="day in ['lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom']" :key="day" class="border-b border-gray-100 py-2 text-center font-medium uppercase text-gray-500">{{ day }}</div>
            <div
                v-for="date in monthDays"
                :key="date"
                class="min-h-[96px] border-b border-l border-gray-100 p-1"
                :class="date.slice(0, 7) === currentMonth ? 'bg-white' : 'bg-gray-50 text-gray-400'"
            >
                <button type="button" class="mb-1 flex h-6 w-6 items-center justify-center rounded-full" :class="date === today ? 'bg-indigo-600 font-bold text-white' : 'hover:bg-gray-100'" @click="openDay(date)">
                    {{ Number(date.slice(8)) }}
                </button>
                <button
                    v-for="session in (sessionsByDate[date] ?? []).slice(0, 3)"
                    :key="session.id"
                    type="button"
                    class="on-level-color mb-0.5 block w-full truncate rounded px-1 py-0.5 text-left text-[11px] text-gray-900"
                    :class="eventClasses(session)"
                    :style="{ backgroundColor: session.color }"
                    @click="emit('select', session)"
                >
                    {{ session.start }} {{ session.title }}
                </button>
                <button v-if="(sessionsByDate[date] ?? []).length > 3" type="button" class="text-[11px] font-medium text-indigo-600" @click="openDay(date)">
                    +{{ sessionsByDate[date].length - 3 }} más
                </button>
            </div>
        </div>

        <!-- Agenda -->
        <div v-else class="divide-y divide-gray-100">
            <div v-for="date in agendaDays" :key="date" class="flex gap-4 p-4">
                <div class="w-16 shrink-0 text-center">
                    <p class="text-xs uppercase text-gray-500">{{ dayHeader(date, { weekday: 'short' }) }}</p>
                    <p class="text-2xl font-semibold" :class="date === today ? 'text-indigo-600' : 'text-gray-900'">{{ Number(date.slice(8)) }}</p>
                </div>
                <div class="flex-1 space-y-2">
                    <button
                        v-for="session in sessionsByDate[date]"
                        :key="session.id"
                        type="button"
                        class="flex w-full items-center gap-3 rounded-xl border border-gray-100 p-3 text-left hover:bg-gray-50"
                        :class="eventClasses(session)"
                        @click="emit('select', session)"
                    >
                        <span class="h-10 w-1.5 shrink-0 rounded-full" :style="{ backgroundColor: session.color }" />
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-medium text-gray-900">{{ session.title }}</span>
                            <span class="block text-xs text-gray-500">
                                {{ session.start }}–{{ session.end }} · {{ session.modality === 'virtual' ? 'Virtual' : session.classroom }}
                                <template v-if="session.teacher"> · {{ session.teacher }}</template>
                            </span>
                        </span>
                        <span v-if="statusBadges[session.student_status]" class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadges[session.student_status].class">
                            {{ statusBadges[session.student_status].label }}
                        </span>
                    </button>
                </div>
            </div>
            <p v-if="agendaDays.length === 0" class="p-6 text-center text-sm text-gray-500">No hay clases en estas dos semanas.</p>
        </div>
    </div>
</template>
