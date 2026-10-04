<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    classSession: Object,
    roster: Array,
    existing: Object,
});

const page = usePage();

// Todos inician como ausentes: el profesor marca presentes a quienes
// asistieron ingresando su código al final de la clase.
const isAdmin = page.props.auth?.roles?.includes('admin');

// El docente no toma asistencia a quien está en mora (parte 8 del plan de mejoras).
const isLocked = (entry) => entry.is_blocked && !isAdmin;

const form = useForm({
    records: props.roster
        .filter((entry) => !(entry.enrollment_id in props.existing) && !isLocked(entry))
        .map((entry) => ({ enrollment_id: entry.enrollment_id, status: 'ausente' })),
});

const code = ref('');
const codeFeedback = ref(null);
const lastMarkedId = ref(null);

const presentCount = computed(() => form.records.filter((r) => r.status === 'presente').length);

const isTeacher = computed(() => page.props.auth?.roles?.includes('profesor'));

function markPresentByCode() {
    const typed = code.value.trim().toLowerCase();
    if (!typed) {
        return;
    }

    const entry = props.roster.find((e) => String(e.student.code).toLowerCase() === typed);

    if (!entry) {
        codeFeedback.value = { type: 'error', message: `No hay ningún estudiante con el código "${code.value.trim()}" en este nivel.` };
    } else if (isLocked(entry)) {
        codeFeedback.value = { type: 'error', message: `${entry.student.name} tiene pagos vencidos: no se le puede tomar asistencia.` };
    } else if (entry.enrollment_id in props.existing) {
        codeFeedback.value = { type: 'error', message: `${entry.student.name} ya tiene la asistencia registrada.` };
    } else if (statusFor(entry.enrollment_id) === 'presente') {
        codeFeedback.value = { type: 'info', message: `${entry.student.name} ya estaba marcado como presente.` };
    } else {
        setStatus(entry.enrollment_id, 'presente');
        lastMarkedId.value = entry.enrollment_id;
        codeFeedback.value = { type: 'success', message: `${entry.student.name} marcado como presente.` };
    }

    code.value = '';
}

function statusFor(enrollmentId) {
    const record = form.records.find((r) => r.enrollment_id === enrollmentId);

    return record?.status;
}

function setStatus(enrollmentId, status) {
    const record = form.records.find((r) => r.enrollment_id === enrollmentId);
    if (record) {
        record.status = status;
    }
}

const statusOptions = [
    { value: 'presente', label: 'Presente', active: 'bg-green-600 text-white' },
    { value: 'ausente', label: 'Ausente', active: 'bg-red-600 text-white' },
    { value: 'excusado', label: 'Excusado', active: 'bg-yellow-500 text-white' },
];

function submit() {
    form.post(route('attendance.store', props.classSession.id));
}
</script>

<template>
    <Head title="Tomar asistencia" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Tomar asistencia
            </h2>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="mb-4 rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="mb-6 rounded-2xl border border-gray-200/80 bg-white p-6">
                    <p class="text-sm text-gray-600">
                        {{ classSession.level?.course?.name }} {{ classSession.level?.name }}
                        — {{ classSession.date }}, {{ classSession.start_time?.slice(0, 5) }} a {{ classSession.end_time?.slice(0, 5) }}
                    </p>
                    <p class="text-sm text-gray-600">
                        Profesor: {{ classSession.teacher?.name }} · Aula: {{ classSession.classroom?.name }}
                    </p>
                </div>

                <div v-if="form.records.length > 0" class="mb-6 rounded-2xl border border-gray-200/80 bg-white p-6">
                    <form @submit.prevent="markPresentByCode">
                        <label for="student_code" class="block text-sm font-medium text-gray-700">
                            Código del estudiante que asistió
                        </label>
                        <div class="mt-1 flex gap-2">
                            <TextInput
                                id="student_code"
                                v-model="code"
                                class="block w-full"
                                placeholder="Escribe o escanea el código y presiona Enter"
                                autocomplete="off"
                                autofocus
                            />
                            <PrimaryButton type="submit">Marcar</PrimaryButton>
                        </div>
                    </form>
                    <p
                        v-if="codeFeedback"
                        class="mt-2 text-sm"
                        :class="{
                            'text-green-700': codeFeedback.type === 'success',
                            'text-red-600': codeFeedback.type === 'error',
                            'text-gray-600': codeFeedback.type === 'info',
                        }"
                    >
                        {{ codeFeedback.message }}
                    </p>
                    <p class="mt-3 text-sm text-gray-600">
                        Presentes: <span class="font-semibold text-gray-900">{{ presentCount }}</span> de {{ form.records.length }}.
                        Quienes no marques quedarán como ausentes; puedes ajustar cualquier estado en la lista.
                    </p>
                </div>

                <!-- Lista cómoda en el celular: botones grandes por estudiante (parte 9 del plan de mejoras). -->
                <ul class="divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-200/80 bg-white">
                    <li
                        v-for="entry in roster"
                        :key="entry.enrollment_id"
                        class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                        :class="{ 'bg-green-50': entry.enrollment_id === lastMarkedId }"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <StudentAvatar :name="entry.student.name" :photo-url="entry.student.photo_url" size="lg" />
                            <div class="min-w-0">
                            <p class="font-medium text-gray-900">
                                {{ entry.student.name }}
                                <span v-if="entry.is_blocked && isAdmin" class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">En mora</span>
                            </p>
                            <p class="text-xs text-gray-500">Código {{ entry.student.code }}</p>
                            </div>
                        </div>

                        <span v-if="entry.enrollment_id in existing" class="text-sm text-gray-500">
                            Ya registrada: {{ existing[entry.enrollment_id] }}
                        </span>
                        <span v-else-if="isLocked(entry)" class="rounded-xl bg-red-50 px-3 py-2 text-sm font-medium text-red-700">
                            Pagos vencidos: no se registra asistencia
                        </span>
                        <div v-else class="grid grid-cols-3 gap-2 sm:w-80">
                            <button
                                v-for="option in statusOptions"
                                :key="option.value"
                                type="button"
                                class="rounded-xl px-3 py-3 text-sm font-semibold transition"
                                :class="statusFor(entry.enrollment_id) === option.value ? option.active : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                @click="setStatus(entry.enrollment_id, option.value)"
                            >
                                {{ option.label }}
                            </button>
                        </div>
                    </li>
                    <li v-if="roster.length === 0" class="p-6 text-center text-sm text-gray-500">
                        No hay estudiantes matriculados activamente en este nivel.
                    </li>
                </ul>

                <div v-if="form.errors.records" class="mt-4 rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-700">
                    {{ form.errors.records }}
                </div>

                <div class="mt-6 flex items-center gap-4">
                    <PrimaryButton :disabled="form.processing || form.records.length === 0" @click="submit">
                        Guardar asistencia
                    </PrimaryButton>
                    <Link :href="route('class-materials.index', classSession.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        Material de la clase
                    </Link>
                    <Link v-if="isTeacher" :href="route('dashboard')" class="text-sm text-gray-600 hover:text-gray-900">
                        Volver al panel
                    </Link>
                    <Link v-else :href="route('class-sessions.index')" class="text-sm text-gray-600 hover:text-gray-900">
                        Volver al calendario
                    </Link>
                </div>
                <p class="mt-2 text-xs text-gray-500">
                    Una vez guardada, la asistencia no se puede editar directamente. Cualquier ajuste requiere una corrección auditada desde "Asistencias".
                </p>
            </div>
        </div>
    </AppLayout>
</template>
