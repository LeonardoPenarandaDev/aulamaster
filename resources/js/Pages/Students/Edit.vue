<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import IdPhotoInput from '@/Components/Contracts/IdPhotoInput.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import StudentGuardianFields from '@/Components/StudentGuardianFields.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    student: Object,
    documentTypes: Object,
    signedContracts: Array,
    canImpersonate: { type: Boolean, default: false },
    checklist: { type: Object, default: () => ({ done: 0, total: 0, complete: false, items: [] }) },
});

const page = usePage();
const isAdmin = page.props.auth.roles?.includes('admin');

const form = useForm({
    code: props.student.code,
    name: props.student.name,
    document_type: props.student.document_type,
    document: props.student.document,
    birth_date: props.student.birth_date,
    email: props.student.email,
    phone: props.student.phone,
    address: props.student.address,
    status: props.student.status,
    guardian_name: props.student.guardian_name,
    guardian_document_type: props.student.guardian_document_type,
    guardian_document: props.student.guardian_document,
    guardian_relationship: props.student.guardian_relationship,
    guardian_email: props.student.guardian_email,
    guardian_phone: props.student.guardian_phone,
});

function submit() {
    form.put(route('students.update', props.student.id));
}

const photoForm = useForm({ photo: null });

function savePhoto() {
    photoForm.post(route('students.photo.update', props.student.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => photoForm.reset(),
    });
}

function removePhoto() {
    if (confirm('¿Eliminar la foto de perfil?')) {
        router.delete(route('students.photo.destroy', props.student.id), { preserveScroll: true });
    }
}

const checklistProcessing = ref(null);

/**
 * Marca o desmarca un requisito manual del checklist del estudiante.
 */
function toggleChecklistItem(item) {
    const options = { preserveScroll: true, onFinish: () => (checklistProcessing.value = null) };
    checklistProcessing.value = item.id;

    if (item.done) {
        if (!confirm(`¿Desmarcar «${item.name}»?`)) {
            checklistProcessing.value = null;

            return;
        }

        router.delete(route('students.checklist.uncheck', [props.student.id, item.id]), options);
    } else {
        router.post(route('students.checklist.check', [props.student.id, item.id]), {}, options);
    }
}

const accessForm = useForm({});
const impersonateForm = useForm({});

function impersonate() {
    if (confirm(`¿Entrar a la cuenta de ${props.student.name}? Verás la plataforma como el estudiante hasta que pulses "Volver a mi cuenta".`)) {
        impersonateForm.post(route('students.impersonate', props.student.id));
    }
}

function grantAccess() {
    accessForm.post(route('students.portal-access.store', props.student.id));
}
</script>

<template>
    <Head title="Editar estudiante" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar estudiante
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6 flex flex-wrap items-center gap-5 rounded-2xl border border-slate-200/80 bg-white p-6">
                    <StudentAvatar :name="student.name" :photo-url="student.photo_url" size="xl" />
                    <div class="min-w-0 flex-1 space-y-3">
                        <div>
                            <p class="text-lg font-semibold text-slate-900">{{ student.name }}</p>
                            <p class="text-sm text-slate-500">Código {{ student.code }}</p>
                        </div>
                        <IdPhotoInput v-model="photoForm.photo" :label="student.photo_url ? 'Cambiar foto de perfil' : 'Foto de perfil'" :max-kb="8192" facing-mode="user" />
                        <InputError :message="photoForm.errors.photo" />
                        <div class="flex flex-wrap items-center gap-3">
                            <PrimaryButton type="button" :disabled="!photoForm.photo || photoForm.processing" @click="savePhoto">Guardar foto</PrimaryButton>
                            <button v-if="student.photo_url" type="button" class="text-sm text-red-600 hover:text-red-800" @click="removePhoto">Eliminar foto</button>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="code" value="Código" />
                                <TextInput id="code" v-model="form.code" class="mt-1 block w-full" required autofocus />
                                <InputError class="mt-2" :message="form.errors.code" />
                            </div>

                            <div>
                                <InputLabel for="status" value="Estado" />
                                <select id="status" v-model="form.status" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="name" value="Nombre" />
                            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="document_type" value="Tipo de documento" />
                                <select id="document_type" v-model="form.document_type" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option :value="null">Sin especificar</option>
                                    <option v-for="(label, value) in documentTypes" :key="value" :value="value">{{ label }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.document_type" />
                            </div>

                            <div>
                                <InputLabel for="document" value="Documento" />
                                <TextInput id="document" v-model="form.document" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.document" />
                            </div>

                            <div>
                                <InputLabel for="birth_date" value="Fecha de nacimiento" />
                                <TextInput id="birth_date" type="date" v-model="form.birth_date" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.birth_date" />
                            </div>

                            <div>
                                <InputLabel for="email" value="Correo" />
                                <TextInput id="email" type="email" v-model="form.email" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div>
                                <InputLabel for="phone" value="Teléfono" />
                                <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.phone" />
                            </div>

                            <div>
                                <InputLabel for="address" value="Dirección" />
                                <TextInput id="address" v-model="form.address" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.address" />
                            </div>
                        </div>

                        <StudentGuardianFields :form="form" :document-types="documentTypes" />

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('students.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>

                <div v-if="checklist.total > 0" class="mt-6 rounded-2xl border border-slate-200/80 bg-white p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-medium text-gray-900">Checklist del alumno</h3>
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="checklist.complete ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                        >
                            {{ checklist.complete ? 'Completo' : `${checklist.done} de ${checklist.total}` }}
                        </span>
                    </div>
                    <ul class="mt-4 divide-y divide-slate-100">
                        <li v-for="item in checklist.items" :key="item.id" class="flex flex-wrap items-center gap-3 py-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                :class="item.done ? 'bg-emerald-500 text-white' : 'border-2 border-slate-300 text-transparent'"
                                aria-hidden="true"
                            >✓</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ item.name }}</p>
                                <p class="text-xs text-gray-500">
                                    <template v-if="item.automatic">Lo revisa la plataforma</template>
                                    <template v-else-if="item.done">Marcado por {{ item.checked_by ?? 'el personal' }} el {{ item.checked_at }}</template>
                                    <template v-else>Pendiente</template>
                                    <template v-if="item.description"> · {{ item.description }}</template>
                                </p>
                            </div>
                            <button
                                v-if="!item.automatic"
                                type="button"
                                class="rounded-lg border px-3 py-1.5 text-xs font-semibold disabled:opacity-50"
                                :class="item.done ? 'border-slate-200 text-slate-600 hover:bg-slate-50' : 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100'"
                                :disabled="checklistProcessing === item.id"
                                @click="toggleChecklistItem(item)"
                            >
                                {{ item.done ? 'Desmarcar' : 'Marcar como cumplido' }}
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200/80 bg-white p-6">
                    <h3 class="text-sm font-medium text-gray-900">Contratos firmados</h3>
                    <ul v-if="signedContracts.length" class="mt-3 divide-y divide-gray-100 text-sm">
                        <li v-for="contract in signedContracts" :key="contract.id" class="flex flex-wrap items-center justify-between gap-2 py-2">
                            <span class="text-gray-800">
                                {{ contract.name }} <span class="text-xs text-gray-400">v{{ contract.version }}</span>
                                <span v-if="contract.level" class="text-gray-500"> · {{ contract.level }}</span>
                                <span v-if="contract.decision" :class="contract.decision === 'acepta' ? 'text-green-700' : 'text-red-700'">
                                    · {{ contract.decision === 'acepta' ? 'Sí acepta' : 'No acepta' }}
                                </span>
                                <span class="block text-xs text-gray-500">{{ contract.signer_name }} ({{ contract.signer_role }}) · {{ contract.signed_at }}</span>
                            </span>
                            <a :href="route('contract-signatures.pdf', contract.id)" target="_blank" class="text-indigo-600 hover:text-indigo-900">PDF</a>
                        </li>
                    </ul>
                    <p v-else class="mt-2 text-sm text-gray-500">Todavía no tiene contratos firmados.</p>
                </div>

                <div v-if="isAdmin" class="mt-6 rounded-2xl border border-slate-200/80 bg-white p-6">
                    <h3 class="text-sm font-medium text-gray-900">Acceso al portal</h3>

                    <div
                        v-if="page.props.flash?.success"
                        class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50 p-3 text-sm text-emerald-700"
                    >
                        {{ page.props.flash.success }}
                    </div>

                    <div v-if="student.user" class="mt-2">
                        <p class="text-sm text-gray-600">
                            Ya tiene acceso con el usuario <strong>{{ student.user.email }}</strong>.
                        </p>
                        <template v-if="canImpersonate">
                            <p class="mt-2 text-sm text-gray-600">
                                Puedes entrar a su cuenta, sin su contraseña, para ver exactamente lo que el estudiante ve.
                                Queda registrado en la auditoría.
                            </p>
                            <SecondaryButton class="mt-3" :disabled="impersonateForm.processing" @click="impersonate">
                                Entrar como el estudiante
                            </SecondaryButton>
                        </template>
                    </div>
                    <div v-else class="mt-2">
                        <p class="text-sm text-gray-600">
                            Este estudiante todavía no puede iniciar sesión. Crear el acceso genera una cuenta con el correo
                            registrado ({{ student.email || 'no hay correo registrado' }}) y una contraseña temporal.
                        </p>
                        <PrimaryButton class="mt-3" :disabled="accessForm.processing || !student.email" @click="grantAccess">
                            Crear acceso
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
