<script setup>
import Icon from '@/Components/Icon.vue';
import PortalButton from '@/Components/Portal/PortalButton.vue';
import PortalCard from '@/Components/Portal/PortalCard.vue';
import PortalEmptyState from '@/Components/Portal/PortalEmptyState.vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

/**
 * Inicio del docente (parte 9 del plan de mejoras): las clases de hoy con
 * accesos directos a tomar asistencia, subir material y poner el enlace
 * virtual.
 */
const props = defineProps({
    teacherData: { type: Object, required: true },
});

const page = usePage();
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);
const today = new Intl.DateTimeFormat('es-CO', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());

const meetingUrls = reactive(Object.fromEntries(props.teacherData.today_sessions.map((session) => [session.id, session.meeting_url ?? ''])));
const meetingUrlErrors = reactive({});

function saveMeetingUrl(session) {
    router.patch(route('class-sessions.meeting-url', session.id), { meeting_url: meetingUrls[session.id] || null }, {
        preserveScroll: true,
        onSuccess: () => delete meetingUrlErrors[session.id],
        onError: (errors) => (meetingUrlErrors[session.id] = errors.meeting_url),
    });
}

function shortDate(value) {
    return new Intl.DateTimeFormat('es-CO', { weekday: 'short', day: 'numeric', month: 'short' }).format(new Date(`${value}T00:00:00`));
}
</script>

<template>
    <Head title="Inicio" />

    <PortalLayout>
        <div class="space-y-6">
            <div>
                <p class="text-sm capitalize text-gray-600">{{ today }}</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">Hola, {{ firstName }}</h1>
                <p v-if="teacherData.my_levels.length" class="mt-1 text-gray-600">{{ teacherData.my_levels.join(' · ') }}</p>
            </div>

            <div v-if="page.props.flash?.success" class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ page.props.flash.success }}</div>

            <section class="space-y-3">
                <h2 class="text-base font-semibold text-gray-900">Clases de hoy</h2>

                <PortalCard v-for="session in teacherData.today_sessions" :key="session.id">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <span class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                                <span class="text-sm font-bold leading-none">{{ session.start_time }}</span>
                            </span>
                            <div>
                                <p class="font-semibold text-gray-900">{{ session.level }}</p>
                                <p class="text-sm text-gray-600">
                                    {{ session.start_time }} – {{ session.end_time }} ·
                                    {{ session.modality === 'virtual' ? 'Virtual' : session.classroom }} ·
                                    {{ session.enrolled_count }} estudiantes
                                </p>
                                <p v-if="session.attendance_taken" class="mt-1 text-sm">
                                    <span class="font-medium text-emerald-700">{{ session.present_count }} presentes</span>
                                    <span class="text-gray-400"> · </span>
                                    <span class="font-medium text-rose-700">{{ session.absent_count }} ausentes</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <form v-if="session.modality === 'virtual'" class="mt-4 flex flex-wrap items-center gap-2" @submit.prevent="saveMeetingUrl(session)">
                        <Icon name="link" class="h-4 w-4 text-gray-400" />
                        <input
                            v-model="meetingUrls[session.id]"
                            type="url"
                            placeholder="Pega aquí el enlace de Meet"
                            class="min-w-0 flex-1 rounded-xl border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <PortalButton type="submit" variant="secondary" size="sm">{{ session.meeting_url ? 'Actualizar' : 'Publicar enlace' }}</PortalButton>
                    </form>
                    <p v-if="meetingUrlErrors[session.id]" class="mt-1 text-sm text-red-600">{{ meetingUrlErrors[session.id] }}</p>

                    <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <PortalButton :href="route('attendance.create', session.id)" size="lg">
                            <Icon name="check-circle" class="h-5 w-5" />
                            {{ session.attendance_taken ? 'Ver asistencia' : 'Tomar asistencia' }}
                        </PortalButton>
                        <PortalButton :href="route('class-materials.index', session.id)" variant="secondary" size="lg">
                            <Icon name="folder" class="h-5 w-5" />
                            Material ({{ session.materials_count }})
                        </PortalButton>
                    </div>
                </PortalCard>

                <PortalEmptyState v-if="teacherData.today_sessions.length === 0" icon="calendar" title="Hoy no tienes clases" text="Revisa tu calendario para ver las próximas.">
                    <PortalButton :href="route('calendar.index')" variant="secondary" size="sm">Abrir calendario</PortalButton>
                </PortalEmptyState>
            </section>

            <PortalCard v-if="teacherData.recent_sessions?.length" title="Clases recientes" subtitle="Comparte material de repaso después de la clase.">
                <ul class="divide-y divide-gray-100">
                    <li v-for="session in teacherData.recent_sessions" :key="session.id" class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                        <div>
                            <p class="font-medium text-gray-900">{{ session.level }}</p>
                            <p class="capitalize text-gray-500">{{ shortDate(session.date) }} · {{ session.start_time }}–{{ session.end_time }}</p>
                        </div>
                        <Link :href="route('class-materials.index', session.id)" class="font-semibold text-indigo-600 hover:text-indigo-800">
                            {{ session.materials_count > 0 ? `Material (${session.materials_count})` : 'Agregar material' }}
                        </Link>
                    </li>
                </ul>
            </PortalCard>
        </div>
    </PortalLayout>
</template>
