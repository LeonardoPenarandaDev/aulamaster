<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CalendarView from '@/Components/Calendar/CalendarView.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * Calendario por rol (parte 10 del plan de mejoras).
 */
const props = defineProps({
    sessions: Array,
    range: Object,
    initialDate: String,
    filters: Object,
    filterOptions: Object,
    canCreate: Boolean,
});

const page = usePage();
const loading = ref(false);
const selected = ref(null);
const filters = ref({
    level_id: props.filters.level_id ?? '',
    teacher_id: props.filters.teacher_id ?? '',
    classroom_id: props.filters.classroom_id ?? '',
});

function addDays(value, days) {
    const [year, month, day] = value.split('-').map(Number);
    const date = new Date(year, month - 1, day + days);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

/**
 * Carga las clases del rango que se va a ver, con un margen para no pedir
 * datos en cada clic de ◀ / ▶.
 */
function loadRange(range) {
    router.reload({
        only: ['sessions', 'range'],
        data: { from: addDays(range.from, -7), to: addDays(range.to, 35), ...activeFilters() },
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
}

function activeFilters() {
    return Object.fromEntries(Object.entries(filters.value).filter(([, value]) => value));
}

function applyFilters() {
    router.get(route('calendar.index'), { from: props.range.from, to: props.range.to, ...activeFilters() }, { preserveState: true, replace: true });
}

const meetingForm = useForm({ meeting_url: '' });

function select(session) {
    selected.value = session;
    meetingForm.meeting_url = session.meeting_url ?? '';
    meetingForm.clearErrors();
}

function saveMeetingUrl() {
    meetingForm.patch(route('class-sessions.meeting-url', selected.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = null;
            loadRange(props.range);
        },
    });
}

function longDate(value) {
    const [year, month, day] = value.split('-').map(Number);
    return new Intl.DateTimeFormat('es-CO', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date(year, month - 1, day));
}

const statusLabels = {
    programada: 'Programada',
    dictada: 'Dictada',
    cancelada: 'Cancelada',
    reprogramada: 'Reprogramada',
};

const selectClasses = 'mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
</script>

<template>
    <Head title="Calendario" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Calendario</h2>
                <div v-if="canCreate" class="flex items-center gap-3">
                    <Link :href="route('class-sessions.index')" class="text-sm text-gray-600 hover:text-gray-900">Ver como lista</Link>
                    <Link :href="route('class-sessions.import.create')" class="text-sm text-gray-600 hover:text-gray-900">Importar horarios</Link>
                    <Link :href="route('class-sessions.create')">
                        <PrimaryButton>Nueva clase</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-10">
            <div class="mx-auto max-w-screen-2xl space-y-4 px-2 sm:px-6 lg:px-8">
                <div v-if="page.props.flash?.success" class="rounded-xl bg-green-50 p-4 text-sm text-green-700">
                    {{ page.props.flash.success }}
                </div>

                <div v-if="filterOptions" class="flex flex-wrap gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Nivel</label>
                        <select v-model="filters.level_id" :class="selectClasses" @change="applyFilters">
                            <option value="">Todos</option>
                            <option v-for="level in filterOptions.levels" :key="level.id" :value="level.id">{{ level.course?.name }} {{ level.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Docente</label>
                        <select v-model="filters.teacher_id" :class="selectClasses" @change="applyFilters">
                            <option value="">Todos</option>
                            <option v-for="teacher in filterOptions.teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Aula</label>
                        <select v-model="filters.classroom_id" :class="selectClasses" @change="applyFilters">
                            <option value="">Todas</option>
                            <option v-for="classroom in filterOptions.classrooms" :key="classroom.id" :value="classroom.id">{{ classroom.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="relative">
                    <div v-if="loading" class="absolute right-4 top-4 z-30 rounded-full bg-white px-3 py-1 text-xs text-gray-500 shadow">Cargando…</div>
                    <CalendarView :sessions="sessions" :loaded-range="range" :initial-date="initialDate" @range="loadRange" @select="select" />
                </div>
            </div>
        </div>

        <Modal :show="selected !== null" max-width="md" @close="selected = null">
            <div v-if="selected" class="space-y-4 p-6">
                <div class="flex items-start gap-3">
                    <span class="mt-1 h-4 w-4 shrink-0 rounded" :style="{ backgroundColor: selected.color }" />
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ selected.title }}</h3>
                        <p class="text-sm capitalize text-gray-600">{{ longDate(selected.date) }} · {{ selected.start }}–{{ selected.end }}</p>
                    </div>
                </div>

                <dl class="space-y-1 text-sm text-gray-700">
                    <div><dt class="inline text-gray-500">Lugar: </dt><dd class="inline">{{ selected.modality === 'virtual' ? 'Clase virtual' : selected.classroom }}</dd></div>
                    <div v-if="selected.teacher">
                        <dt class="inline text-gray-500">Docente: </dt>
                        <dd class="inline" :class="{ 'font-medium text-amber-700': !selected.has_teacher }">{{ selected.teacher }}</dd>
                    </div>
                    <div><dt class="inline text-gray-500">Estado: </dt><dd class="inline">{{ statusLabels[selected.status] ?? selected.status }}</dd></div>
                    <div v-if="selected.notes"><dt class="inline text-gray-500">Notas: </dt><dd class="inline">{{ selected.notes }}</dd></div>
                </dl>

                <form v-if="selected.can.meeting_url" class="space-y-2" @submit.prevent="saveMeetingUrl">
                    <label class="block text-xs font-medium text-gray-600">Enlace de la clase virtual</label>
                    <div class="flex gap-2">
                        <TextInput v-model="meetingForm.meeting_url" type="url" class="block w-full" placeholder="https://meet.google.com/..." />
                        <PrimaryButton :disabled="meetingForm.processing">Guardar</PrimaryButton>
                    </div>
                    <p v-if="meetingForm.errors.meeting_url" class="text-sm text-red-600">{{ meetingForm.errors.meeting_url }}</p>
                </form>

                <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                    <a v-if="selected.can.join" :href="selected.meeting_url" target="_blank" rel="noopener">
                        <PrimaryButton type="button">Unirse a la clase</PrimaryButton>
                    </a>
                    <Link v-if="selected.can.student_materials" :href="route('student-materials.index')" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Ver material
                    </Link>
                    <Link v-if="selected.can.take_attendance" :href="route('attendance.create', selected.id)">
                        <PrimaryButton type="button">Tomar asistencia</PrimaryButton>
                    </Link>
                    <Link v-if="selected.can.materials" :href="route('class-materials.index', selected.id)" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Material ({{ selected.materials_count }})
                    </Link>
                    <Link v-if="selected.can.edit" :href="route('class-sessions.edit', { class_session: selected.id, return_to: 'calendar' })" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Editar o cancelar
                    </Link>
                </div>
                <p v-if="selected.can.edit && !selected.has_teacher" class="text-xs text-amber-700">Esta clase no tiene docente: no se puede tomar asistencia hasta asignarle uno.</p>
            </div>
        </Modal>
    </AppLayout>
</template>
