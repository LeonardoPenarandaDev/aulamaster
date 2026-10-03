<script setup>
import IdPhotoInput from '@/Components/Contracts/IdPhotoInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SignaturePad from '@/Components/Contracts/SignaturePad.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue';

/**
 * "Firmar aquí" en el computador de la oficina (parte 6.7 del plan de
 * mejoras). Los contratos se firman seguidos sin salir de pantalla completa.
 */
const props = defineProps({
    enrollment: Object,
    contract: Object,
    progress: Object,
    signer: Object,
    canReusePhotos: Boolean,
    maxPhotoKb: Number,
});

const page = usePage();
const pad = ref(null);
const isFullscreen = ref(Boolean(document.fullscreenElement));

function newForm() {
    return {
        signature_png: null,
        decision: props.contract.is_optional ? null : 'acepta',
        signer_name: props.signer.name ?? '',
        signer_document: props.signer.document ?? '',
        signer_role: props.signer.role,
        signer_email: props.signer.email ?? '',
        identity_verified: props.canReusePhotos,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        id_front: null,
        id_back: null,
    };
}

const form = useForm(newForm());

watch(() => props.contract.id, () => {
    form.defaults(newForm());
    form.reset();
    form.clearErrors();
    pad.value?.clear();
    window.scrollTo({ top: 0 });
});

const position = computed(() => props.progress.signed + 1);
const total = computed(() => props.progress.signed + props.progress.remaining);

function submit() {
    form.signature_png = pad.value?.toDataURL() ?? null;

    if (!form.signature_png) {
        form.setError('signature_png', 'Falta la firma.');
        return;
    }

    form.post(route('contract-signatures.sign', props.contract.id), {
        forceFormData: true,
        preserveScroll: true,
    });
}

async function toggleFullscreen() {
    if (document.fullscreenElement) {
        await document.exitFullscreen();
    } else {
        await document.documentElement.requestFullscreen?.();
    }
}

function onFullscreenChange() {
    isFullscreen.value = Boolean(document.fullscreenElement);
}

onMounted(() => document.addEventListener('fullscreenchange', onFullscreenChange));
onBeforeUnmount(() => document.removeEventListener('fullscreenchange', onFullscreenChange));
</script>

<template>
    <Head :title="`Firmar: ${contract.name}`" />

    <div class="min-h-screen bg-gray-100">
        <header class="sticky top-0 z-10 border-b border-gray-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Contrato {{ position }} de {{ total }} · {{ enrollment.student }} · {{ enrollment.level }}</p>
                    <h1 class="text-lg font-semibold text-gray-900">{{ contract.name }} <span class="text-sm font-normal text-gray-400">v{{ contract.version }}</span></h1>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50" @click="toggleFullscreen">
                        {{ isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa' }}
                    </button>
                    <Link :href="route('enrollments.edit', enrollment.id)" class="text-sm text-gray-600 underline hover:text-gray-900">Volver a la matrícula</Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl space-y-6 px-4 py-6">
            <div v-if="page.props.flash?.success" class="rounded-md bg-green-50 p-3 text-sm text-green-700">
                {{ page.props.flash.success }}
            </div>

            <article class="rounded-2xl border border-gray-200 bg-white p-6 text-[15px] leading-relaxed text-gray-800 sm:p-10 [&_p]:mb-4" v-html="contract.body_html" />

            <form class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6" @submit.prevent="submit">
                <div v-if="contract.is_optional">
                    <p class="text-sm font-medium text-gray-900">¿El firmante acepta?</p>
                    <div class="mt-2 flex gap-3">
                        <label
                            v-for="option in [{ value: 'acepta', label: 'Sí, acepto' }, { value: 'no_acepta', label: 'No acepto' }]"
                            :key="option.value"
                            class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-sm"
                            :class="form.decision === option.value ? 'border-indigo-600 bg-indigo-50 text-indigo-800' : 'border-gray-300 text-gray-700'"
                        >
                            <input v-model="form.decision" type="radio" :value="option.value" class="text-indigo-600 focus:ring-indigo-500" />
                            {{ option.label }}
                        </label>
                    </div>
                    <InputError class="mt-2" :message="form.errors.decision" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel for="signer_name" :value="signer.role === 'acudiente' ? 'Nombre del acudiente' : 'Nombre del estudiante'" />
                        <TextInput id="signer_name" v-model="form.signer_name" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.signer_name" />
                    </div>
                    <div>
                        <InputLabel for="signer_document" value="Documento" />
                        <TextInput id="signer_document" v-model="form.signer_document" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.signer_document" />
                    </div>
                    <div>
                        <InputLabel for="signer_email" value="Correo (opcional)" />
                        <TextInput id="signer_email" type="email" v-model="form.signer_email" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.signer_email" />
                    </div>
                </div>
                <p v-if="signer.role === 'acudiente'" class="-mt-2 text-xs text-amber-700">
                    El estudiante es menor de edad: firma solo el acudiente.
                </p>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p v-if="canReusePhotos" class="text-sm text-gray-600">
                        Se usará la foto del documento tomada en el contrato anterior. Si firma otra persona, toma una foto nueva.
                    </p>
                    <div class="mt-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <IdPhotoInput v-model="form.id_front" :label="canReusePhotos ? 'Documento (anverso) · opcional' : 'Documento de identidad (anverso)'" :max-kb="maxPhotoKb" />
                        <IdPhotoInput v-model="form.id_back" label="Documento (reverso) · opcional" :max-kb="maxPhotoKb" />
                    </div>
                    <InputError class="mt-2" :message="form.errors.id_front || form.errors.id_back" />
                    <p class="mt-2 text-xs text-gray-500">La foto se guarda cifrada, no aparece en el PDF y solo la ven el admin y la secretaria.</p>

                    <label class="mt-4 flex items-start gap-2 text-sm text-gray-800">
                        <input v-model="form.identity_verified" type="checkbox" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        Verifiqué el documento de identidad original de quien firma.
                    </label>
                    <InputError class="mt-2" :message="form.errors.identity_verified" />
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-900">Firma</p>
                    <SignaturePad ref="pad" class="mt-2" :height="240" />
                    <InputError class="mt-2" :message="form.errors.signature_png || form.errors.signature" />
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <PrimaryButton class="px-6 py-3" :disabled="form.processing">
                        {{ progress.remaining > 1 ? 'Firmar y seguir con el siguiente' : 'Firmar y terminar' }}
                    </PrimaryButton>
                    <span v-if="form.progress" class="text-sm text-gray-500">Subiendo… {{ form.progress.percentage }}%</span>
                </div>
            </form>
        </main>
    </div>
</template>
