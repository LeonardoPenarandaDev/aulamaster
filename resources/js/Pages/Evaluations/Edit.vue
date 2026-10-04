<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    evaluation: Object,
    levels: Array,
});

const form = useForm({
    level_id: props.evaluation.level_id,
    name: props.evaluation.name,
    competency: props.evaluation.competency,
    minimum_grade: props.evaluation.minimum_grade,
    status: props.evaluation.status,
});

function submit() {
    form.put(route('evaluations.update', props.evaluation.id));
}
</script>

<template>
    <Head title="Editar evaluación" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar evaluación
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="level_id" value="Nivel" />
                            <select id="level_id" v-model="form.level_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="level in levels" :key="level.id" :value="level.id">{{ level.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.level_id" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="name" value="Nombre" />
                                <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required autofocus />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="competency" value="Competencia" />
                                <TextInput id="competency" v-model="form.competency" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.competency" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="minimum_grade" value="Nota mínima" />
                                <TextInput id="minimum_grade" type="number" step="0.1" v-model="form.minimum_grade" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.minimum_grade" />
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

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('evaluations.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
