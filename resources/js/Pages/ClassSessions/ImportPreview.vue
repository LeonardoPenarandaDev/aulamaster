<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    token: String,
    week: String,
    repeatWeeks: Number,
    rows: Array,
    summary: Object,
});

const filter = ref('todas');
const visibleRows = computed(() => (filter.value === 'todas' ? props.rows : props.rows.filter((row) => row.status === filter.value)));

const form = useForm({ token: props.token });

function confirm() {
    form.post(route('class-sessions.import.store'));
}

const statusStyles = {
    ok: { label: 'Correcta', class: 'bg-green-100 text-green-800' },
    sin_docente: { label: 'Sin docente', class: 'bg-amber-100 text-amber-800' },
    error: { label: 'Con errores', class: 'bg-red-100 text-red-700' },
};

function longDate(value) {
    const [year, month, day] = value.split('-').map(Number);
    return new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(year, month - 1, day));
}
</script>

<template>
    <Head title="Vista previa de horarios" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Vista previa de la importación</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-screen-2xl space-y-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-600">
                    Semana del {{ longDate(week) }}<template v-if="repeatWeeks > 1">, repetida durante {{ repeatWeeks }} semanas</template>.
                    Todavía no se ha guardado nada.
                </p>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <button type="button" class="rounded-xl border bg-white p-4 text-left" :class="filter === 'ok' ? 'border-green-500' : 'border-gray-200'" @click="filter = filter === 'ok' ? 'todas' : 'ok'">
                        <p class="text-2xl font-semibold text-green-700">{{ summary.ok }}</p>
                        <p class="text-sm text-gray-600">filas correctas</p>
                    </button>
                    <button type="button" class="rounded-xl border bg-white p-4 text-left" :class="filter === 'sin_docente' ? 'border-amber-500' : 'border-gray-200'" @click="filter = filter === 'sin_docente' ? 'todas' : 'sin_docente'">
                        <p class="text-2xl font-semibold text-amber-700">{{ summary.sin_docente }}</p>
                        <p class="text-sm text-gray-600">sin docente</p>
                    </button>
                    <button type="button" class="rounded-xl border bg-white p-4 text-left" :class="filter === 'error' ? 'border-red-500' : 'border-gray-200'" @click="filter = filter === 'error' ? 'todas' : 'error'">
                        <p class="text-2xl font-semibold text-red-700">{{ summary.error }}</p>
                        <p class="text-sm text-gray-600">con errores (no se crean)</p>
                    </button>
                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                        <p class="text-2xl font-semibold text-gray-900">{{ summary.sessions }}</p>
                        <p class="text-sm text-gray-600">clases a crear</p>
                    </div>
                </div>

                <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3 font-medium">Fila</th>
                                <th class="px-4 py-3 font-medium">Estado</th>
                                <th class="px-4 py-3 font-medium">Día y hora</th>
                                <th class="px-4 py-3 font-medium">Nivel</th>
                                <th class="px-4 py-3 font-medium">Aula</th>
                                <th class="px-4 py-3 font-medium">Docente</th>
                                <th class="px-4 py-3 font-medium">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="row in visibleRows" :key="row.line">
                                <td class="px-4 py-3 text-gray-500">{{ row.line }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusStyles[row.status].class">{{ statusStyles[row.status].label }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 capitalize text-gray-900">{{ row.raw.dia }} {{ row.data?.start_time ?? row.raw.hora_inicio }}–{{ row.data?.end_time ?? row.raw.hora_fin }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ row.data?.level ?? row.raw.nivel }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ row.data?.classroom ?? row.raw.aula }} <span v-if="row.data?.modality === 'virtual'" class="text-xs text-gray-500">(virtual)</span></td>
                                <td class="px-4 py-3 text-gray-700">{{ row.data ? (row.data.teacher ?? '—') : row.raw.docente }}</td>
                                <td class="px-4 py-3 text-xs">
                                    <ul v-if="row.errors.length" class="list-disc space-y-0.5 pl-4 text-red-700">
                                        <li v-for="error in row.errors" :key="error">{{ error }}</li>
                                    </ul>
                                    <span v-else class="text-gray-500">{{ row.dates.length }} clase(s)</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <PrimaryButton :disabled="form.processing || summary.sessions === 0" @click="confirm">
                        Crear {{ summary.sessions }} clases
                    </PrimaryButton>
                    <a v-if="summary.error" :href="route('class-sessions.import.errors', token)" class="text-sm font-medium text-red-700 hover:text-red-900">
                        Descargar filas con error
                    </a>
                    <Link :href="route('class-sessions.import.create')" class="text-sm text-gray-600 hover:text-gray-900">Subir otro archivo</Link>
                </div>
                <p v-if="summary.sin_docente" class="text-xs text-gray-500">
                    Las clases sin docente aparecen así en el calendario; si a 48 horas todavía no tienen docente, se avisa al admin y al coordinador.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
