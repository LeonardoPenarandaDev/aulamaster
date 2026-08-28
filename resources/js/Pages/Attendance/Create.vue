<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    classSession: Object,
    roster: Array,
    existing: Object,
});

const page = usePage();

const form = useForm({
    records: props.roster
        .filter((entry) => !(entry.enrollment_id in props.existing))
        .map((entry) => ({ enrollment_id: entry.enrollment_id, status: 'presente' })),
});

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

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estudiante</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Asistencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="entry in roster" :key="entry.enrollment_id">
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
                    <Link :href="route('class-sessions.index')" class="text-sm text-gray-600 hover:text-gray-900">
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
