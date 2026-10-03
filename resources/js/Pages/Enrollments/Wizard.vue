<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StudentGuardianFields from '@/Components/StudentGuardianFields.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

/**
 * Asistente de matrícula (parte 6.3 del plan de mejoras).
 */
const props = defineProps({
    students: Array,
    signedPerStudentCodes: Object,
    levels: Array,
    promotions: Array,
    referrals: Array,
    templates: Array,
    documentTypes: Object,
});

const page = usePage();
const isAdmin = page.props.auth.roles?.includes('admin');
const today = new Date().toISOString().slice(0, 10);
const selectClasses = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

const steps = ['Alumno', 'Nivel y precio', 'Contratos', 'Resumen y firma'];
const step = ref(0);
const studentMode = ref('existing');
const studentSearch = ref('');

const form = useForm({
    student_id: '',
    new_student: {
        code: '',
        name: '',
        document_type: null,
        document: '',
        birth_date: '',
        email: '',
        phone: '',
        address: '',
        guardian_name: '',
        guardian_document_type: null,
        guardian_document: '',
        guardian_relationship: '',
        guardian_email: '',
        guardian_phone: '',
    },
    level_id: '',
    enrolled_at: today,
    start_date: today,
    estimated_end_date: '',
    required_hours: '',
    weekly_hours: '',
    base_price: '',
    promotion_id: '',
    referral_id: '',
    monthly_fee: '',
    skip_prerequisite: false,
    special_clauses: Object.fromEntries(props.templates.map((template) => [template.id, ''])),
    sign_method: 'oficina',
});

/* Paso 1: alumno */
const filteredStudents = computed(() => {
    const term = studentSearch.value.trim().toLowerCase();
    const list = term
        ? props.students.filter((student) => `${student.code} ${student.name}`.toLowerCase().includes(term))
        : props.students;

    return list.slice(0, 50);
});

const selectedStudent = computed(() => props.students.find((student) => student.id === Number(form.student_id)) ?? null);

function computeIsMinor(birthDate) {
    if (!birthDate) {
        return false;
    }
    const adulthood = new Date(`${birthDate}T00:00:00`);
    adulthood.setFullYear(adulthood.getFullYear() + 18);
    return adulthood > new Date();
}

const student = computed(() => {
    if (studentMode.value === 'existing') {
        return selectedStudent.value;
    }

    const data = form.new_student;
    const isMinor = computeIsMinor(data.birth_date);
    const missing = [];
    if (!data.birth_date) missing.push('fecha de nacimiento');
    if (isMinor && !data.guardian_name) missing.push('nombre del acudiente');
    if (isMinor && !data.guardian_email) missing.push('correo del acudiente');

    return {
        id: null,
        name: data.name,
        email: data.email,
        guardian_name: data.guardian_name,
        guardian_email: data.guardian_email,
        is_minor: isMinor,
        missing_contract_data: missing,
    };
});

const newStudentErrors = computed(() => Object.fromEntries(
    Object.entries(form.errors)
        .filter(([key]) => key.startsWith('new_student.'))
        .map(([key, message]) => [key.replace('new_student.', ''), message]),
));

const studentStepValid = computed(() => (studentMode.value === 'existing'
    ? Boolean(form.student_id)
    : Boolean(form.new_student.code && form.new_student.name)));

/* Paso 2: nivel y precio */
const selectedLevel = computed(() => props.levels.find((level) => level.id === Number(form.level_id)) ?? null);
const selectedPromotion = computed(() => props.promotions.find((promotion) => promotion.id === Number(form.promotion_id)));
const selectedReferral = computed(() => props.referrals.find((referral) => referral.id === Number(form.referral_id)));

watch(() => form.level_id, () => {
    const level = selectedLevel.value;
    if (!level) {
        return;
    }

    form.required_hours = level.required_hours;
    form.weekly_hours = level.weekly_hours;
    form.base_price = level.price;
    form.monthly_fee = level.monthly_fee ?? '';

    if (level.duration_months) {
        const end = new Date(form.start_date || today);
        end.setMonth(end.getMonth() + level.duration_months);
        form.estimated_end_date = end.toISOString().slice(0, 10);
    }
});

const pricePreview = computed(() => {
    const base = Number(form.base_price || 0);
    let promotionDiscount = 0;
    if (selectedPromotion.value) {
        promotionDiscount = selectedPromotion.value.discount_type === 'porcentaje'
            ? Math.round(base * (Number(selectedPromotion.value.value) / 100))
            : Number(selectedPromotion.value.value);
    }

    let referralDiscount = 0;
    if (selectedReferral.value) {
        referralDiscount = student.value?.id && Number(selectedReferral.value.referred_student_id) === Number(student.value.id)
            ? Number(selectedReferral.value.referred_discount)
            : Number(selectedReferral.value.referrer_discount);
    }

    return { base, promotionDiscount, referralDiscount, final: Math.max(0, base - promotionDiscount - referralDiscount) };
});

const levelStepValid = computed(() => Boolean(form.level_id && form.start_date && form.enrolled_at && form.weekly_hours && form.required_hours !== ''));

function money(value) {
    return Number(value).toLocaleString('es-CO');
}

/* Paso 3: contratos (se asignan solos) */
const assignedTemplates = computed(() => {
    const signedCodes = student.value?.id ? (props.signedPerStudentCodes[student.value.id] ?? []) : [];

    return props.templates.filter((template) => {
        if (template.requires_guardian && !student.value?.is_minor) {
            return false;
        }
        return !(template.scope === 'alumno' && signedCodes.includes(template.code));
    });
});

const skippedTemplates = computed(() => props.templates.filter((template) => !assignedTemplates.value.includes(template)));
const contractsBlocked = computed(() => (student.value?.missing_contract_data ?? []).length > 0);
const signer = computed(() => (student.value?.is_minor
    ? { role: 'acudiente', name: student.value.guardian_name, email: student.value.guardian_email }
    : { role: 'alumno', name: student.value?.name, email: student.value?.email }));

/* Navegación */
const canContinue = computed(() => [studentStepValid.value, levelStepValid.value, true, true][step.value]);

const stepFields = [
    ['student_id', 'new_student'],
    ['level_id', 'enrolled_at', 'start_date', 'estimated_end_date', 'required_hours', 'weekly_hours', 'base_price', 'monthly_fee', 'promotion_id', 'referral_id', 'skip_prerequisite'],
    ['special_clauses'],
    ['sign_method'],
];

function submit() {
    form
        .transform((data) => {
            const payload = { ...data };
            if (studentMode.value === 'existing') {
                delete payload.new_student;
            } else {
                delete payload.student_id;
            }
            if (contractsBlocked.value || assignedTemplates.value.length === 0) {
                payload.sign_method = 'despues';
            }
            return payload;
        })
        .post(route('enrollments.wizard.store'), {
            onError: (errors) => {
                const firstStep = stepFields.findIndex((fields) => Object.keys(errors).some((key) => fields.some((field) => key === field || key.startsWith(`${field}.`))));
                if (firstStep >= 0) {
                    step.value = firstStep;
                }
            },
        });
}

const signMethods = [
    { value: 'oficina', label: 'Firmar ahora en la oficina', help: 'Abre los contratos en pantalla completa en este computador.' },
    { value: 'correo', label: 'Enviar enlace por correo', help: 'Llega al correo de quien firma, junto con el código de verificación.' },
    { value: 'whatsapp', label: 'Enviar enlace por WhatsApp', help: 'Abre WhatsApp Web con el mensaje listo.' },
    { value: 'enlace', label: 'Copiar el enlace', help: 'Para compartirlo por otro medio.' },
    { value: 'despues', label: 'Firmar después', help: 'Los contratos quedan generados en la matrícula.' },
];
</script>

<template>
    <Head title="Asistente de matrícula" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Asistente de matrícula</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <ol class="grid grid-cols-4 gap-2 text-xs sm:text-sm">
                    <li
                        v-for="(label, index) in steps"
                        :key="label"
                        class="rounded-lg border px-2 py-2 text-center"
                        :class="index === step ? 'border-indigo-600 bg-indigo-50 font-semibold text-indigo-700' : index < step ? 'border-green-200 bg-green-50 text-green-700' : 'border-gray-200 bg-white text-gray-500'"
                    >
                        {{ index + 1 }}. {{ label }}
                    </li>
                </ol>

                <div class="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg">
                    <!-- Paso 1 -->
                    <template v-if="step === 0">
                        <div class="flex gap-2">
                            <button
                                v-for="mode in [{ value: 'existing', label: 'Estudiante registrado' }, { value: 'new', label: 'Estudiante nuevo' }]"
                                :key="mode.value"
                                type="button"
                                class="rounded-md border px-4 py-2 text-sm"
                                :class="studentMode === mode.value ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-300 text-gray-700'"
                                @click="studentMode = mode.value"
                            >
                                {{ mode.label }}
                            </button>
                        </div>

                        <div v-if="studentMode === 'existing'" class="space-y-3">
                            <TextInput v-model="studentSearch" class="block w-full" placeholder="Buscar por nombre o código..." />
                            <select v-model="form.student_id" size="8" :class="selectClasses">
                                <option v-for="item in filteredStudents" :key="item.id" :value="item.id">{{ item.code }} - {{ item.name }}</option>
                            </select>
                            <InputError :message="form.errors.student_id" />

                            <div v-if="selectedStudent" class="rounded-md bg-gray-50 p-3 text-sm text-gray-700">
                                <p>
                                    <strong>{{ selectedStudent.name }}</strong>
                                    <span v-if="selectedStudent.is_minor" class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Menor de edad</span>
                                </p>
                                <p v-if="selectedStudent.is_minor" class="mt-1">Acudiente: {{ selectedStudent.guardian_name || '—' }} · {{ selectedStudent.guardian_email || 'sin correo' }}</p>
                                <p v-if="selectedStudent.missing_contract_data.length" class="mt-2 text-red-700">
                                    Datos incompletos: falta {{ selectedStudent.missing_contract_data.join(', ') }}.
                                    <Link :href="route('students.edit', selectedStudent.id)" class="font-medium underline">Completar ficha</Link>
                                    (sin estos datos la matrícula se crea, pero los contratos no).
                                </p>
                            </div>
                        </div>

                        <div v-else class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="new_code" value="Código" />
                                    <TextInput id="new_code" v-model="form.new_student.code" class="mt-1 block w-full" required />
                                    <InputError class="mt-2" :message="form.errors['new_student.code']" />
                                </div>
                                <div>
                                    <InputLabel for="new_name" value="Nombre completo" />
                                    <TextInput id="new_name" v-model="form.new_student.name" class="mt-1 block w-full" required />
                                    <InputError class="mt-2" :message="form.errors['new_student.name']" />
                                </div>
                                <div>
                                    <InputLabel for="new_document_type" value="Tipo de documento" />
                                    <select id="new_document_type" v-model="form.new_student.document_type" :class="selectClasses">
                                        <option :value="null">Sin especificar</option>
                                        <option v-for="(label, value) in documentTypes" :key="value" :value="value">{{ label }}</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors['new_student.document_type']" />
                                </div>
                                <div>
                                    <InputLabel for="new_document" value="Documento" />
                                    <TextInput id="new_document" v-model="form.new_student.document" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors['new_student.document']" />
                                </div>
                                <div>
                                    <InputLabel for="new_birth_date" value="Fecha de nacimiento" />
                                    <TextInput id="new_birth_date" type="date" v-model="form.new_student.birth_date" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors['new_student.birth_date']" />
                                </div>
                                <div>
                                    <InputLabel for="new_email" value="Correo" />
                                    <TextInput id="new_email" type="email" v-model="form.new_student.email" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors['new_student.email']" />
                                </div>
                                <div>
                                    <InputLabel for="new_phone" value="Teléfono" />
                                    <TextInput id="new_phone" v-model="form.new_student.phone" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors['new_student.phone']" />
                                </div>
                                <div>
                                    <InputLabel for="new_address" value="Dirección" />
                                    <TextInput id="new_address" v-model="form.new_student.address" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors['new_student.address']" />
                                </div>
                            </div>

                            <StudentGuardianFields :form="form.new_student" :errors="newStudentErrors" :document-types="documentTypes" />
                        </div>
                    </template>

                    <!-- Paso 2 -->
                    <template v-else-if="step === 1">
                        <div>
                            <InputLabel for="level_id" value="Curso / Nivel" />
                            <select id="level_id" v-model="form.level_id" :class="selectClasses">
                                <option value="" disabled>Elige un nivel</option>
                                <option v-for="level in levels" :key="level.id" :value="level.id">{{ level.course?.name }} {{ level.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.level_id" />
                            <p v-if="selectedLevel?.previous_level" class="mt-1 text-xs text-gray-500">Requiere haber aprobado {{ selectedLevel.previous_level.name }}.</p>
                            <label v-if="isAdmin && selectedLevel?.previous_level" class="mt-2 flex items-start gap-2 text-sm text-amber-700">
                                <input v-model="form.skip_prerequisite" type="checkbox" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                Omitir requisito (queda registrado en la auditoría)
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <InputLabel for="enrolled_at" value="Fecha de matrícula" />
                                <TextInput id="enrolled_at" type="date" v-model="form.enrolled_at" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.enrolled_at" />
                            </div>
                            <div>
                                <InputLabel for="start_date" value="Fecha de inicio" />
                                <TextInput id="start_date" type="date" v-model="form.start_date" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.start_date" />
                            </div>
                            <div>
                                <InputLabel for="estimated_end_date" value="Fecha estimada de fin" />
                                <TextInput id="estimated_end_date" type="date" v-model="form.estimated_end_date" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.estimated_end_date" />
                            </div>
                            <div>
                                <InputLabel for="required_hours" value="Horas requeridas" />
                                <TextInput id="required_hours" type="number" step="0.5" v-model="form.required_hours" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.required_hours" />
                            </div>
                            <div>
                                <InputLabel for="weekly_hours" value="Horas por semana" />
                                <TextInput id="weekly_hours" type="number" step="0.5" min="1" v-model="form.weekly_hours" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.weekly_hours" />
                            </div>
                            <div>
                                <InputLabel for="monthly_fee" value="Mensualidad" />
                                <TextInput id="monthly_fee" type="number" step="1000" min="0" v-model="form.monthly_fee" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.monthly_fee" />
                            </div>
                            <div>
                                <InputLabel for="base_price" value="Precio base" />
                                <TextInput id="base_price" type="number" step="1000" min="0" v-model="form.base_price" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.base_price" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="promotion_id" value="Promoción" />
                                <select id="promotion_id" v-model="form.promotion_id" :class="selectClasses">
                                    <option value="">Ninguna</option>
                                    <option v-for="promotion in promotions" :key="promotion.id" :value="promotion.id">{{ promotion.name }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel for="referral_id" value="Referido" />
                                <select id="referral_id" v-model="form.referral_id" :class="selectClasses">
                                    <option value="">Ninguno</option>
                                    <option v-for="referral in referrals" :key="referral.id" :value="referral.id">{{ referral.referrer?.name }} → {{ referral.referred?.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="rounded-md border border-gray-200 bg-gray-50 p-4 text-sm">
                            <div class="flex justify-between"><span class="text-gray-600">Precio base</span><span>${{ money(pricePreview.base) }}</span></div>
                            <div v-if="pricePreview.promotionDiscount > 0" class="flex justify-between text-red-600"><span>Promoción</span><span>-${{ money(pricePreview.promotionDiscount) }}</span></div>
                            <div v-if="pricePreview.referralDiscount > 0" class="flex justify-between text-red-600"><span>Referido</span><span>-${{ money(pricePreview.referralDiscount) }}</span></div>
                            <div class="mt-2 flex justify-between border-t border-gray-300 pt-2 font-medium"><span>Precio final</span><span>${{ money(pricePreview.final) }}</span></div>
                        </div>
                    </template>

                    <!-- Paso 3 -->
                    <template v-else-if="step === 2">
                        <div v-if="contractsBlocked" class="rounded-md bg-red-50 p-3 text-sm text-red-700">
                            Datos incompletos del estudiante: falta {{ student.missing_contract_data.join(', ') }}. La matrícula se creará, pero los contratos se generan cuando la ficha esté completa.
                        </div>

                        <p class="text-sm text-gray-600">
                            Firma: <strong>{{ signer.name || '—' }}</strong> ({{ signer.role === 'acudiente' ? 'acudiente, porque el estudiante es menor de edad' : 'el estudiante' }}).
                        </p>

                        <ul v-if="assignedTemplates.length" class="divide-y divide-gray-100 rounded-md border border-gray-200">
                            <li v-for="template in assignedTemplates" :key="template.id" class="space-y-2 p-3">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ template.name }}
                                    <span class="ml-1 text-xs font-normal text-gray-500">
                                        {{ template.acceptance_mode === 'obligatorio' ? 'obligatorio' : 'opcional (Sí/No)' }} · {{ template.scope === 'alumno' ? 'una vez por alumno' : 'por matrícula' }}
                                    </span>
                                </p>
                                <textarea
                                    v-model="form.special_clauses[template.id]"
                                    rows="2"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Cláusulas especiales para este estudiante (opcional)"
                                ></textarea>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-500">No hay plantillas de contrato publicadas que apliquen.</p>

                        <p v-if="skippedTemplates.length" class="text-xs text-gray-500">
                            Se omiten: {{ skippedTemplates.map((template) => template.name).join(', ') }} (ya firmados o no aplican).
                        </p>
                    </template>

                    <!-- Paso 4 -->
                    <template v-else>
                        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                            <div><dt class="text-gray-500">Estudiante</dt><dd class="font-medium text-gray-900">{{ student?.name }}{{ studentMode === 'new' ? ' (nuevo)' : '' }}</dd></div>
                            <div><dt class="text-gray-500">Nivel</dt><dd class="font-medium text-gray-900">{{ selectedLevel?.course?.name }} {{ selectedLevel?.name }}</dd></div>
                            <div><dt class="text-gray-500">Inicio</dt><dd class="font-medium text-gray-900">{{ form.start_date }}</dd></div>
                            <div><dt class="text-gray-500">Precio final</dt><dd class="font-medium text-gray-900">${{ money(pricePreview.final) }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-gray-500">Contratos</dt><dd class="font-medium text-gray-900">{{ contractsBlocked ? 'Pendientes de completar la ficha' : (assignedTemplates.map((template) => template.name).join(', ') || 'Ninguno') }}</dd></div>
                        </dl>

                        <p class="text-sm text-gray-600">La matrícula queda <strong>pendiente</strong> y se activa sola cuando estén firmados los contratos obligatorios y registrado el pago.</p>

                        <fieldset v-if="!contractsBlocked && assignedTemplates.length" class="space-y-2">
                            <legend class="text-sm font-medium text-gray-900">¿Cómo se firman los contratos?</legend>
                            <label
                                v-for="method in signMethods"
                                :key="method.value"
                                class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm"
                                :class="form.sign_method === method.value ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200'"
                            >
                                <input v-model="form.sign_method" type="radio" :value="method.value" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                                <span>
                                    <span class="font-medium text-gray-900">{{ method.label }}</span>
                                    <span class="block text-gray-500">{{ method.help }}</span>
                                </span>
                            </label>
                            <InputError :message="form.errors.sign_method" />
                        </fieldset>
                    </template>

                    <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                        <SecondaryButton v-if="step > 0" type="button" @click="step--">Atrás</SecondaryButton>
                        <Link v-else :href="route('enrollments.index')" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>

                        <PrimaryButton v-if="step < steps.length - 1" type="button" :disabled="!canContinue" @click="step++">Continuar</PrimaryButton>
                        <PrimaryButton v-else type="button" :disabled="form.processing" @click="submit">Crear matrícula</PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
