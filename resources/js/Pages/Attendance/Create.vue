<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
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
const form = useForm({
    records: props.roster
        .filter((entry) => !(entry.enrollment_id in props.existing))
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

function submit() {
    form.post(route('attendance.store', props.classSession.id));
}
</script>

<template>
    <Head title="Tomar asistencia" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Tomar asistencia
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-600">
                        {{ classSession.level?.course?.name }} {{ classSession.level?.name }}
                        — {{ classSession.date }}, {{ classSession.start_time?.slice(0, 5) }} a {{ classSession.end_time?.slice(0, 5) }}
                    </p>
                    <p class="text-sm text-gray-600">
                        Profesor: {{ classSession.teacher?.name }} · Aula: {{ classSession.classroom?.name }}
                    </p>
                </div>

                <div v-if="form.records.length > 0" class="mb-6 rounded-lg bg-white p-6 shadow-sm">
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

                <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estudiante</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Asistencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr
                                v-for="entry in roster"
                                :key="entry.enrollment_id"
                                :class="{ 'bg-green-50': entry.enrollment_id === lastMarkedId }"
                            >
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ entry.student.code }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ entry.student.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span v-if="entry.enrollment_id in existing" class="text-gray-500">
                                        Ya registrada: {{ existing[entry.enrollment_id] }}
                                    </span>
                                    <div v-else class="flex gap-2">
                                        <button
                                            type="button"
                                            class="rounded-md px-3 py-1 text-xs font-medium"
                                            :class="statusFor(entry.enrollment_id) === 'presente' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700'"
                                            @click="setStatus(entry.enrollment_id, 'presente')"
                                        >
                                            Presente
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md px-3 py-1 text-xs font-medium"
                                            :class="statusFor(entry.enrollment_id) === 'ausente' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700'"
                                            @click="setStatus(entry.enrollment_id, 'ausente')"
                                        >
                                            Ausente
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md px-3 py-1 text-xs font-medium"
                                            :class="statusFor(entry.enrollment_id) === 'excusado' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700'"
                                            @click="setStatus(entry.enrollment_id, 'excusado')"
                                        >
                                            Excusado
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="roster.length === 0">
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay estudiantes matriculados activamente en este nivel.
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
    </AuthenticatedLayout>
</template>
