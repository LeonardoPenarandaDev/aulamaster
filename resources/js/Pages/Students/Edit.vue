<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StudentGuardianFields from '@/Components/StudentGuardianFields.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    student: Object,
    documentTypes: Object,
    signedContracts: Array,
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

const accessForm = useForm({});

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

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="code" value="Código" />
                                <TextInput id="code" v-model="form.code" class="mt-1 block w-full" required autofocus />
                                <InputError class="mt-2" :message="form.errors.code" />
                            </div>

                            <div>
                                <InputLabel for="status" value="Estado" />
                                <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                <select id="document_type" v-model="form.document_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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

                <div class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg">
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

                <div v-if="isAdmin" class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-sm font-medium text-gray-900">Acceso al portal</h3>

                    <div
                        v-if="page.props.flash?.success"
                        class="mt-3 rounded-md bg-green-50 p-3 text-sm text-green-700"
                    >
                        {{ page.props.flash.success }}
                    </div>

                    <p v-if="student.user" class="mt-2 text-sm text-gray-600">
                        Ya tiene acceso con el usuario <strong>{{ student.user.email }}</strong>.
                    </p>
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
