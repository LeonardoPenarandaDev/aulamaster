<script setup>
import IdPhotoInput from '@/Components/Contracts/IdPhotoInput.vue';
import InputError from '@/Components/InputError.vue';
import InstitutionLogo from '@/Components/InstitutionLogo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SignaturePad from '@/Components/Contracts/SignaturePad.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

/**
 * Firma a distancia sin cuenta (parte 6.8 del plan de mejoras): leer hasta
 * el final, marcar "He leído y acepto", confirmar el código del correo y
 * firmar con el dedo.
 */
const props = defineProps({
    enrollmentId: Number,
    student: String,
    level: String,
    signer: Object,
    contracts: Array,
    signedCount: Number,
    otpVerified: Boolean,
    idPhotoRequired: Boolean,
    maxPhotoKb: Number,
});

const page = usePage();
const current = computed(() => props.contracts[0] ?? null);
const total = computed(() => props.signedCount + props.contracts.length);

const readToEnd = ref(false);
const pad = ref(null);

const codeForm = useForm({ code: '' });
const requestCodeForm = useForm({});

function requestCode() {
    requestCodeForm.post(route('contracts.remote.code', props.enrollmentId), { preserveScroll: true });
}

function verifyCode() {
    codeForm.post(route('contracts.remote.code', props.enrollmentId), {
        preserveScroll: true,
        onSuccess: () => codeForm.reset(),
    });
}

function newSignForm() {
    return {
        signature_png: null,
        accepted_terms: false,
        decision: null,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        id_front: null,
        id_back: null,
    };
}

const signForm = useForm(newSignForm());

watch(() => current.value?.id, () => {
    signForm.defaults(newSignForm());
    signForm.reset();
    signForm.clearErrors();
    readToEnd.value = false;
    pad.value?.clear();
    window.scrollTo({ top: 0 });
});

function onScroll(event) {
    const element = event.target;
    if (element.scrollTop + element.clientHeight >= element.scrollHeight - 24) {
        readToEnd.value = true;
    }
}

function checkShortContract(element) {
    if (element && element.scrollHeight <= element.clientHeight + 24) {
        readToEnd.value = true;
    }
}

function sign() {
    signForm.signature_png = pad.value?.toDataURL() ?? null;

    if (!signForm.signature_png) {
        signForm.setError('signature_png', 'Falta tu firma.');
        return;
    }

    signForm.post(route('contracts.remote.sign', [props.enrollmentId, current.value.id]), {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Firmar contratos" />

    <div class="min-h-screen bg-gradient-to-b from-indigo-50 via-white to-white pb-16">
        <header class="mx-auto flex max-w-3xl items-center gap-3 px-4 pt-6">
            <InstitutionLogo />
            <span class="font-semibold text-gray-800">{{ $page.props.institution.name }}</span>
        </header>

        <main class="mx-auto mt-6 max-w-3xl space-y-5 px-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Contratos de matrícula</h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ student }} · {{ level }}.
                    <template v-if="signer.role === 'acudiente'">Como acudiente, tú firmas por el estudiante.</template>
                </p>
            </div>

            <div v-if="page.props.flash?.success" class="rounded-md bg-green-50 p-3 text-sm text-green-700">{{ page.props.flash.success }}</div>

            <div v-if="!current" class="rounded-2xl border border-green-200 bg-white p-6 text-center">
                <p class="text-lg font-semibold text-green-700">¡Listo! Firmaste todos los contratos.</p>
                <p class="mt-2 text-sm text-gray-600">Te enviamos una copia en PDF de cada uno a tu correo. Ya puedes cerrar esta página.</p>
            </div>

            <template v-else>
                <p class="text-sm font-medium text-gray-700">Contrato {{ signedCount + 1 }} de {{ total }}: {{ current.name }}</p>

                <article
                    :ref="checkShortContract"
                    class="max-h-[60vh] overflow-y-auto rounded-2xl border border-gray-200 bg-white p-5 text-[15px] leading-relaxed text-gray-800 [&_p]:mb-4"
                    @scroll="onScroll"
                    v-html="current.body_html"
                />
                <p v-if="!readToEnd" class="text-center text-xs text-gray-500">Desplázate hasta el final del contrato para continuar.</p>

                <section v-if="!otpVerified" class="space-y-3 rounded-2xl border border-gray-200 bg-white p-5">
                    <h2 class="text-sm font-semibold text-gray-900">Verifica tu correo</h2>
                    <p class="text-sm text-gray-600">Te enviaremos un código a <strong>{{ signer.email_hint }}</strong> para confirmar que eres tú.</p>
                    <button type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 disabled:opacity-50" :disabled="requestCodeForm.processing" @click="requestCode">
                        Enviarme el código
                    </button>
                    <form class="flex flex-wrap items-start gap-2" @submit.prevent="verifyCode">
                        <TextInput v-model="codeForm.code" inputmode="numeric" maxlength="6" placeholder="123456" class="w-36 text-center text-lg tracking-widest" />
                        <PrimaryButton :disabled="codeForm.processing || codeForm.code.length < 6">Confirmar</PrimaryButton>
                    </form>
                    <InputError :message="codeForm.errors.code || page.props.errors?.code" />
                </section>

                <form v-else class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5" @submit.prevent="sign">
                    <div v-if="current.is_optional">
                        <p class="text-sm font-medium text-gray-900">¿Aceptas?</p>
                        <div class="mt-2 flex gap-3">
                            <label
                                v-for="option in [{ value: 'acepta', label: 'Sí, acepto' }, { value: 'no_acepta', label: 'No acepto' }]"
                                :key="option.value"
                                class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border px-4 py-3 text-sm"
                                :class="signForm.decision === option.value ? 'border-indigo-600 bg-indigo-50 text-indigo-800' : 'border-gray-300 text-gray-700'"
                            >
                                <input v-model="signForm.decision" type="radio" :value="option.value" class="text-indigo-600 focus:ring-indigo-500" />
                                {{ option.label }}
                            </label>
                        </div>
                        <InputError class="mt-2" :message="signForm.errors.decision" />
                    </div>

                    <label class="flex items-start gap-2 text-sm text-gray-800" :class="{ 'opacity-50': !readToEnd }">
                        <input v-model="signForm.accepted_terms" type="checkbox" :disabled="!readToEnd" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        He leído y acepto el contrato.
                    </label>
                    <InputError :message="signForm.errors.accepted_terms" />

                    <div>
                        <p class="text-sm font-medium text-gray-900">Firma con el dedo o el mouse</p>
                        <SignaturePad ref="pad" class="mt-2" :height="200" />
                        <InputError class="mt-2" :message="signForm.errors.signature_png || page.props.errors?.signature" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <IdPhotoInput v-model="signForm.id_front" :label="idPhotoRequired ? 'Foto de tu documento (anverso)' : 'Foto de tu documento (anverso) · opcional'" :max-kb="maxPhotoKb" />
                        <IdPhotoInput v-model="signForm.id_back" label="Reverso · opcional" :max-kb="maxPhotoKb" />
                    </div>
                    <InputError :message="signForm.errors.id_front || signForm.errors.id_back" />

                    <PrimaryButton class="w-full justify-center py-3" :disabled="signForm.processing || !readToEnd || !signForm.accepted_terms">
                        Firmar
                    </PrimaryButton>
                    <p class="text-center text-xs text-gray-500">
                        Firmado como {{ signer.name }} · {{ signer.document }}. Guardaremos la fecha, la hora y la dirección IP como evidencia de la firma.
                    </p>
                </form>
            </template>
        </main>
    </div>
</template>
