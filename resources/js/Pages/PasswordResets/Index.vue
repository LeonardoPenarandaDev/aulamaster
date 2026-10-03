<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

const page = usePage();
const search = ref(props.filters.search ?? '');
const resettingId = ref(null);

function applySearch() {
    router.get(
        route('password-resets.index'),
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function resetPassword(user) {
    if (!confirm(`¿Restablecer la contraseña de ${user.name}? Su contraseña actual dejará de funcionar.`)) {
        return;
    }

    router.post(route('password-resets.store', user.id), {}, {
        preserveScroll: true,
        onStart: () => (resettingId.value = user.id),
        onFinish: () => (resettingId.value = null),
    });
}
</script>

<template>
    <Head title="Restablecer contraseñas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Restablecer contraseñas
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-screen-2xl space-y-4 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-md bg-green-50 p-4 text-sm text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <p class="text-sm text-gray-500">
                    Se genera una contraseña temporal que se muestra una sola vez. Compártela con la persona: al iniciar sesión
                    deberá cambiarla por una propia.
                </p>

                <TextInput
                    v-model="search"
                    type="text"
                    placeholder="Buscar por nombre o correo..."
                    class="w-full max-w-sm"
                    @keyup.enter="applySearch"
                />

                <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Correo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rol</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="user in users.data" :key="user.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ user.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ user.email }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ user.role ?? '—' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span
                                        v-if="user.must_change_password"
                                        class="rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800"
                                    >
                                        contraseña temporal
                                    </span>
                                    <span
                                        v-else
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                    >
                                        {{ user.is_active ? 'activo' : 'inactivo' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <button
                                        type="button"
                                        class="text-indigo-600 hover:text-indigo-900 disabled:opacity-50"
                                        :disabled="resettingId === user.id"
                                        @click="resetPassword(user)"
                                    >
                                        Restablecer contraseña
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No se encontraron usuarios.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="users.links.length > 3" class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in users.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        class="rounded-md border px-3 py-1 text-sm"
                        :class="[
                            link.active ? 'border-indigo-500 bg-indigo-50 text-indigo-600' : 'border-gray-200 text-gray-600',
                            !link.url ? 'pointer-events-none opacity-50' : '',
                        ]"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
