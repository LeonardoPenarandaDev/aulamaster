<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    enrollment: Object,
    evaluations: Array,
    teachers: Array,
    missingRequirements: Array,
});

const page = usePage();
const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    evaluation_id: props.evaluations[0]?.id ?? '',
    teacher_id: props.teachers[0]?.id ?? '',
    evaluated_at: today,
    grade: '',
    notes: '',
    payment_confirmed: false,
});

function latestResult(evaluation) {
    return evaluation.results.length ? evaluation.results[evaluation.results.length - 1] : null;
}

const selectedEvaluation = computed(() => props.evaluations.find((e) => e.id === Number(form.evaluation_id)));
const selectedTerms = computed(() => selectedEvaluation.value?.next_attempt_terms);

function money(value) {
    return Number(value).toLocaleString('es-CO');
}

function submit() {
    form.post(route('enrollments.evaluation-results.store', props.enrollment.id));
}
</script>

<template>
    <Head title="Registrar evaluación" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Registrar evaluación
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-md bg-green-50 p-4 text-sm text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-600">
                        {{ enrollment.student?.name }} ({{ enrollment.student?.code }}) —
                        {{ enrollment.level?.course?.name }} {{ enrollment.level?.name }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600">
                        Horas: {{ enrollment.accumulated_hours }} / {{ enrollment.required_hours }}
                        ({{ enrollment.progress_percentage }}%)
                    </p>

                    <div v-if="missingRequirements.length === 0" class="mt-3 rounded-md bg-green-50 p-3 text-sm text-green-700">
                        ✓ Apto para presentar evaluaciones.
                    </div>
                    <div v-else class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700">
                        <p class="font-medium">NO APTO PARA PRESENTAR</p>
                        <p class="mt-1">Faltan:</p>
                        <ul class="list-inside list-disc">
                            <li v-for="item in missingRequirements" :key="item">{{ item }}</li>
                        </ul>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Evaluación</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Intentos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Último resultado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Próximo intento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="evaluation in evaluations" :key="evaluation.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ evaluation.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ evaluation.results.length }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span v-if="!latestResult(evaluation)" class="text-gray-400">Sin presentar</span>
                                    <span
                                        v-else
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="latestResult(evaluation).result === 'aprobado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    >
                                        {{ latestResult(evaluation).grade }} — {{ latestResult(evaluation).result }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    <span v-if="evaluation.next_attempt_terms.blocked" class="text-red-600">
                                        {{ evaluation.next_attempt_terms.block_reason }}
                                    </span>
                                    <span v-else-if="!evaluation.next_attempt_terms.is_recovery">Intento original</span>
                                    <span v-else-if="evaluation.next_attempt_terms.cost > 0">
                                        Recuperación paga — ${{ money(evaluation.next_attempt_terms.cost) }}
                                    </span>
                                    <span v-else>Recuperación gratuita</span>
                                </td>
                            </tr>
                            <tr v-if="evaluations.length === 0">
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Este nivel no tiene evaluaciones activas configuradas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="evaluations.length > 0" class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-sm font-medium text-gray-900">Registrar nuevo resultado</h3>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="evaluation_id" value="Evaluación" />
                            <select id="evaluation_id" v-model="form.evaluation_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="evaluation in evaluations" :key="evaluation.id" :value="evaluation.id">{{ evaluation.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.evaluation_id" />
                        </div>

                        <div>
                            <InputLabel for="teacher_id" value="Profesor evaluador" />
                            <select id="teacher_id" v-model="form.teacher_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.teacher_id" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="evaluated_at" value="Fecha" />
                                <TextInput id="evaluated_at" type="date" v-model="form.evaluated_at" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.evaluated_at" />
                            </div>

                            <div>
                                <InputLabel for="grade" value="Nota" />
                                <TextInput id="grade" type="number" step="0.1" v-model="form.grade" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.grade" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="notes" value="Observaciones" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>

                        <div v-if="selectedTerms?.blocked" class="rounded-md bg-red-50 p-3 text-sm text-red-700">
                            {{ selectedTerms.block_reason }}
                        </div>

                        <div v-else-if="selectedTerms?.cost > 0" class="rounded-md bg-yellow-50 p-3 text-sm text-yellow-800">
                            <label class="flex items-start gap-2">
                                <input type="checkbox" v-model="form.payment_confirmed" class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                <span>
                                    Esta recuperación tiene un costo de <strong>${{ money(selectedTerms.cost) }}</strong>.
                                    Confirmo que el pago fue realizado; al guardar se registrará el pago correspondiente.
                                </span>
                            </label>
                            <InputError class="mt-2" :message="form.errors.payment_confirmed" />
                        </div>

                        <InputError :message="form.errors.eligibility" />

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing || selectedTerms?.blocked">Guardar resultado</PrimaryButton>
                            <Link :href="route('evaluation-results.index')" class="text-sm text-gray-600 hover:text-gray-900">
                                Volver al listado
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
