<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import Icon from '@/Components/Icon.vue';
import InstitutionLogo from '@/Components/InstitutionLogo.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Layout del portal de alumnos y docentes (parte 9 del plan de mejoras):
 * barra superior blanca semitransparente, navegación inferior en el
 * celular y, para el alumno, el fondo con el color de su nivel.
 */
const page = usePage();
const isStudent = computed(() => page.props.auth.roles?.includes('estudiante'));

const navigation = computed(() => (isStudent.value
    ? [
        { label: 'Inicio', icon: 'home', route: 'dashboard', active: 'dashboard' },
        { label: 'Calendario', icon: 'calendar', route: 'calendar.index', active: 'calendar.*' },
        { label: 'Horarios', icon: 'clock', route: 'student-schedule.index', active: 'student-schedule.*' },
        { label: 'Material', icon: 'folder', route: 'student-materials.index', active: 'student-materials.*' },
        { label: 'Contratos', icon: 'document', route: 'student-contracts.index', active: 'student-contracts.*' },
    ]
    : [
        { label: 'Inicio', icon: 'home', route: 'dashboard', active: 'dashboard' },
        { label: 'Calendario', icon: 'calendar', route: 'calendar.index', active: 'calendar.*' },
        { label: 'Mi perfil', icon: 'user', route: 'profile.edit', active: 'profile.*' },
    ]));

const background = computed(() => {
    const color = isStudent.value ? page.props.studentTheme?.color : null;

    return color ? { backgroundImage: `linear-gradient(to bottom, ${color}, #f9fafb 420px)` } : null;
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 pb-24 sm:pb-10" :style="background">
        <header class="sticky top-0 z-40 border-b border-gray-200/60 bg-white/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6">
                <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-2">
                    <InstitutionLogo />
                    <span class="truncate text-base font-semibold text-gray-900">{{ page.props.institution.name }}</span>
                </Link>

                <nav class="hidden items-center gap-1 md:flex">
                    <Link
                        v-for="item in navigation"
                        :key="item.route"
                        :href="route(item.route)"
                        class="flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium transition"
                        :class="route().current(item.active) ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                    >
                        <Icon :name="item.icon" class="h-4 w-4" />
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="flex items-center gap-1">
                    <NotificationBell />
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white" :aria-label="page.props.auth.user.name">
                                {{ page.props.auth.user.name.charAt(0).toUpperCase() }}
                            </button>
                        </template>
                        <template #content>
                            <div class="border-b border-gray-100 px-4 py-2 text-xs text-gray-500">{{ page.props.auth.user.name }}</div>
                            <DropdownLink :href="route('profile.edit')">Mi perfil</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Cerrar sesión</DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 pt-6 sm:px-6 sm:pt-10">
            <div v-if="$slots.header" class="mb-6 [&_h2]:text-2xl [&_h2]:font-semibold [&_h2]:text-gray-900">
                <slot name="header" />
            </div>

            <div v-if="page.props.flash?.error" class="mb-4 rounded-2xl bg-red-50 p-4 text-sm text-red-700">{{ page.props.flash.error }}</div>

            <slot />
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/90 backdrop-blur md:hidden">
            <div class="mx-auto flex max-w-md justify-around px-2 pb-[env(safe-area-inset-bottom)]">
                <Link
                    v-for="item in navigation"
                    :key="item.route"
                    :href="route(item.route)"
                    class="flex flex-1 flex-col items-center gap-0.5 py-2 text-[11px] font-medium"
                    :class="route().current(item.active) ? 'text-indigo-600' : 'text-gray-500'"
                >
                    <Icon :name="item.icon" class="h-6 w-6" />
                    {{ item.label }}
                </Link>
            </div>
        </nav>
    </div>
</template>
