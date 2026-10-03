<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EnrollmentContractsPanel from '@/Components/Contracts/EnrollmentContractsPanel.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    enrollment: Object,
    students: Array,
    levels: Array,
    contracts: Object,
});

const page = usePage();
const isAdmin = page.props.auth.roles?.includes('admin');

const canPromote = isAdmin
    && props.enrollment.status === 'aprobada'
    && props.enrollment.level?.next_level
    && !props.enrollment.next_enrollment;

function promote() {
    if (confirm(`¿Matricular al estudiante en ${props.enrollment.level.next_level.name} como pendiente?`)) {
        router.post(route('enrollments.promote', props.enrollment.id));
    }
}

const form = useForm({
    student_id: props.enrollment.student_id,
    level_id: props.enrollment.level_id,
    enrolled_at: props.enrollment.enrolled_at,
    start_date: props.enrollment.start_date,
    estimated_end_date: props.enrollment.estimated_end_date,
    actual_end_date: props.enrollment.actual_end_date,
    status: props.enrollment.status,
    required_hours: props.enrollment.required_hours,
    weekly_hours: props.enrollment.weekly_hours,
    base_price: props.enrollment.base_price,
    final_price: props.enrollment.final_price,
    monthly_fee: props.enrollment.monthly_fee ?? '',
});

function submit() {
    form.put(route('enrollments.update', props.enrollment.id));
}

const extensionForm = useForm({
    new_end_date: '',
    reason: '',
    notes: '',
});

function submitExtension() {
    extensionForm.post(route('enrollments.extensions.store', props.enrollment.id), {
        preserveScroll: true,
        onSuccess: () => extensionForm.reset(),
    });
}
</script>

<template>
    <Head title="Editar matrícula" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar matrícula
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="student_id" value="Estudiante" />
                                <select id="student_id" v-model="form.student_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.code }} - {{ student.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.student_id" />
                            </div>

                            <div>
                                <InputLabel for="level_id" value="Curso / Nivel" />
                                <select id="level_id" v-model="form.level_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="level in levels" :key="level.id" :value="level.id">{{ level.course?.name }} {{ level.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.level_id" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <InputLabel for="enrolled_at" value="Fecha de matrícula" />
                                <TextInput id="enrolled_at" type="date" v-model="form.enrolled_at" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.enrolled_at" />
                            </div>

                            <div>
                                <InputLabel for="start_date" value="Fecha de inicio" />
                                <TextInput id="start_date" type="date" v-model="form.start_date" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.start_date" />
                            </div>

                            <div>
                                <InputLabel for="estimated_end_date" value="Fecha estimada de fin" />
                                <TextInput id="estimated_end_date" type="date" v-model="form.estimated_end_date" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.estimated_end_date" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="actual_end_date" value="Fecha real de finalización" />
                            <TextInput id="actual_end_date" type="date" v-model="form.actual_end_date" class="mt-1 block w-full max-w-xs" />
                            <InputError class="mt-2" :message="form.errors.actual_end_date" />
                        </div>

                        <div>
                            <InputLabel for="status" value="Estado" />
                            <select id="status" v-model="form.status" class="mt-1 block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="pendiente">Pendiente</option>
                                <option value="activa">Activa</option>
                                <option value="en_recuperacion">En recuperación</option>
                                <option value="extendida">Extendida</option>
                                <option value="finalizada">Finalizada</option>
                                <option value="cancelada">Cancelada</option>
                                <option value="aprobada">Aprobada</option>
                                <option value="reprobada">Reprobada</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="required_hours" value="Horas requeridas" />
                                <TextInput id="required_hours" type="number" step="0.5" v-model="form.required_hours" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.required_hours" />
                            </div>

                            <div>
                                <InputLabel for="weekly_hours" value="Intensidad horaria (horas/semana)" />
                                <TextInput id="weekly_hours" type="number" step="0.5" min="1" v-model="form.weekly_hours" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.weekly_hours" />
                            </div>

                            <div>
                                <InputLabel value="Horas acumuladas" />
                                <p class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                    {{ enrollment.accumulated_hours }} horas ({{ enrollment.progress_percentage }}%)
                                </p>
                                <p class="mt-1 text-xs text-gray-500">Calculado automáticamente a partir de la asistencia registrada. No es editable aquí.</p>
                            </div>
                        </div>

                        <div v-if="enrollment.promotion || enrollment.referral" class="rounded-md border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600">
                            <p v-if="enrollment.promotion">Promoción aplicada: {{ enrollment.promotion.name }} (-${{ Number(enrollment.promotion_discount).toLocaleString('es-CO') }})</p>
                            <p v-if="enrollment.referral">Referido por: {{ enrollment.referral.referrer?.name }} (-${{ Number(enrollment.referral_discount).toLocaleString('es-CO') }})</p>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="base_price" value="Precio base" />
                                <TextInput id="base_price" type="number" step="0.01" v-model="form.base_price" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.base_price" />
                            </div>

                            <div>
                                <InputLabel for="final_price" value="Precio final" />
                                <TextInput id="final_price" type="number" step="0.01" v-model="form.final_price" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.final_price" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="monthly_fee" value="Mensualidad" />
                            <TextInput id="monthly_fee" type="number" step="1000" min="0" v-model="form.monthly_fee" class="mt-1 block w-full max-w-xs" />
                            <InputError class="mt-2" :message="form.errors.monthly_fee" />
                            <p class="mt-1 text-xs text-gray-500">Tomada del nivel; ajústala si este estudiante paga otro valor. Vacía: no se cobra mensualidad.</p>
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('enrollments.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                            <a
                                v-if="isAdmin && enrollment.status === 'aprobada'"
                                :href="route('enrollments.certificate', enrollment.id)"
                                class="ml-auto text-sm font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                Descargar certificado
                            </a>
                        </div>
                    </form>
                </div>

                <EnrollmentContractsPanel v-if="contracts" :enrollment="enrollment" :contracts="contracts" />

                <div
                    v-if="enrollment.previous_enrollment || enrollment.next_enrollment || enrollment.level?.next_level || enrollment.prerequisite_waived"
                    class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg"
                >
                    <h3 class="text-sm font-medium text-gray-900">Ruta de niveles</h3>

                    <div v-if="page.props.flash?.success" class="mt-3 rounded-md bg-green-50 p-3 text-sm text-green-700">
                        {{ page.props.flash.success }}
                    </div>

                    <ul class="mt-3 space-y-2 text-sm text-gray-600">
                        <li v-if="enrollment.previous_enrollment">
                            Viene de
                            <Link :href="route('enrollments.edit', enrollment.previous_enrollment.id)" class="font-medium text-indigo-600 hover:text-indigo-800">
                                {{ enrollment.previous_enrollment.level?.name }}
                            </Link>
                            (aprobado).
                        </li>
                        <li v-if="enrollment.prerequisite_waived" class="text-amber-700">
                            Matriculado sin haber aprobado el nivel anterior: el administrador omitió el requisito.
                        </li>
                        <li v-if="enrollment.level?.next_level">
                            Nivel siguiente: <strong>{{ enrollment.level.next_level.name }}</strong>
                            <template v-if="enrollment.next_enrollment">
                                ·
                                <Link :href="route('enrollments.edit', enrollment.next_enrollment.id)" class="font-medium text-indigo-600 hover:text-indigo-800">
                                    ver matrícula ({{ enrollment.next_enrollment.status }})
                                </Link>
                            </template>
                        </li>
                        <li v-else class="text-gray-500">Este es el último nivel de la ruta.</li>
                    </ul>

                    <PrimaryButton v-if="canPromote" class="mt-4" type="button" @click="promote">
                        Pasar al siguiente nivel
                    </PrimaryButton>
                </div>

                <div v-if="isAdmin" class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-sm font-medium text-gray-900">Extensión de nivel</h3>
                    <p class="mt-1 text-xs text-gray-500">
                        Registra un cambio en la fecha estimada de finalización (por ejemplo, por un proceso de recuperación).
                    </p>

                    <div v-if="enrollment.extensions?.length" class="mt-4 space-y-2">
                        <div v-for="extension in enrollment.extensions" :key="extension.id" class="rounded-md border border-gray-200 p-3 text-sm">
                            <p class="text-gray-700">
                                {{ extension.previous_end_date ?? '—' }} → <strong>{{ extension.new_end_date }}</strong>
                                — {{ extension.reason }}
                            </p>
                            <p class="text-xs text-gray-500">
                                Por {{ extension.extended_by?.name }} el {{ extension.created_at?.slice(0, 10) }}
                                <span v-if="extension.notes"> · {{ extension.notes }}</span>
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="submitExtension" class="mt-4 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="new_end_date" value="Nueva fecha de finalización" />
                                <TextInput id="new_end_date" type="date" v-model="extensionForm.new_end_date" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="extensionForm.errors.new_end_date" />
                            </div>

                            <div>
                                <InputLabel for="reason" value="Motivo" />
                                <TextInput id="reason" v-model="extensionForm.reason" class="mt-1 block w-full" required placeholder="Recuperación de evaluación" />
                                <InputError class="mt-2" :message="extensionForm.errors.reason" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="extension_notes" value="Observaciones" />
                            <textarea
                                id="extension_notes"
                                v-model="extensionForm.notes"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <InputError class="mt-2" :message="extensionForm.errors.notes" />
                        </div>

                        <PrimaryButton :disabled="extensionForm.processing">Registrar extensión</PrimaryButton>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
