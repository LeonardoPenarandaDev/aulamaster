<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    enrollment: Object,
    contracts: Object,
});

const page = usePage();

const statusClasses = {
    pendiente: 'bg-gray-100 text-gray-700',
    enviado: 'bg-blue-100 text-blue-800',
    abierto: 'bg-indigo-100 text-indigo-800',
    firmado: 'bg-green-100 text-green-800',
    anulado: 'bg-red-50 text-red-700 line-through',
};

const openContracts = computed(() => props.contracts.items.filter((contract) => ['pendiente', 'enviado', 'abierto'].includes(contract.status)));
const canGenerate = computed(() => props.contracts.pendingTemplates.length > 0 && props.contracts.missingData.length === 0);

const showClauses = ref(false);
const generateForm = useForm({
    special_clauses: Object.fromEntries(props.contracts.pendingTemplates.map((template) => [template.id, ''])),
});

function generate() {
    generateForm.post(route('enrollments.contracts.store', props.enrollment.id), {
        preserveScroll: true,
        onSuccess: () => (showClauses.value = false),
    });
}

const sendForm = useForm({ via: 'correo' });

function send(via) {
    sendForm.via = via;
    sendForm.post(route('enrollments.contracts.send', props.enrollment.id), { preserveScroll: true });
}

function voidContract(contract) {
    const reason = prompt(`¿Anular «${contract.name}»? Escribe el motivo (opcional):`);
    if (reason === null) {
        return;
    }

    router.post(route('contract-signatures.void', contract.id), { reason }, { preserveScroll: true });
}

/**
 * wa.me necesita el número con indicativo de país: a los celulares locales
 * de 10 dígitos se les antepone el 57 (Colombia).
 */
function whatsappUrl(phone, text) {
    let digits = (phone ?? '').replace(/\D/g, '');

    if (digits.length === 10) {
        digits = `57${digits}`;
    }

    return `https://wa.me/${digits}?text=${encodeURIComponent(text)}`;
}

const link = computed(() => page.props.flash?.contractLink ?? null);
const copied = ref(false);

const whatsappMessage = computed(() => link.value
    ? `Hola ${link.value.signer_name ?? ''}, te enviamos los contratos de matrícula para que los leas y los firmes: ${link.value.url} (el enlace vence el ${link.value.expires_at}).`
    : '');

watch(link, (value) => {
    copied.value = false;

    if (value?.via === 'whatsapp') {
        window.open(whatsappUrl(value.phone, whatsappMessage.value), '_blank', 'noopener');
    }
}, { immediate: true });

async function copyLink() {
    await navigator.clipboard.writeText(link.value.url);
    copied.value = true;
}

const signerLabel = computed(() => (props.contracts.signer.role === 'acudiente' ? 'Acudiente' : 'Estudiante'));
</script>

<template>
    <div class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="text-sm font-medium text-gray-900">Contratos</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Firma: <strong class="text-gray-800">{{ contracts.signer.name || '—' }}</strong>
                    ({{ signerLabel }}<template v-if="contracts.signer.email"> · {{ contracts.signer.email }}</template>)
                </p>
            </div>

            <div v-if="openContracts.length" class="flex flex-wrap items-center gap-2">
                <Link :href="route('enrollments.contracts.sign', enrollment.id)">
                    <PrimaryButton type="button">Firmar aquí</PrimaryButton>
                </Link>
                <SecondaryButton type="button" :disabled="sendForm.processing" @click="send('correo')">Enviar por correo</SecondaryButton>
                <SecondaryButton type="button" :disabled="sendForm.processing || !contracts.signer.phone" @click="send('whatsapp')">WhatsApp</SecondaryButton>
                <SecondaryButton type="button" :disabled="sendForm.processing" @click="send('enlace')">Copiar enlace</SecondaryButton>
            </div>
        </div>

        <div v-if="contracts.missingData.length" class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
            <strong>Datos incompletos:</strong> falta {{ contracts.missingData.join(', ') }}. No se pueden generar ni enviar contratos hasta
            <Link :href="route('students.edit', enrollment.student_id)" class="font-medium underline">completar la ficha del estudiante</Link>.
        </div>

        <div v-if="page.props.errors?.contracts" class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
            {{ page.props.errors.contracts }}
        </div>

        <div v-if="link" class="mt-4 space-y-2 rounded-md border border-indigo-100 bg-indigo-50 p-3 text-sm text-indigo-900">
            <p>Enlace de firma (vence el {{ link.expires_at }}). Los enlaces enviados antes dejan de funcionar.</p>
            <div class="flex flex-wrap items-center gap-2">
                <input :value="link.url" readonly class="min-w-0 flex-1 rounded-md border-indigo-200 bg-white text-xs" @focus="$event.target.select()" />
                <SecondaryButton type="button" @click="copyLink">{{ copied ? '¡Copiado!' : 'Copiar' }}</SecondaryButton>
                <a
                    v-if="link.phone"
                    :href="whatsappUrl(link.phone, whatsappMessage)"
                    target="_blank"
                    rel="noopener"
                    class="text-sm font-medium text-green-700 hover:text-green-900"
                >
                    Abrir WhatsApp
                </a>
            </div>
        </div>

        <div v-if="contracts.pendingTemplates.length" class="mt-4 rounded-md border border-gray-200 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-700">
                    Por generar: {{ contracts.pendingTemplates.map((template) => template.name).join(', ') }}
                </p>
                <div class="flex items-center gap-3">
                    <button type="button" class="text-sm text-gray-600 underline hover:text-gray-900" @click="showClauses = !showClauses">
                        {{ showClauses ? 'Ocultar cláusulas' : 'Agregar cláusulas especiales' }}
                    </button>
                    <PrimaryButton type="button" :disabled="!canGenerate || generateForm.processing" @click="generate">Generar contratos</PrimaryButton>
                </div>
            </div>

            <div v-if="showClauses" class="mt-4 space-y-3">
                <div v-for="template in contracts.pendingTemplates" :key="template.id">
                    <label :for="`clauses-${template.id}`" class="block text-xs font-medium text-gray-600">Cláusulas especiales · {{ template.name }}</label>
                    <textarea
                        :id="`clauses-${template.id}`"
                        v-model="generateForm.special_clauses[template.id]"
                        rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Opcional. Se agregan al final del contrato de este estudiante."
                    ></textarea>
                </div>
            </div>
        </div>

        <div v-if="contracts.items.length" class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="py-2 pr-4 font-medium">Contrato</th>
                        <th class="py-2 pr-4 font-medium">Estado</th>
                        <th class="py-2 pr-4 font-medium">Detalle</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="contract in contracts.items" :key="contract.id">
                        <td class="py-3 pr-4 text-gray-900">
                            {{ contract.name }} <span class="text-xs text-gray-400">v{{ contract.version }}</span>
                            <span v-if="contract.is_optional" class="ml-1 text-xs text-gray-500">(opcional)</span>
                            <p v-if="contract.special_clauses" class="mt-1 text-xs text-gray-500">Con cláusulas especiales</p>
                        </td>
                        <td class="whitespace-nowrap py-3 pr-4">
                            <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[contract.status]">{{ contract.status_label }}</span>
                        </td>
                        <td class="py-3 pr-4 text-xs text-gray-600">
                            <template v-if="contract.status === 'firmado'">
                                {{ contract.signer_name }} ({{ contract.signer_role }}) · {{ contract.signed_at }} ·
                                {{ contract.signing_method === 'oficina' ? 'en la oficina' : 'a distancia' }}
                                <span v-if="contract.is_optional" class="font-medium" :class="contract.decision === 'acepta' ? 'text-green-700' : 'text-red-700'">
                                    · {{ contract.decision === 'acepta' ? 'Sí acepta' : 'No acepta' }}
                                </span>
                            </template>
                            <template v-else-if="contract.status === 'anulado'">
                                {{ contract.void_reason || 'Anulado' }}
                            </template>
                            <template v-else-if="contract.sent_at">
                                Enviado por {{ contract.sent_via }} el {{ contract.sent_at }}
                                <template v-if="contract.opened_at"> · abierto el {{ contract.opened_at }}</template>
                                · vence {{ contract.link_expires_at }}
                            </template>
                            <template v-else>Listo para firmar</template>
                        </td>
                        <td class="whitespace-nowrap py-3 text-right text-sm">
                            <a :href="route('contract-signatures.pdf', contract.id)" target="_blank" class="text-indigo-600 hover:text-indigo-900">
                                {{ contract.status === 'firmado' ? 'PDF' : 'Vista previa' }}
                            </a>
                            <template v-if="contract.has_id_photos">
                                <a :href="route('contract-signatures.id-photo', [contract.id, 'anverso'])" target="_blank" class="ml-3 text-gray-600 hover:text-gray-900">Documento</a>
                                <a v-if="contract.has_id_back" :href="route('contract-signatures.id-photo', [contract.id, 'reverso'])" target="_blank" class="ml-2 text-gray-600 hover:text-gray-900">(reverso)</a>
                            </template>
                            <button v-if="contract.status !== 'anulado'" type="button" class="ml-3 text-red-600 hover:text-red-900" @click="voidContract(contract)">
                                Anular
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-else-if="!contracts.pendingTemplates.length" class="mt-4 text-sm text-gray-500">
            No hay plantillas de contrato publicadas que apliquen a esta matrícula.
        </p>
    </div>
</template>
