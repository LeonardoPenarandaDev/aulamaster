<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    logs: Object,
    filters: Object,
    modules: Array,
    users: Array,
});

const filters = ref({
    module: props.filters.module ?? '',
    user_id: props.filters.user_id ?? '',
    action: props.filters.action ?? '',
    date: props.filters.date ?? '',
});

function applyFilters() {
    router.get(route('audit-logs.index'), filters.value, {
        preserveState: true,
        replace: true,
    });
}

const actionLabels = { creado: 'Creado', actualizado: 'Actualizado', eliminado: 'Eliminado' };
const actionClasses = {
    creado: 'bg-green-100 text-green-800',
    actualizado: 'bg-blue-100 text-blue-800',
    eliminado: 'bg-red-100 text-red-800',
};

const expanded = ref(null);

function toggle(logId) {
    expanded.value = expanded.value === logId ? null : logId;
}
</script>

<template>
    <Head title="Auditoría" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Auditoría
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Fecha</label>
                        <input type="date" v-model="filters.date" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Módulo</label>
                        <select v-model="filters.module" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option v-for="m in modules" :key="m" :value="m">{{ m }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Usuario</label>
                        <select v-model="filters.user_id" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Acción</label>
                        <select v-model="filters.action" @change="applyFilters" class="mt-1 rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todas</option>
                            <option v-for="(label, value) in actionLabels" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200/80 bg-white">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Fecha y hora</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Rol</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Módulo</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Acción</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Descripción</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">IP</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template v-for="log in logs.data" :key="log.id">
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ log.created_at?.replace('T', ' ').slice(0, 19) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ log.user_name ?? 'Sistema' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ log.role ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ log.module }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span class="rounded-full px-2 py-1 text-xs font-medium" :class="actionClasses[log.action]">
                                            {{ actionLabels[log.action] ?? log.action }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ log.description }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ log.ip_address ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <button
                                            v-if="log.old_values || log.new_values"
                                            type="button"
                                            class="text-indigo-600 hover:text-indigo-900"
                                            @click="toggle(log.id)"
                                        >
                                            {{ expanded === log.id ? 'Ocultar' : 'Ver valores' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="expanded === log.id">
                                    <td colspan="8" class="bg-gray-50 px-6 py-4">
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div v-if="log.old_values">
                                                <p class="mb-1 text-xs font-medium uppercase text-gray-500">Antes</p>
                                                <pre class="overflow-x-auto rounded-md bg-white p-3 text-xs text-gray-700">{{ JSON.stringify(log.old_values, null, 2) }}</pre>
                                            </div>
                                            <div v-if="log.new_values">
                                                <p class="mb-1 text-xs font-medium uppercase text-gray-500">Después</p>
                                                <pre class="overflow-x-auto rounded-md bg-white p-3 text-xs text-gray-700">{{ JSON.stringify(log.new_values, null, 2) }}</pre>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay registros de auditoría con estos filtros.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="logs.links.length > 3" class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in logs.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        class="rounded-lg border px-3 py-1.5 text-sm"
                        :class="[
                            link.active ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                            !link.url ? 'pointer-events-none opacity-50' : '',
                        ]"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
