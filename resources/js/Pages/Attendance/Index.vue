<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    attendances: Object,
    filters: Object,
    students: Array,
    levels: Array,
});

const page = usePage();
const filters = ref({
    student_id: props.filters.student_id ?? '',
    level_id: props.filters.level_id ?? '',
    date: props.filters.date ?? '',
});

function applyFilters() {
    router.get(route('attendance.index'), filters.value, {
        preserveState: true,
        replace: true,
    });
}

const statusLabels = { presente: 'Presente', ausente: 'Ausente', excusado: 'Excusado' };
const statusClasses = {
    presente: 'bg-green-100 text-green-800',
    ausente: 'bg-red-100 text-red-800',
    excusado: 'bg-yellow-100 text-yellow-800',
};

const correctingAttendance = ref(null);
const correctionForm = useForm({
    new_status: 'presente',
    reason: '',
});

function openCorrection(attendance) {
    correctingAttendance.value = attendance;
    correctionForm.reset();
    correctionForm.new_status = attendance.effective_status;
}

function closeCorrection() {
    correctingAttendance.value = null;
}

function submitCorrection() {
    correctionForm.post(route('attendance-corrections.store', correctingAttendance.value.id), {
        onSuccess: () => closeCorrection(),
    });
}
</script>

<template>
    <Head title="Asistencias" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Asistencias
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-screen-2xl space-y-4 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-md bg-green-50 p-4 text-sm text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="flex flex-wrap gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Fecha</label>
                        <input type="date" v-model="filters.date" @change="applyFilters" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Estudiante</label>
                        <select v-model="filters.student_id" @change="applyFilters" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option v-for="s in students" :key="s.id" :value="s.id">{{ s.code }} - {{ s.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Nivel</label>
                        <select v-model="filters.level_id" @change="applyFilters" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estudiante</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Curso / Nivel</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Profesor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado original</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado efectivo</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="attendance in attendances.data" :key="attendance.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ attendance.class_date }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ attendance.enrollment?.student?.code }} - {{ attendance.enrollment?.student?.name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ attendance.class_session?.level?.course?.name }} {{ attendance.class_session?.level?.name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ attendance.teacher?.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[attendance.status]">
                                        {{ statusLabels[attendance.status] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[attendance.effective_status]">
                                        {{ statusLabels[attendance.effective_status] }}
                                    </span>
                                    <span v-if="attendance.corrections?.length" class="ml-1 text-xs text-gray-400">
                                        ({{ attendance.corrections.length }} corrección{{ attendance.corrections.length > 1 ? 'es' : '' }})
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <button type="button" class="text-indigo-600 hover:text-indigo-900" @click="openCorrection(attendance)">
                                        Corregir
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="attendances.data.length === 0">
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay asistencias registradas con estos filtros.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="correctingAttendance !== null" @close="closeCorrection">
            <div class="p-6" v-if="correctingAttendance">
                <h3 class="text-lg font-medium text-gray-900">
                    Corregir asistencia — {{ correctingAttendance.enrollment?.student?.name }}
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    El registro original ({{ statusLabels[correctingAttendance.status] }}) se conserva. Esta acción crea un
                    evento de corrección auditado con el estado efectivo actualizado.
                </p>

                <form @submit.prevent="submitCorrection" class="mt-4 space-y-4">
                    <div>
                        <InputLabel for="new_status" value="Nuevo estado" />
                        <select id="new_status" v-model="correctionForm.new_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="presente">Presente</option>
                            <option value="ausente">Ausente</option>
                            <option value="excusado">Excusado</option>
                        </select>
                        <InputError class="mt-2" :message="correctionForm.errors.new_status" />
                    </div>

                    <div>
                        <InputLabel for="reason" value="Motivo de la corrección" />
                        <textarea
                            id="reason"
                            v-model="correctionForm.reason"
                            rows="3"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <InputError class="mt-2" :message="correctionForm.errors.reason" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <SecondaryButton type="button" @click="closeCorrection">Cancelar</SecondaryButton>
                        <PrimaryButton :disabled="correctionForm.processing">Guardar corrección</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
