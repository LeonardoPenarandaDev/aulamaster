<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    students: Array,
    levels: Array,
    promotions: Array,
    referrals: Array,
});

const isAdmin = usePage().props.auth.roles?.includes('admin');
const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    student_id: props.students[0]?.id ?? '',
    level_id: props.levels[0]?.id ?? '',
    enrolled_at: today,
    start_date: today,
    estimated_end_date: '',
    status: 'activa',
    required_hours: '',
    weekly_hours: '',
    base_price: '',
    promotion_id: '',
    referral_id: '',
    monthly_fee: '',
    skip_prerequisite: false,
});

function applyLevelDefaults(levelId) {
    const level = props.levels.find((l) => l.id === Number(levelId));
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
}

watch(() => form.level_id, applyLevelDefaults, { immediate: true });

const selectedLevel = computed(() => props.levels.find((l) => l.id === Number(form.level_id)));
const selectedPromotion = computed(() => props.promotions.find((p) => p.id === Number(form.promotion_id)));
const selectedReferral = computed(() => props.referrals.find((r) => r.id === Number(form.referral_id)));

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
        referralDiscount = Number(selectedReferral.value.referred_student_id) === Number(form.student_id)
            ? Number(selectedReferral.value.referred_discount)
            : Number(selectedReferral.value.referrer_discount);
    }

    return {
        base,
        promotionDiscount,
        referralDiscount,
        final: Math.max(0, base - promotionDiscount - referralDiscount),
    };
});

function money(value) {
    return Number(value).toLocaleString('es-CO');
}

function submit() {
    form.post(route('enrollments.store'));
}
</script>

<template>
    <Head title="Nueva matrícula" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Nueva matrícula
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="student_id" value="Estudiante" />
                                <select id="student_id" v-model="form.student_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.code }} - {{ student.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.student_id" />
                            </div>

                            <div>
                                <InputLabel for="level_id" value="Curso / Nivel" />
                                <select id="level_id" v-model="form.level_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="level in levels" :key="level.id" :value="level.id">{{ level.course?.name }} {{ level.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.level_id" />
                                <p v-if="selectedLevel?.previous_level" class="mt-1 text-xs text-gray-500">
                                    Requiere haber aprobado {{ selectedLevel.previous_level.name }}.
                                </p>
                                <label v-if="isAdmin && selectedLevel?.previous_level" class="mt-2 flex items-start gap-2 text-sm text-amber-700">
                                    <input v-model="form.skip_prerequisite" type="checkbox" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    Omitir requisito (queda registrado en la auditoría)
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <InputLabel for="enrolled_at" value="Fecha de matrícula" />
                                <TextInput id="enrolled_at" type="date" v-model="form.enrolled_at" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.enrolled_at" />
                            </div>

                            <div>
                                <InputLabel for="start_date" value="Fecha de inicio" />
                                <TextInput id="start_date" type="date" v-model="form.start_date" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.start_date" />
                            </div>

                            <div>
                                <InputLabel for="estimated_end_date" value="Fecha estimada de fin" />
                                <TextInput id="estimated_end_date" type="date" v-model="form.estimated_end_date" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.estimated_end_date" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="status" value="Estado" />
                            <select id="status" v-model="form.status" class="mt-1 block w-full max-w-xs rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="pendiente">Pendiente</option>
                                <option value="activa">Activa</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="required_hours" value="Horas requeridas" />
                                <TextInput id="required_hours" type="number" step="0.5" v-model="form.required_hours" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.required_hours" />
                            </div>

                            <div>
                                <InputLabel for="weekly_hours" value="Intensidad horaria (horas/semana)" />
                                <TextInput id="weekly_hours" type="number" step="0.5" min="1" v-model="form.weekly_hours" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.weekly_hours" />
                            </div>
                        </div>
                        <p class="-mt-4 text-xs text-gray-500">
                            Tomados del nivel; ajústalos según la intensidad que contrató el estudiante. Las horas acumuladas se calculan
                            automáticamente a partir de la asistencia una vez que la matrícula quede guardada.
                        </p>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="promotion_id" value="Promoción" />
                                <select id="promotion_id" v-model="form.promotion_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Ninguna</option>
                                    <option v-for="promotion in promotions" :key="promotion.id" :value="promotion.id">{{ promotion.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.promotion_id" />
                            </div>

                            <div>
                                <InputLabel for="referral_id" value="Referido" />
                                <select id="referral_id" v-model="form.referral_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Ninguno</option>
                                    <option v-for="referral in referrals" :key="referral.id" :value="referral.id">
                                        {{ referral.referrer?.name }} → {{ referral.referred?.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.referral_id" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="base_price" value="Precio base" />
                            <TextInput id="base_price" type="number" step="1000" min="0" v-model="form.base_price" class="mt-1 block w-full max-w-xs" required />
                            <InputError class="mt-2" :message="form.errors.base_price" />
                            <p class="mt-1 text-xs text-gray-500">
                                Sugerido: precio del nivel (${{ money(selectedLevel?.price ?? 0) }}). Ajústalo según la intensidad horaria contratada.
                            </p>
                        </div>

                        <div>
                            <InputLabel for="monthly_fee" value="Mensualidad" />
                            <TextInput id="monthly_fee" type="number" step="1000" min="0" v-model="form.monthly_fee" class="mt-1 block w-full max-w-xs" />
                            <InputError class="mt-2" :message="form.errors.monthly_fee" />
                            <p class="mt-1 text-xs text-gray-500">Tomada del nivel; ajústala si este estudiante paga otro valor. Vacía: no se cobra mensualidad.</p>
                        </div>

                        <div class="rounded-md border border-gray-200 bg-gray-50 p-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Precio base</span>
                                <span class="text-gray-900">${{ money(pricePreview.base) }}</span>
                            </div>
                            <div v-if="pricePreview.promotionDiscount > 0" class="flex justify-between text-red-600">
                                <span>Promoción</span>
                                <span>-${{ money(pricePreview.promotionDiscount) }}</span>
                            </div>
                            <div v-if="pricePreview.referralDiscount > 0" class="flex justify-between text-red-600">
                                <span>Descuento por referido</span>
                                <span>-${{ money(pricePreview.referralDiscount) }}</span>
                            </div>
                            <div class="mt-2 flex justify-between border-t border-gray-300 pt-2 font-medium text-gray-900">
                                <span>Precio final</span>
                                <span>${{ money(pricePreview.final) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('enrollments.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
