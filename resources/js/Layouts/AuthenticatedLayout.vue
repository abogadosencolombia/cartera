<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

// Importamos todos los íconos de Heroicons para una interfaz más rica
import {
    ChartBarIcon,
    FolderIcon,
    BellIcon,
    UsersIcon,
    ScaleIcon,
    Cog6ToothIcon,
    ShieldCheckIcon,
    KeyIcon,
    DocumentTextIcon,
    ListBulletIcon,
    ExclamationTriangleIcon,
    CircleStackIcon,
} from '@heroicons/vue/24/outline';

const showingNavigationDropdown = ref(false);
const page = usePage();

// Propiedades computadas para un template más limpio
const userRole = computed(() => page.props.auth.user.tipo_usuario);
const unreadCount = computed(() => page.props.auth.unreadNotifications);

// =================================================================
// ===== EL CORAZÓN DE LA ARQUITECTURA: El "Catálogo" del Menú =====
// =================================================================
const navigationMenu = computed(() => [
    // --- ENLACES PRINCIPALES ---
    { type: 'link', label: 'Dashboard', href: route('dashboard'), active: 'dashboard', icon: ChartBarIcon, roles: ['admin', 'gestor', 'abogado'] },
    { type: 'link', label: 'Casos', href: route('casos.index'), active: 'casos.*', icon: FolderIcon, roles: ['admin', 'gestor', 'abogado'] },
    { type: 'link', label: 'Reportes', href: route('reportes.index'), active: 'reportes.*', icon: ChartBarIcon, roles: ['admin', 'gestor', 'abogado'] },
    { type: 'notification', label: 'Notificaciones', href: route('notificaciones.index'), active: 'notificaciones.*', icon: BellIcon, roles: ['admin', 'gestor', 'abogado'] },

    // --- MENÚ DESPLEGABLE: GESTIÓN ---
    {
        type: 'dropdown',
        label: 'Gestión',
        active: ['cooperativas.*', 'personas.*', 'admin.users.*'],
        icon: UsersIcon,
        roles: ['admin', 'gestor', 'abogado'],
        items: [
            { label: 'Cooperativas', href: route('cooperativas.index'), active: 'cooperativas.*', roles: ['admin', 'gestor', 'abogado'] },
            { label: 'Personas', href: route('personas.index'), active: 'personas.*', roles: ['admin', 'gestor', 'abogado'] },
            { label: 'Usuarios', href: route('admin.users.index'), active: 'admin.users.*', roles: ['admin'] },
        ]
    },

    // --- MENÚ DESPLEGABLE: DEFENSA JURÍDICA ---
    {
        type: 'dropdown',
        label: 'Defensa Jurídica',
        active: ['admin.incidentes-juridicos.*', 'admin.juridico.indicadores'],
        icon: ScaleIcon,
        roles: ['admin'],
        items: [
            { label: 'Gestión de Incidentes', href: route('admin.incidentes-juridicos.index'), active: 'admin.incidentes-juridicos.*', roles: ['admin'] },
            { label: 'Panel de Indicadores', href: route('admin.juridico.indicadores'), active: 'admin.juridico.indicadores', roles: ['admin'] },
        ]
    },

    // --- MENÚ DESPLEGABLE: ADMINISTRACIÓN ---
    {
        type: 'dropdown',
        label: 'Administración',
        active: [
            'plantillas.*', 'documentos-generados.*', 'requisitos.*',
            'admin.reglas-alerta.*', 'admin.auditoria.*',
            'admin.tokens.*', 'integraciones.index'
        ],
        icon: Cog6ToothIcon,
        roles: ['admin'],
        items: [
            { label: 'Plantillas', href: route('plantillas.index'), active: 'plantillas.*', icon: DocumentTextIcon, roles: ['admin'] },
            { label: 'Requisitos Docs', href: route('requisitos.index'), active: 'requisitos.*', icon: ListBulletIcon, roles: ['admin'] },
            { label: 'Reglas de Alerta', href: route('admin.reglas-alerta.index'), active: 'admin.reglas-alerta.*', icon: ExclamationTriangleIcon, roles: ['admin'] },
            { type: 'divider', roles: ['admin'] },
            { label: 'Gestión de Credenciales', href: route('admin.tokens.index'), active: 'admin.tokens.*', icon: KeyIcon, roles: ['admin'] },
            { label: 'Logs de Integraciones', href: route('integraciones.index'), active: 'integraciones.index', icon: CircleStackIcon, roles: ['admin'] },
            { type: 'divider', roles: ['admin'] },
            { label: 'Auditoría Global', href: route('admin.auditoria.index'), active: 'admin.auditoria.index', icon: ShieldCheckIcon, roles: ['admin'] },
            { label: 'Auditoría Docs', href: route('documentos-generados.index'), active: 'documentos-generados.*', icon: ShieldCheckIcon, roles: ['admin'] },
        ]
    },
]);

// Función para comprobar si una ruta está activa, soportando wildcards (*).
const isRouteActive = (patterns) => {
    if (!Array.isArray(patterns)) patterns = [patterns];
    return patterns.some(pattern => route().current(pattern));
};

// Menú final que se mostrará, filtrado por el rol del usuario.
const visibleMenu = computed(() => {
    return navigationMenu.value
        .filter(item => item.roles.includes(userRole.value))
        .map(item => {
            // Se crea una copia para no modificar el objeto original
            const newItem = { ...item };
            if (newItem.type === 'dropdown' && newItem.items) {
                newItem.items = newItem.items.filter(subItem => subItem.roles.includes(userRole.value));
            }
            return newItem;
        });
});
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo class="block h-9 w-auto" />
                                </Link>
                            </div>

                            <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex">
                                <template v-for="item in visibleMenu" :key="item.label">

                                    <NavLink v-if="item.type === 'link'" :href="item.href" :active="isRouteActive(item.active)">
                                        <component :is="item.icon" class="h-5 w-5 mr-1 inline-block" aria-hidden="true" />
                                        {{ item.label }}
                                    </NavLink>

                                    <NavLink v-else-if="item.type === 'notification'" :href="item.href" :active="isRouteActive(item.active)">
                                        <div class="relative flex items-center">
                                            <component :is="item.icon" class="h-5 w-5 mr-1" aria-hidden="true" />
                                            <span>{{ item.label }}</span>
                                            <span v-if="unreadCount > 0" class="absolute -top-1 -right-2.5 inline-flex items-center justify-center h-5 w-5 rounded-full bg-red-500 text-xs font-bold text-white">
                                                {{ unreadCount }}
                                            </span>
                                        </div>
                                    </NavLink>

                                    <div v-else-if="item.type === 'dropdown'" class="hidden sm:flex sm:items-center sm:ms-1">
                                        <Dropdown align="left" width="56">
                                            <template #trigger>
                                                <button class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out" :class="isRouteActive(item.active) ? 'border-indigo-400 dark:border-indigo-600 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'">
                                                    <component :is="item.icon" class="h-5 w-5 mr-1" aria-hidden="true" />
                                                    <span>{{ item.label }}</span>
                                                    <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                                </button>
                                            </template>
                                            <template #content>
                                                <template v-for="(subItem, index) in item.items" :key="index">
                                                    <div v-if="subItem.type === 'divider'" class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                                    <DropdownLink v-else :href="subItem.href" :active="isRouteActive(subItem.active)">
                                                        <component v-if="subItem.icon" :is="subItem.icon" class="h-4 w-4 mr-2 inline-block text-gray-400" aria-hidden="true"/>
                                                        {{ subItem.label }}
                                                    </DropdownLink>
                                                </template>
                                            </template>
                                        </Dropdown>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <div class="ms-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                                                {{ page.props.auth.user.name }}
                                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                            </button>
                                        </span>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')"> Perfil </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">Cerrar Sesión</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /><path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <template v-for="item in visibleMenu" :key="item.label">
                            <ResponsiveNavLink v-if="item.type === 'link' || item.type === 'notification'" :href="item.href" :active="isRouteActive(item.active)">
                                <component :is="item.icon" class="h-6 w-6 mr-2 inline-block" aria-hidden="true" />
                                {{ item.label }}
                            </ResponsiveNavLink>
                        </template>
                    </div>

                    <template v-for="item in visibleMenu" :key="`dropdown-${item.label}`">
                        <div v-if="item.type === 'dropdown'" class="pt-4 pb-3 border-t border-gray-200 dark:border-gray-600">
                            <div class="px-4 flex items-center">
                                <component :is="item.icon" class="h-6 w-6 mr-2 text-gray-400" aria-hidden="true" />
                                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ item.label }}</div>
                            </div>
                            <div class="mt-3 space-y-1">
                                <template v-for="(subItem, subIndex) in item.items" :key="subIndex">
                                    <div v-if="subItem.type === 'divider'" class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                    <ResponsiveNavLink v-else :href="subItem.href" :active="isRouteActive(subItem.active)">
                                        <span class="ms-4">
                                             <component v-if="subItem.icon" :is="subItem.icon" class="h-5 w-5 mr-3 inline-block text-gray-400" aria-hidden="true"/>
                                             {{ subItem.label }}
                                        </span>
                                    </ResponsiveNavLink>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                        <div class="px-4">
                            <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ page.props.auth.user.name }}</div>
                            <div class="font-medium text-sm text-gray-500">{{ page.props.auth.user.email }}</div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"> Perfil </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button"> Cerrar Sesión </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <header class="bg-white dark:bg-gray-800 shadow" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>