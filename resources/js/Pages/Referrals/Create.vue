<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    students: Array,
});

const form = useForm({
    referrer_student_id: '',
    referred_student_id: '',
    referrer_discount: 50000,
    referred_discount: 25000,
});

function submit() {
    form.post(route('referrals.store'));
}
</script>

<template>
    <Head title="Nuevo referido" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Nuevo referido
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="referrer_student_id" value="Estudiante referente" />
                                <select id="referrer_student_id" v-model="form.referrer_student_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Selecciona...</option>
                                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.code }} - {{ student.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.referrer_student_id" />
                            </div>

                            <div>
                                <InputLabel for="referred_student_id" value="Estudiante nuevo (referido)" />
                                <select id="referred_student_id" v-model="form.referred_student_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Selecciona...</option>
                                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.code }} - {{ student.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.referred_student_id" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="referrer_discount" value="Beneficio para el referente" />
                                <TextInput id="referrer_discount" type="number" step="0.01" v-model="form.referrer_discount" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.referrer_discount" />
                            </div>

                            <div>
                                <InputLabel for="referred_discount" value="Beneficio para el nuevo estudiante" />
                                <TextInput id="referred_discount" type="number" step="0.01" v-model="form.referred_discount" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.referred_discount" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('referrals.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
