<script setup>
import Icon from '@/Components/Icon.vue';
import PortalButton from '@/Components/Portal/PortalButton.vue';
import PortalCard from '@/Components/Portal/PortalCard.vue';
import PortalEmptyState from '@/Components/Portal/PortalEmptyState.vue';
import PortalStat from '@/Components/Portal/PortalStat.vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Inicio del alumno (parte 9 del plan de mejoras): saludo, ruta de niveles,
 * progreso de horas, próxima clase con "Unirme" y estado de cuenta.
 */
const props = defineProps({
    studentData: { type: Object, required: true },
});

const page = usePage();
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);
const today = new Intl.DateTimeFormat('es-CO', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());
const data = computed(() => props.studentData);
const currentWeek = computed(() => data.value.weekly_summary?.find((week) => week.is_current) ?? null);

const accountStatus = {
    al_dia: { label: 'Al día', class: 'bg-emerald-100 text-emerald-800' },
    pendiente: { label: 'Pago pendiente', class: 'bg-amber-100 text-amber-800' },
    vencido: { label: 'Pago vencido', class: 'bg-rose-100 text-rose-800' },
};

function money(value) {
    return Number(value).toLocaleString('es-CO');
}

function hours(value) {
    return Number(value).toLocaleString('es-CO', { maximumFractionDigits: 1 });
}

function longDate(value) {
    return new Intl.DateTimeFormat('es-CO', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date(`${value}T00:00:00`));
}
</script>

<template>
    <Head title="Inicio" />

    <PortalLayout>
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <StudentAvatar :name="page.props.auth.user.name" :photo-url="page.props.auth.avatar_url" size="lg" />
                <div>
                    <p class="text-sm capitalize text-gray-600">{{ today }}</p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">Hola, {{ firstName }}</h1>
                    <p v-if="data.has_enrollment" class="mt-1 text-gray-600">{{ data.enrollment.course }} · {{ data.enrollment.level }}</p>
                </div>
            </div>

            <div v-if="data.pending_payments?.length" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm text-amber-900">
                    Tienes {{ data.pending_payments.length }} pago{{ data.pending_payments.length === 1 ? '' : 's' }} pendiente{{ data.pending_payments.length === 1 ? '' : 's' }}.
                </p>
                <PortalButton :href="route('payments.pay-online', data.pending_payments[0].id)" external size="sm">Pagar en línea</PortalButton>
            </div>

            <!-- Próxima clase -->
            <PortalCard v-if="data.has_enrollment" title="Próxima clase">
                <div v-if="data.next_session" class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                            <Icon :name="data.next_session.modality === 'virtual' ? 'video' : 'calendar'" class="h-6 w-6" />
                        </span>
                        <div>
                            <p class="font-semibold capitalize text-gray-900">{{ longDate(data.next_session.date) }}</p>
                            <p class="text-sm text-gray-600">
                                {{ data.next_session.start_time }} – {{ data.next_session.end_time }} ·
                                {{ data.next_session.modality === 'virtual' ? 'Clase virtual' : data.next_session.classroom }}
                            </p>
                        </div>
                    </div>
                    <PortalButton v-if="data.next_session.modality === 'virtual' && data.next_session.meeting_url" :href="data.next_session.meeting_url" external size="lg">
                        <Icon name="video" class="h-5 w-5" /> Unirme
                    </PortalButton>
                    <p v-else-if="data.next_session.modality === 'virtual'" class="text-sm text-gray-500">El enlace se publica el día de la clase.</p>
                </div>
                <p v-else class="text-sm text-gray-500">No hay clases programadas próximamente.</p>
            </PortalCard>

            <PortalEmptyState v-else icon="book" title="Todavía no tienes una matrícula activa" text="Cuando la institución active tu matrícula verás aquí tus clases y tu progreso." />

            <!-- Ruta de niveles -->
            <PortalCard v-if="data.level_path?.length > 1" title="Mi ruta de niveles">
                <ol class="flex flex-wrap items-center gap-2">
                    <template v-for="(step, index) in data.level_path" :key="step.id">
                        <li
                            class="flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm"
                            :class="{
                                'on-level-color': step.state !== 'proximo',
                                'font-semibold text-gray-900 ring-2 ring-indigo-600 ring-offset-2': step.state === 'actual',
                                'text-gray-700': step.state === 'aprobado',
                                'border border-dashed border-gray-300 text-gray-400': step.state === 'proximo',
                            }"
                            :style="step.state !== 'proximo' ? { backgroundColor: step.color } : {}"
                        >
                            <Icon v-if="step.state === 'aprobado'" name="check-circle" class="h-4 w-4 text-emerald-600" />
                            {{ step.name }}
                        </li>
                        <li v-if="index < data.level_path.length - 1" aria-hidden="true" class="text-gray-300">
                            <Icon name="chevron-right" class="h-4 w-4" />
                        </li>
                    </template>
                </ol>
            </PortalCard>

            <!-- Progreso -->
            <div v-if="data.has_enrollment" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <PortalStat
                    label="Horas del nivel"
                    :value="`${hours(data.enrollment.accumulated_hours)} / ${hours(data.enrollment.required_hours)}`"
                    icon="clock"
                    :progress="data.enrollment.progress_percentage"
                    :hint="`${data.enrollment.progress_percentage}% completado`"
                />
                <PortalStat
                    v-if="currentWeek"
                    label="Esta semana"
                    :value="`${hours(currentWeek.attended_hours)} / ${hours(currentWeek.target_hours)} h`"
                    icon="calendar"
                    :progress="currentWeek.target_hours > 0 ? (currentWeek.attended_hours / currentWeek.target_hours) * 100 : 0"
                    :hint="currentWeek.pending_hours === 0 ? 'Semana completa' : `Te faltan ${hours(currentWeek.pending_hours)} h`"
                />
                <PortalStat label="Evaluaciones" :value="`${data.evaluations.presented} / ${data.evaluations.total}`" icon="check-circle" hint="presentadas" />
            </div>

            <div v-if="data.has_enrollment && data.catch_up?.backlog_hours > 0" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200/80 bg-white p-4 text-sm">
                <p class="text-gray-700">
                    Tienes <strong class="text-amber-700">{{ hours(data.catch_up.backlog_hours) }} h por recuperar</strong>.
                    Esta semana necesitas {{ hours(data.catch_up.needed_this_week) }} h para ponerte al día.
                </p>
                <PortalButton :href="route('student-schedule.index')" variant="secondary" size="sm">Ver horarios</PortalButton>
            </div>

            <!-- Certificados -->
            <PortalCard v-if="data.certificates?.length" title="Mis certificados">
                <ul class="divide-y divide-gray-100">
                    <li v-for="certificate in data.certificates" :key="certificate.enrollment_id" class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                        <span class="text-gray-900">{{ certificate.course }} {{ certificate.level }}</span>
                        <a :href="route('student-certificates.download', certificate.enrollment_id)" class="font-semibold text-indigo-600 hover:text-indigo-800">Descargar PDF</a>
                    </li>
                </ul>
            </PortalCard>

            <!-- Estado de cuenta -->
            <PortalCard title="Estado de cuenta">
                <template #actions>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="accountStatus[data.account.status].class">
                        {{ accountStatus[data.account.status].label }}
                    </span>
                </template>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div><p class="text-gray-500">Facturado</p><p class="font-semibold tabular-nums text-gray-900">${{ money(data.account.total_billed) }}</p></div>
                    <div><p class="text-gray-500">Pagado</p><p class="font-semibold tabular-nums text-gray-900">${{ money(data.account.total_paid) }}</p></div>
                    <div><p class="text-gray-500">Saldo</p><p class="font-semibold tabular-nums text-gray-900">${{ money(data.account.balance) }}</p></div>
                </div>
                <ul v-if="data.pending_payments?.length" class="mt-5 space-y-2">
                    <li v-for="payment in data.pending_payments" :key="payment.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-gray-50 px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-gray-900">{{ payment.concept }}</p>
                            <p :class="payment.status === 'vencido' ? 'text-rose-600' : 'text-gray-500'">${{ money(payment.final_amount) }}<template v-if="payment.status === 'vencido'"> · vencido</template></p>
                        </div>
                        <PortalButton :href="route('payments.pay-online', payment.id)" external size="sm">Pagar</PortalButton>
                    </li>
                </ul>
            </PortalCard>
        </div>
    </PortalLayout>
</template>
