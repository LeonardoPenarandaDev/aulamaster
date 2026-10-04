<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import Icon from '@/Components/Icon.vue';
import InstitutionLogo from '@/Components/InstitutionLogo.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import { sidebarTheme } from '@/sidebarThemes';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

/**
 * Layout del personal (admin, coordinador, cajero y secretaria): barra
 * lateral con los módulos agrupados y barra superior translúcida. En el
 * celular la barra lateral se abre como un panel.
 */
const page = usePage();
const sidebarOpen = ref(false);

// Estilo del menú elegido en Sistema → Apariencia.
const theme = computed(() => sidebarTheme(page.props.institution?.sidebar_style));

const roles = computed(() => page.props.auth.roles ?? []);
const has = (...names) => names.some((name) => roles.value.includes(name));
const isCurrent = (...patterns) => patterns.some((pattern) => route().current(pattern));

/**
 * Menú por rol. Cada grupo y cada opción solo aparecen para los roles que
 * tienen permiso sobre esas rutas.
 */
const navigation = computed(() => [
    {
        items: [
            { label: 'Inicio', icon: 'home', route: 'dashboard', active: ['dashboard'], show: true },
            { label: 'Calendario', icon: 'calendar', route: 'calendar.index', active: ['calendar.*'], show: has('estudiante', 'profesor') && !has('admin', 'coordinador') },
            { label: 'Horarios disponibles', icon: 'clock', route: 'student-schedule.index', active: ['student-schedule.*'], show: has('estudiante') },
            { label: 'Material de clase', icon: 'folder', route: 'student-materials.index', active: ['student-materials.*'], show: has('estudiante') },
            { label: 'Mis contratos', icon: 'document', route: 'student-contracts.index', active: ['student-contracts.*'], show: has('estudiante') },
        ],
    },
    {
        label: 'Académico',
        show: has('admin', 'coordinador'),
        items: [
            { label: 'Calendario', icon: 'calendar', route: 'calendar.index', active: ['calendar.*'], show: true },
            { label: 'Clases (lista)', icon: 'clipboard', route: 'class-sessions.index', active: ['class-sessions.*', 'class-schedules.*'], show: true },
            { label: 'Cursos', icon: 'book', route: 'courses.index', active: ['courses.*'], show: true },
            { label: 'Niveles', icon: 'chart', route: 'levels.index', active: ['levels.*'], show: true },
            { label: 'Aulas', icon: 'building', route: 'classrooms.index', active: ['classrooms.*'], show: true },
        ],
    },
    {
        label: 'Personas',
        show: has('admin', 'cajero', 'secretaria'),
        items: [
            { label: 'Estudiantes', icon: 'users', route: 'students.index', active: ['students.*'], show: has('admin', 'secretaria') },
            { label: 'Profesores', icon: 'teacher', route: 'teachers.index', active: ['teachers.*'], show: has('admin') },
            { label: 'Matrículas', icon: 'document', route: 'enrollments.index', active: ['enrollments.*'], show: true },
        ],
    },
    {
        label: 'Seguimiento',
        show: has('admin'),
        items: [
            { label: 'Asistencias', icon: 'check-circle', route: 'attendance.index', active: ['attendance.index'], show: true },
            { label: 'Inasistencias', icon: 'alert-triangle', route: 'attendance.absences', active: ['attendance.absences'], show: true },
            { label: 'Evaluaciones', icon: 'clipboard', route: 'evaluation-results.index', active: ['evaluation-results.*', 'evaluations.*'], show: true },
            { label: 'Recuperaciones', icon: 'refresh', route: 'recovery.index', active: ['recovery.*', 'recovery-settings.*'], show: true },
        ],
    },
    {
        label: 'Finanzas',
        show: has('admin', 'cajero', 'secretaria'),
        items: [
            { label: 'Pagos', icon: 'wallet', route: 'payments.index', active: ['payments.*'], show: true },
            { label: 'Cartera en mora', icon: 'alert-triangle', route: 'collections.index', active: ['collections.*'], show: true },
            { label: 'Promociones', icon: 'coins', route: 'promotions.index', active: ['promotions.*', 'referrals.*'], show: has('admin', 'cajero') },
            { label: 'Reportes', icon: 'chart', route: 'reports.index', active: ['reports.*'], show: has('admin', 'cajero') },
        ],
    },
    {
        label: 'Sistema',
        show: has('admin', 'coordinador'),
        items: [
            { label: 'Usuarios del personal', icon: 'users', route: 'staff-users.index', active: ['staff-users.*'], show: has('admin') },
            { label: 'Restablecer contraseñas', icon: 'shield', route: 'password-resets.index', active: ['password-resets.*'], show: true },
            { label: 'Plantillas de contrato', icon: 'document', route: 'contract-templates.index', active: ['contract-templates.*'], show: has('admin') },
            { label: 'Auditoría', icon: 'clipboard', route: 'audit-logs.index', active: ['audit-logs.*'], show: has('admin') },
            { label: 'Apariencia', icon: 'sun', route: 'appearance.edit', active: ['appearance.*'], show: has('admin') },
            { label: 'Configuración', icon: 'settings', route: 'institution-settings.edit', active: ['institution-settings.*'], show: has('admin') },
        ],
    },
]
    .filter((group) => group.show !== false)
    .map((group) => ({ ...group, items: group.items.filter((item) => item.show) }))
    .filter((group) => group.items.length));

const roleLabel = computed(() => ({
    admin: 'Administrador',
    coordinador: 'Coordinador',
    cajero: 'Cajero',
    secretaria: 'Secretaria',
    profesor: 'Profesor',
    estudiante: 'Estudiante',
}[roles.value[0]] ?? ''));

const initials = computed(() => page.props.auth.user.name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word[0].toUpperCase())
    .join(''));

// Al navegar en el celular se cierra el panel lateral.
const removeListener = router.on('navigate', () => (sidebarOpen.value = false));
onBeforeUnmount(removeListener);
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <!-- Fondo oscuro del panel en el celular -->
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
            <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden" @click="sidebarOpen = false" />
        </Transition>

        <!-- Barra lateral -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r transition-transform duration-200 lg:translate-x-0"
            :class="[theme.aside, sidebarOpen ? 'translate-x-0' : '-translate-x-full']"
        >
            <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b px-5" :class="theme.divider">
                <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-2.5">
                    <InstitutionLogo />
                    <span class="truncate text-[15px] font-semibold" :class="theme.name">{{ page.props.institution.name }}</span>
                </Link>
                <Link
                    v-if="has('admin')"
                    :href="route('institution-settings.edit')"
                    class="hidden shrink-0 rounded-lg p-1.5 lg:block"
                    :class="theme.closeButton"
                    title="Cambiar el nombre y el logo de la institución"
                    aria-label="Cambiar el nombre y el logo de la institución"
                >
                    <Icon name="settings" class="h-4 w-4" />
                </Link>
                <button type="button" class="rounded-lg p-1.5 lg:hidden" :class="theme.closeButton" aria-label="Cerrar menú" @click="sidebarOpen = false">
                    <Icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
                <div v-for="(group, index) in navigation" :key="group.label ?? index">
                    <p v-if="group.label" class="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider" :class="theme.groupLabel">{{ group.label }}</p>
                    <ul class="space-y-0.5">
                        <li v-for="item in group.items" :key="item.route">
                            <Link
                                :href="route(item.route)"
                                class="group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition"
                                :class="isCurrent(...item.active) ? theme.itemActive : theme.item"
                            >
                                <Icon
                                    :name="item.icon"
                                    class="h-5 w-5 shrink-0"
                                    :class="isCurrent(...item.active) ? theme.iconActive : theme.icon"
                                />
                                {{ item.label }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="border-t p-3" :class="theme.divider">
                <Link :href="route('profile.edit')" class="flex items-center gap-3 rounded-xl px-3 py-2" :class="theme.profileHover">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-600 to-accent-500 text-sm font-semibold text-white">{{ initials }}</span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium" :class="theme.profileName">{{ page.props.auth.user.name }}</span>
                        <span class="block truncate text-xs" :class="theme.profileRole">{{ roleLabel }}</span>
                    </span>
                </Link>
            </div>
        </aside>

        <div class="lg:pl-72">
            <!-- Barra superior -->
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200/70 bg-white/80 px-4 backdrop-blur sm:px-6 lg:px-8">
                <button type="button" class="-ml-1 rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Abrir menú" @click="sidebarOpen = true">
                    <Icon name="menu" class="h-6 w-6" />
                </button>

                <div class="min-w-0 flex-1 [&_h2]:truncate [&_h2]:text-lg [&_h2]:font-semibold [&_h2]:leading-tight [&_h2]:text-slate-900">
                    <slot name="header" />
                </div>

                <ThemeToggle />
                <NotificationBell />

                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button type="button" class="flex items-center gap-2 rounded-full p-0.5 hover:bg-slate-100 sm:pr-2" :aria-label="page.props.auth.user.name">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-600 to-accent-500 text-xs font-semibold text-white">{{ initials }}</span>
                            <Icon name="chevron-down" class="hidden h-4 w-4 text-slate-500 sm:block" />
                        </button>
                    </template>
                    <template #content>
                        <div class="border-b border-slate-100 px-4 py-2.5">
                            <p class="truncate text-sm font-medium text-slate-900">{{ page.props.auth.user.name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ page.props.auth.user.email }}</p>
                        </div>
                        <DropdownLink :href="route('profile.edit')">Mi perfil</DropdownLink>
                        <DropdownLink :href="route('logout')" method="post" as="button">Cerrar sesión</DropdownLink>
                    </template>
                </Dropdown>
            </header>

            <div v-if="page.props.flash?.error" class="px-4 pt-6 sm:px-6 lg:px-8">
                <div class="flex items-start gap-3 rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-700">
                    <Icon name="alert-triangle" class="h-5 w-5 shrink-0" />
                    {{ page.props.flash.error }}
                </div>
            </div>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
