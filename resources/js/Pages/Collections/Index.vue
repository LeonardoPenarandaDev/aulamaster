<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * Cartera en mora (parte 8 del plan de mejoras).
 */
const props = defineProps({
    students: Array,
    totals: Object,
    filters: Object,
    levels: Array,
    channels: Object,
    results: Object,
    canManageAgreements: Boolean,
});

const page = usePage();
const filters = ref({
    days: props.filters.days,
    level_id: props.filters.level_id ?? '',
    uncontacted: props.filters.uncontacted,
});
const expandedStudentId = ref(null);

function applyFilters() {
    router.get(route('collections.index'), {
        days: filters.value.days !== 'todos' ? filters.value.days : undefined,
        level_id: filters.value.level_id || undefined,
        uncontacted: filters.value.uncontacted ? 1 : undefined,
    }, { preserveState: true, replace: true });
}

function money(value) {
    return Number(value).toLocaleString('es-CO');
}

/**
 * wa.me necesita el número con indicativo de país: a los celulares locales
 * de 10 dígitos se les antepone el 57 (Colombia).
 */
function whatsappUrl(phone, text = '') {
    let digits = (phone ?? '').replace(/\D/g, '');

    if (digits.length === 10) {
        digits = `57${digits}`;
    }

    return `https://wa.me/${digits}${text ? `?text=${encodeURIComponent(text)}` : ''}`;
}

function reminderText(row) {
    const institution = page.props.institution?.name ?? '';
    const greeting = row.contact.role === 'acudiente'
        ? `Hola ${row.contact.name ?? ''}, le escribimos de ${institution} sobre los pagos de ${row.student.name}.`
        : `Hola ${row.student.name}, te escribimos de ${institution}.`;

    return `${greeting} Hay un saldo vencido de $${money(row.total)}. ¿Podemos ayudarte a ponerte al día?`;
}

/* Registro de contactos */
const followUpRow = ref(null);
const followUpForm = useForm({ channel: 'whatsapp', result: 'contactado', note: '' });

function openFollowUp(row, channel = 'whatsapp') {
    followUpRow.value = row;
    followUpForm.reset();
    followUpForm.channel = channel;
}

function saveFollowUp() {
    followUpForm.post(route('collections.follow-ups.store', followUpRow.value.student.id), {
        preserveScroll: true,
        onSuccess: () => (followUpRow.value = null),
    });
}

/* Acuerdos de pago */
const agreementRow = ref(null);
const agreementForm = useForm({ agreed_until: '', notes: '' });

function openAgreement(row) {
    agreementRow.value = row;
    agreementForm.reset();
}

function saveAgreement() {
    agreementForm.post(route('collections.agreements.store', agreementRow.value.student.id), {
        preserveScroll: true,
        onSuccess: () => (agreementRow.value = null),
    });
}

function cancelAgreement(row) {
    if (confirm(`¿Cancelar el acuerdo de pago de ${row.student.name}? Si sigue en mora, vuelve a quedar bloqueado.`)) {
        router.post(route('collections.agreements.cancel', row.agreement.id), {}, { preserveScroll: true });
    }
}

const selectClasses = 'mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
</script>

<template>
    <Head title="Cartera en mora" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Cartera en mora</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-screen-2xl space-y-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash?.success" class="rounded-md bg-green-50 p-4 text-sm text-green-700">
                    {{ page.props.flash.success }}
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Días de mora</label>
                        <select v-model="filters.days" :class="selectClasses" @change="applyFilters">
                            <option value="todos">Todos</option>
                            <option value="amarillo">Amarillo (1 a 9 días)</option>
                            <option value="rojo">Rojo (10 días o más)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Nivel</label>
                        <select v-model="filters.level_id" :class="selectClasses" @change="applyFilters">
                            <option value="">Todos</option>
                            <option v-for="level in levels" :key="level.id" :value="level.id">{{ level.course?.name }} {{ level.name }}</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 pb-2 text-sm text-gray-700">
                        <input v-model="filters.uncontacted" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @change="applyFilters" />
                        Sin contactar
                    </label>
                </div>

                <p class="text-sm text-gray-500">
                    {{ totals.students }} estudiante{{ totals.students === 1 ? '' : 's' }} en mora · ${{ money(totals.amount) }} vencidos.
                </p>

                <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Mora</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estudiante</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Saldo vencido</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Contactar a</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Último contacto</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <template v-for="row in students" :key="row.student.id">
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="row.severity === 'rojo' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'"
                                        >
                                            <span class="h-2 w-2 rounded-full" :class="row.severity === 'rojo' ? 'bg-red-500' : 'bg-amber-400'" />
                                            {{ row.days_overdue }} día{{ row.days_overdue === 1 ? '' : 's' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-900">
                                        {{ row.student.code }} - {{ row.student.name }}
                                        <span v-if="row.student.is_minor" class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">Menor</span>
                                        <p class="text-xs text-gray-500">{{ row.levels.join(', ') }}</p>
                                        <p v-if="row.agreement" class="mt-1 text-xs font-medium text-indigo-700">
                                            Acuerdo de pago hasta el {{ row.agreement.agreed_until }}
                                            <button v-if="canManageAgreements" type="button" class="ml-1 text-gray-500 underline" @click="cancelAgreement(row)">cancelar</button>
                                        </p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-semibold text-gray-900">${{ money(row.total) }}</td>
                                    <td class="px-4 py-4 text-sm">
                                        <p class="text-gray-900">{{ row.contact.name || '—' }} <span class="text-xs text-gray-500">({{ row.contact.role }})</span></p>
                                        <div class="mt-1 flex flex-wrap gap-1.5">
                                            <a
                                                v-if="row.contact.phone"
                                                :href="whatsappUrl(row.contact.phone, reminderText(row))"
                                                target="_blank"
                                                rel="noopener"
                                                class="rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 hover:bg-green-200"
                                                @click="openFollowUp(row, 'whatsapp')"
                                            >WhatsApp</a>
                                            <a
                                                v-if="row.contact.phone"
                                                :href="`tel:${row.contact.phone}`"
                                                class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 hover:bg-gray-200"
                                                @click="openFollowUp(row, 'llamada')"
                                            >Llamar</a>
                                            <a
                                                v-if="row.contact.email"
                                                :href="`mailto:${row.contact.email}?subject=${encodeURIComponent('Pagos pendientes')}&body=${encodeURIComponent(reminderText(row))}`"
                                                class="rounded bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100"
                                                @click="openFollowUp(row, 'correo')"
                                            >Correo</a>
                                            <span v-if="!row.contact.phone && !row.contact.email" class="text-xs text-gray-400">Sin datos de contacto</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-gray-600">
                                        <template v-if="row.last_follow_up">
                                            <p>{{ row.last_follow_up.contacted_at }} · {{ row.last_follow_up.channel }}</p>
                                            <p class="font-medium text-gray-800">{{ row.last_follow_up.result }}</p>
                                            <p v-if="row.last_follow_up.note" class="text-gray-500">{{ row.last_follow_up.note }}</p>
                                        </template>
                                        <span v-else class="font-medium text-red-600">Sin contactar</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                        <button type="button" class="text-indigo-600 hover:text-indigo-900" @click="openFollowUp(row)">Registrar contacto</button>
                                        <button v-if="canManageAgreements" type="button" class="ml-3 text-gray-600 hover:text-gray-900" @click="openAgreement(row)">Acuerdo</button>
                                        <button type="button" class="ml-3 text-gray-500 hover:text-gray-800" @click="expandedStudentId = expandedStudentId === row.student.id ? null : row.student.id">
                                            {{ expandedStudentId === row.student.id ? 'Ocultar' : 'Pagos' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="expandedStudentId === row.student.id" class="bg-gray-50">
                                    <td colspan="6" class="px-4 py-3">
                                        <ul class="flex flex-wrap gap-2">
                                            <li v-for="payment in row.payments" :key="payment.id" class="rounded-md border border-gray-200 bg-white px-3 py-1 text-xs text-gray-700">
                                                {{ payment.concept }} · ${{ money(payment.amount) }} · venció {{ payment.due_date ?? '—' }}
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="students.length === 0">
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No hay estudiantes en mora con estos filtros. 🎉</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="followUpRow !== null" max-width="md" @close="followUpRow = null">
            <form v-if="followUpRow" class="space-y-4 p-6" @submit.prevent="saveFollowUp">
                <h3 class="text-lg font-medium text-gray-900">Registrar contacto · {{ followUpRow.student.name }}</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Medio</label>
                        <select v-model="followUpForm.channel" :class="[selectClasses, 'w-full']">
                            <option v-for="(label, value) in channels" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Resultado</label>
                        <select v-model="followUpForm.result" :class="[selectClasses, 'w-full']">
                            <option v-for="(label, value) in results" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Nota</label>
                    <textarea v-model="followUpForm.note" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ej: dice que paga el viernes"></textarea>
                    <InputError :message="followUpForm.errors.note" />
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="followUpRow = null">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="followUpForm.processing">Guardar</PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="agreementRow !== null" max-width="md" @close="agreementRow = null">
            <form v-if="agreementRow" class="space-y-4 p-6" @submit.prevent="saveAgreement">
                <h3 class="text-lg font-medium text-gray-900">Acuerdo de pago · {{ agreementRow.student.name }}</h3>
                <p class="text-sm text-gray-600">Mientras el acuerdo esté vigente, el estudiante puede usar el portal y asistir a clase aunque siga en mora.</p>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Pagará a más tardar el</label>
                    <input v-model="agreementForm.agreed_until" type="date" :class="[selectClasses, 'w-full']" required />
                    <InputError :message="agreementForm.errors.agreed_until" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Condiciones</label>
                    <textarea v-model="agreementForm.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="agreementRow = null">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="agreementForm.processing">Guardar acuerdo</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
