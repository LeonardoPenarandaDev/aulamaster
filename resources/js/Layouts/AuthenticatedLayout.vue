<script setup>
import { computed, ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import InstitutionLogo from '@/Components/InstitutionLogo.vue';
import NavDropdown from '@/Components/NavDropdown.vue';
import NavLink from '@/Components/NavLink.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();
const isAdmin = () => page.props.auth.roles?.includes('admin');
const isCoordinador = () => page.props.auth.roles?.includes('coordinador');
const isCajero = () => page.props.auth.roles?.includes('cajero');
const isSecretaria = () => page.props.auth.roles?.includes('secretaria');
const isStudent = () => page.props.auth.roles?.includes('estudiante');
const isTeacher = () => page.props.auth.roles?.includes('profesor');

// Fondo del estudiante: degradado suave del color de su nivel actual hacia
// blanco (parte 4 del plan de mejoras). Sin nivel, se mantiene el gris.
const studentBackground = computed(() => {
    const color = page.props.studentTheme?.color;

    return color ? { backgroundImage: `linear-gradient(to bottom, ${color}, #ffffff 70%)` } : null;
});
</script>

<template>
    <div>
        <div class="min-h-screen" :class="{ 'bg-gray-100': !studentBackground }" :style="studentBackground">
            <nav
                class="border-b border-gray-100 bg-white"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')" class="flex items-center gap-2">
                                    <InstitutionLogo />
                                    <span class="hidden max-w-[14rem] truncate text-lg font-bold tracking-tight text-gray-800 sm:block">{{ page.props.institution.name }}</span>
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden items-center space-x-1 lg:ms-8 lg:flex"
                            >
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Dashboard
                                </NavLink>
                                <NavLink
                                    v-if="isStudent() || isTeacher()"
                                    :href="route('calendar.index')"
                                    :active="route().current('calendar.*')"
                                >
                                    Calendario
                                </NavLink>
                                <NavLink
                                    v-if="isStudent()"
                                    :href="route('student-schedule.index')"
                                    :active="route().current('student-schedule.*')"
                                >
                                    Horarios disponibles
                                </NavLink>
                                <NavLink
                                    v-if="isStudent()"
                                    :href="route('student-materials.index')"
                                    :active="route().current('student-materials.*')"
                                >
                                    Material de clase
                                </NavLink>
                                <NavLink
                                    v-if="isStudent()"
                                    :href="route('student-contracts.index')"
                                    :active="route().current('student-contracts.*')"
                                >
                                    Mis contratos
                                </NavLink>
                                <NavDropdown
                                    v-if="isAdmin() || isCoordinador()"
                                    label="Académico"
                                    :active="route().current('courses.*') || route().current('levels.*') || route().current('classrooms.*') || route().current('class-sessions.*') || route().current('class-schedules.*')"
                                >
                                    <DropdownLink :href="route('courses.index')">Cursos</DropdownLink>
                                    <DropdownLink :href="route('levels.index')">Niveles</DropdownLink>
                                    <DropdownLink :href="route('classrooms.index')">Aulas</DropdownLink>
                                    <DropdownLink :href="route('calendar.index')">Calendario</DropdownLink>
                                    <DropdownLink :href="route('class-sessions.index')">Clases (lista)</DropdownLink>
                                </NavDropdown>
                                <NavDropdown
                                    v-if="isAdmin() || isCajero() || isSecretaria()"
                                    label="Personas"
                                    :active="route().current('students.*') || route().current('teachers.*') || route().current('enrollments.*')"
                                >
                                    <DropdownLink v-if="isAdmin() || isSecretaria()" :href="route('students.index')">Estudiantes</DropdownLink>
                                    <DropdownLink v-if="isAdmin()" :href="route('teachers.index')">Profesores</DropdownLink>
                                    <DropdownLink :href="route('enrollments.index')">Matrículas</DropdownLink>
                                </NavDropdown>
                                <NavDropdown
                                    v-if="isAdmin()"
                                    label="Seguimiento"
                                    :active="route().current('attendance.*') || route().current('evaluation-results.*') || route().current('evaluations.*') || route().current('recovery.*') || route().current('recovery-settings.*')"
                                >
                                    <DropdownLink :href="route('attendance.index')">Asistencias</DropdownLink>
                                    <DropdownLink :href="route('attendance.absences')">Inasistencias</DropdownLink>
                                    <DropdownLink :href="route('evaluation-results.index')">Evaluaciones</DropdownLink>
                                    <DropdownLink :href="route('recovery.index')">Recuperaciones</DropdownLink>
                                </NavDropdown>
                                <NavDropdown
                                    v-if="isAdmin() || isCajero() || isSecretaria()"
                                    label="Finanzas"
                                    :active="route().current('payments.*') || route().current('collections.*') || route().current('promotions.*') || route().current('referrals.*') || route().current('reports.*')"
                                >
                                    <DropdownLink :href="route('payments.index')">Pagos</DropdownLink>
                                    <DropdownLink :href="route('collections.index')">Cartera en mora</DropdownLink>
                                    <DropdownLink v-if="!isSecretaria()" :href="route('promotions.index')">Promociones</DropdownLink>
                                    <DropdownLink v-if="!isSecretaria()" :href="route('reports.index')">Reportes</DropdownLink>
                                </NavDropdown>
                                <NavDropdown
                                    v-if="isAdmin() || isCoordinador()"
                                    label="Sistema"
                                    :active="route().current('audit-logs.*') || route().current('institution-settings.*') || route().current('staff-users.*') || route().current('password-resets.*') || route().current('contract-templates.*')"
                                >
                                    <DropdownLink v-if="isAdmin()" :href="route('staff-users.index')">Usuarios del personal</DropdownLink>
                                    <DropdownLink :href="route('password-resets.index')">Restablecer contraseñas</DropdownLink>
                                    <DropdownLink v-if="isAdmin()" :href="route('contract-templates.index')">Plantillas de contrato</DropdownLink>
                                    <DropdownLink v-if="isAdmin()" :href="route('audit-logs.index')">Auditoría</DropdownLink>
                                    <DropdownLink v-if="isAdmin()" :href="route('institution-settings.edit')">Configuración institucional</DropdownLink>
                                </NavDropdown>
                            </div>
                        </div>

                        <div class="hidden lg:ms-6 lg:flex lg:items-center">
                            <NotificationBell />

                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                <span class="max-w-[10rem] truncate">{{ $page.props.auth.user.name }}</span>

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Mi perfil
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Cerrar sesión
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center gap-2 lg:hidden">
                            <NotificationBell />
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="lg:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="isStudent() || isTeacher()"
                            :href="route('calendar.index')"
                            :active="route().current('calendar.*')"
                        >
                            Calendario
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="isStudent()"
                            :href="route('student-schedule.index')"
                            :active="route().current('student-schedule.*')"
                        >
                            Horarios disponibles
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="isStudent()"
                            :href="route('student-materials.index')"
                            :active="route().current('student-materials.*')"
                        >
                            Material de clase
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="isStudent()"
                            :href="route('student-contracts.index')"
                            :active="route().current('student-contracts.*')"
                        >
                            Mis contratos
                        </ResponsiveNavLink>
                        <template v-if="isAdmin() || isCoordinador()">
                            <div class="px-4 pb-1 pt-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Académico</div>
                            <ResponsiveNavLink :href="route('courses.index')" :active="route().current('courses.*')">Cursos</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('levels.index')" :active="route().current('levels.*')">Niveles</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('classrooms.index')" :active="route().current('classrooms.*')">Aulas</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('calendar.index')" :active="route().current('calendar.*')">Calendario</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('class-sessions.index')" :active="route().current('class-sessions.*') || route().current('class-schedules.*')">Clases (lista)</ResponsiveNavLink>
                        </template>

                        <template v-if="isAdmin() || isCajero() || isSecretaria()">
                            <div class="px-4 pb-1 pt-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Personas</div>
                            <ResponsiveNavLink v-if="isAdmin() || isSecretaria()" :href="route('students.index')" :active="route().current('students.*')">Estudiantes</ResponsiveNavLink>
                            <ResponsiveNavLink v-if="isAdmin()" :href="route('teachers.index')" :active="route().current('teachers.*')">Profesores</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('enrollments.index')" :active="route().current('enrollments.*')">Matrículas</ResponsiveNavLink>
                        </template>

                        <template v-if="isAdmin()">
                            <div class="px-4 pb-1 pt-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Seguimiento</div>
                            <ResponsiveNavLink :href="route('attendance.index')" :active="route().current('attendance.index')">Asistencias</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('attendance.absences')" :active="route().current('attendance.absences')">Inasistencias</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('evaluation-results.index')" :active="route().current('evaluation-results.*') || route().current('evaluations.*')">Evaluaciones</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('recovery.index')" :active="route().current('recovery.*') || route().current('recovery-settings.*')">Recuperaciones</ResponsiveNavLink>
                        </template>

                        <template v-if="isAdmin() || isCajero() || isSecretaria()">
                            <div class="px-4 pb-1 pt-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Finanzas</div>
                            <ResponsiveNavLink :href="route('payments.index')" :active="route().current('payments.*')">Pagos</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('collections.index')" :active="route().current('collections.*')">Cartera en mora</ResponsiveNavLink>
                            <ResponsiveNavLink v-if="!isSecretaria()" :href="route('promotions.index')" :active="route().current('promotions.*') || route().current('referrals.*')">Promociones</ResponsiveNavLink>
                            <ResponsiveNavLink v-if="!isSecretaria()" :href="route('reports.index')" :active="route().current('reports.*')">Reportes</ResponsiveNavLink>
                        </template>

                        <template v-if="isAdmin() || isCoordinador()">
                            <div class="px-4 pb-1 pt-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Sistema</div>
                            <ResponsiveNavLink v-if="isAdmin()" :href="route('staff-users.index')" :active="route().current('staff-users.*')">Usuarios del personal</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('password-resets.index')" :active="route().current('password-resets.*')">Restablecer contraseñas</ResponsiveNavLink>
                            <ResponsiveNavLink v-if="isAdmin()" :href="route('contract-templates.index')" :active="route().current('contract-templates.*')">Plantillas de contrato</ResponsiveNavLink>
                            <ResponsiveNavLink v-if="isAdmin()" :href="route('audit-logs.index')" :active="route().current('audit-logs.*')">Auditoría</ResponsiveNavLink>
                            <ResponsiveNavLink v-if="isAdmin()" :href="route('institution-settings.edit')" :active="route().current('institution-settings.*')">Configuración institucional</ResponsiveNavLink>
                        </template>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div
                        class="border-t border-gray-200 pb-1 pt-4"
                    >
                        <div class="px-4">
                            <div
                                class="text-base font-medium text-gray-800"
                            >
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Mi perfil
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Cerrar sesión
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-white shadow"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <div
                v-if="page.props.flash?.error"
                class="mx-auto mt-4 max-w-screen-2xl px-4 sm:px-6 lg:px-8"
            >
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ page.props.flash.error }}
                </div>
            </div>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
