<script setup>
/**
 * AuthenticatedLayout.vue
 *
 * Layout principal para toda el área autenticada del sistema.
 * Proporciona una estructura consistente con navegación, cabecera y contenido principal.
 * El menú de navegación es dinámico y se adapta según el rol del usuario.
 *
 * Estructura:
 * 1. Importaciones (Core, Componentes, Iconos)
 * 2. Definición del Menú (NAV_ITEMS)
 * 3. Estado y Datos (Inertia, UI)
 * 4. Lógica Computada (Filtrado de menú por rol)
 * 5. Funciones de Ayuda (Helpers)
 * 6. Efectos de Ciclo de Vida (Lifecycle)
 */

// 1. IMPORTACIONES
// -----------------------------------------------------------------------------

// Core - Vue & Inertia
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { initPush } from '@/push';

// Componentes UI
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ToastList from '@/Components/ToastList.vue'; // <--- 1. IMPORT INTEGRADO

// Iconos (Heroicons)
import {
    ChartBarIcon, FolderIcon, BellIcon, UsersIcon, ScaleIcon,
    Cog6ToothIcon, ShieldCheckIcon, KeyIcon, DocumentTextIcon,
    ListBulletIcon, ExclamationTriangleIcon, CircleStackIcon, GlobeAltIcon,
    BanknotesIcon,
} from '@heroicons/vue/24/outline';


// 2. DEFINICIÓN DEL MENÚ
// -----------------------------------------------------------------------------
/**
 * Catálogo completo de elementos de navegación.
 * Esta estructura declarativa es la única fuente de verdad para el menú.
 * - type: 'link', 'notification', 'dropdown', 'divider', 'external'
 * - roles: Array de roles que pueden ver el elemento.
 * - active: Patrones de ruta para resaltar el enlace activo.
 */
const NAV_ITEMS = [
    // === Módulos Principales ===
    { type: 'link', label: 'Dashboard', href: route('dashboard'), active: 'dashboard', icon: ChartBarIcon, roles: ['admin', 'gestor', 'abogado'] },
    { type: 'link', label: 'Casos', href: route('casos.index'), active: 'casos.*', icon: FolderIcon, roles: ['admin', 'gestor', 'abogado'] },
    { type: 'link', label: 'Reportes', href: route('reportes.index'), active: 'reportes.*', icon: ChartBarIcon, roles: ['admin', 'gestor', 'abogado'] },

    // === Notificaciones ===
    { type: 'notification', label: 'Notificaciones', href: route('notificaciones.index'), active: 'notificaciones.*', icon: BellIcon, roles: ['admin', 'gestor', 'abogado'] },

    // === Entidades ===
    {
        type: 'dropdown', label: 'Entidades', icon: UsersIcon,
        active: ['cooperativas.*', 'personas.*', 'admin.users.*', 'admin.gestores.*', 'procesos.*'], // ← añadido 'procesos.*'
        roles: ['admin', 'gestor', 'abogado'],
        items: [
            { label: 'Cooperativas', href: route('cooperativas.index'), active: 'cooperativas.*', roles: ['admin', 'gestor', 'abogado'] },
            { label: 'Personas', href: route('personas.index'), active: 'personas.*', roles: ['admin', 'gestor', 'abogado'] },
            { type: 'divider', roles: ['admin'] },
            { label: 'Usuarios del Sistema', href: route('admin.users.index'), active: 'admin.users.*', roles: ['admin'] },
            { label: 'Abogados/Gestores', href: route('admin.gestores.index'), active: 'admin.gestores.*', roles: ['admin'] },
            { label: 'Radicados', href: route('procesos.index'), active: 'procesos.*', roles: ['admin','gestor','abogado'] },
        ],
    },

    // === Gestión ===
    {
        type: 'dropdown', label: 'Gestión', icon: ScaleIcon,
        active: ['gestion.honorarios.*', 'honorarios.contratos.*'],
        roles: ['admin', 'gestor', 'abogado'],
        items: [
            { label: 'Honorarios', href: route('gestion.honorarios.index'), active: 'gestion.honorarios.*', roles: ['admin', 'gestor', 'abogado'] },
        ],
    },

    // === Configuración (Solo Admin) ===
    {
        type: 'dropdown', label: 'Configuración', icon: Cog6ToothIcon,
        active: [
            'plantillas.*', 'requisitos.*', 'admin.incidentes-juridicos.*', 'admin.juridico.indicadores',
            'admin.reglas-alerta.*', 'admin.auditoria.*', 'admin.tokens.*', 'integraciones.index', 'documentos-generados.*',
            'admin.tasas.*',
        ],
        roles: ['admin'],
        items: [
            { label: 'Plantillas de Documentos', href: route('plantillas.index'), active: 'plantillas.*', icon: DocumentTextIcon, roles: ['admin'] },
            { label: 'Requisitos de Documentos', href: route('requisitos.index'), active: 'requisitos.*', icon: ListBulletIcon, roles: ['admin'] },
            { label: 'Tasas de Interés', href: route('admin.tasas.index'), active: 'admin.tasas.*', icon: BanknotesIcon, roles: ['admin'] },
            { label: 'Gestión de Incidentes', href: route('admin.incidentes-juridicos.index'), active: 'admin.incidentes-juridicos.*', icon: ScaleIcon, roles: ['admin'] },
            { label: 'Panel de Indicadores', href: route('admin.juridico.indicadores'), active: 'admin.juridico.indicadores', icon: ChartBarIcon, roles: ['admin'] },
            { type: 'divider', roles: ['admin'] },
            { label: 'Reglas de Alerta', href: route('admin.reglas-alerta.index'), active: 'admin.reglas-alerta.*', icon: ExclamationTriangleIcon, roles: ['admin'] },
            { label: 'Auditoría Global', href: route('admin.auditoria.index'), active: 'admin.auditoria.index', icon: ShieldCheckIcon, roles: ['admin'] },
            { label: 'Auditoría de Documentos', href: route('documentos-generados.index'), active: 'documentos-generados.*', icon: ShieldCheckIcon, roles: ['admin'] },
            { label: 'Gestión de Credenciales', href: route('admin.tokens.index'), active: 'admin.tokens.*', icon: KeyIcon, roles: ['admin'] },
            { label: 'Logs de Integraciones', href: route('integraciones.index'), active: 'integraciones.index', icon: CircleStackIcon, roles: ['admin'] },
        ],
    },

    // === Herramientas Externas ===
    {
        type: 'dropdown', label: 'Herramientas', icon: GlobeAltIcon,
        active: [],
        roles: ['admin', 'gestor', 'abogado'],
        items: [
            { type: 'external', label: 'Consulta de procesos', href: 'https://consultaprocesos.ramajudicial.gov.co/procesos/Index', roles: ['admin', 'gestor', 'abogado'] },
            { type: 'external', label: 'Procesos rama judicial', href: 'https://procesos.ramajudicial.gov.co/procesoscs/ConsultaJusticias21.aspx?EntryId=1ND%2fT1QaEBFgDjwxVoCZ45pYS4g%3d', roles: ['admin', 'gestor', 'abogado'] },
            { type: 'external', label: 'Publicaciones Procesales', href: 'https://publicacionesprocesales.ramajudicial.gov.co/', roles: ['admin', 'gestor', 'abogado'] },
            { type: 'external', label: 'Asistente ChatGPT', href: 'https://chatgpt.com/g/g-68360119b4d48191898d44cb97865146-abogado-civil-director-juridico-experto?model=gpt-5-pro', roles: ['admin', 'gestor', 'abogado'] },
            { type: 'external', label: 'Asistente Gemini', href: 'https://gemini.google.com/', roles: ['admin', 'gestor', 'abogado'] },
            { type: 'external', label: 'Asistente Claude', href: 'https://claude.ai/', recommendation: 'Se recomienda la app de escritorio', roles: ['admin', 'gestor', 'abogado'] },
        ],
    },
];


// 3. ESTADO Y DATOS
// -----------------------------------------------------------------------------

// Estado global de la página (Inertia)
const page = usePage();

// Estado local de la UI
const showingNavigationDropdown = ref(false);

// Datos derivados del usuario autenticado
const currentUser = computed(() => page.props.auth?.user ?? { name: 'Usuario', email: '' });
const userRole = computed(() => page.props.auth?.user?.tipo_usuario ?? page.props.auth?.user?.role ?? 'guest');
const unreadCount = computed(() => Number(page.props.auth?.unreadNotifications ?? 0));


// 4. LÓGICA COMPUTADA
// -----------------------------------------------------------------------------

/**
 * Filtra el menú de navegación (NAV_ITEMS) basándose en el rol del usuario actual.
 * - Filtra los elementos de nivel superior.
 * - Filtra los sub-elementos dentro de los dropdowns.
 * @returns {Array} El menú visible para el usuario.
 */
const visibleMenu = computed(() =>
    NAV_ITEMS
        .filter((item) => item.roles.includes(userRole.value))
        .map((item) =>
            item.type === 'dropdown'
                ? { ...item, items: item.items.filter((sub) => sub.roles.includes(userRole.value)) }
                : item
        )
);


// 5. FUNCIONES DE AYUDA (HELPERS)
// -----------------------------------------------------------------------------

/**
 * Comprueba si la ruta actual coincide con alguno de los patrones dados.
 * @param {String|Array} patterns - Uno o más patrones de ruta a comprobar.
 * @returns {Boolean} - Verdadero si hay una coincidencia.
 */
const isRouteActive = (patterns) => {
    if (!Array.isArray(patterns)) patterns = [patterns];
    return patterns.some((p) => route().current(p));
};


// 6. EFECTOS DE CICLO DE VIDA (LIFECYCLE)
// -----------------------------------------------------------------------------
onMounted(() => {
    // Inicializa las notificaciones push al montar el componente.
    initPush().catch(() => {
        // Manejar error de inicialización si es necesario (ej. log)
    });
});
</script>

<template>
    <!-- Enlace de accesibilidad para saltar directamente al contenido principal -->
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:bg-white focus:text-black focus:px-3 focus:py-2 focus:rounded">
        Saltar al contenido
    </a>

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- ================================================================== -->
        <!-- NAVBAR PRINCIPAL                                                   -->
        <!-- ================================================================== -->
        <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">

                    <!-- ----------- Marca + Menú principal (Desktop) ----------- -->
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')" aria-label="Ir al Dashboard">
                            <ApplicationLogo class="block h-9 w-auto" />
                            </Link>
                        </div>

                        <!-- Enlaces de navegación -->
                        <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex">
                            <template v-for="item in visibleMenu" :key="item.label">

                                <!-- Caso 1: Enlace simple -->
                                <NavLink v-if="item.type === 'link'" :href="item.href"
                                         :active="isRouteActive(item.active)">
                                    <component :is="item.icon" class="h-5 w-5 mr-1 inline-block" aria-hidden="true" />
                                    {{ item.label }}
                                </NavLink>

                                <!-- Caso 2: Enlace de Notificaciones -->
                                <NavLink v-else-if="item.type === 'notification'" :href="item.href"
                                         :active="isRouteActive(item.active)" :aria-label="`Notificaciones (${unreadCount})`">
                                    <div class="relative flex items-center">
                                        <component :is="item.icon" class="h-5 w-5 mr-1" aria-hidden="true" />
                                        <span>{{ item.label }}</span>
                                        <span v-if="unreadCount > 0"
                                              class="absolute -top-1 -right-2.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">
                                            <span class="sr-only">No leídas:</span>{{ unreadCount }}
                                        </span>
                                    </div>
                                </NavLink>

                                <!-- Caso 3: Menú Desplegable (Dropdown) -->
                                <div v-else-if="item.type === 'dropdown'" class="hidden sm:flex sm:items-center sm:ms-1">
                                    <Dropdown align="left" :width="item.label === 'Configuración' ? '64' : '56'">
                                        <template #trigger>
                                            <button
                                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition"
                                                :class="isRouteActive(item.active)
                                                            ? 'border-indigo-400 dark:border-indigo-600 text-gray-900 dark:text-gray-100'
                                                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                                :aria-label="item.label" aria-haspopup="true">
                                                <component :is="item.icon" class="h-5 w-5 mr-1" aria-hidden="true" />
                                                <span>{{ item.label }}</span>
                                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                     viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd"
                                                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                          clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </template>

                                        <template #content>
                                            <template v-for="(subItem, index) in item.items" :key="index">
                                                <div v-if="subItem.type === 'divider'"
                                                     class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                                <a v-else-if="subItem.type === 'external'" :href="subItem.href"
                                                   target="_blank" rel="noopener noreferrer"
                                                   class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none transition">
                                                    <div>
                                                        <div class="flex items-center">
                                                            <component v-if="subItem.icon" :is="subItem.icon"
                                                                       class="h-4 w-4 mr-2 inline-block text-gray-400"
                                                                       aria-hidden="true" />
                                                            {{ subItem.label }}
                                                        </div>
                                                        <div v-if="subItem.recommendation"
                                                             class="text-xs text-gray-500 dark:text-gray-400 mt-1 ps-6">
                                                            {{ subItem.recommendation }}
                                                        </div>
                                                    </div>
                                                </a>
                                                <DropdownLink v-else :href="subItem.href"
                                                              :active="isRouteActive(subItem.active)">
                                                    <component v-if="subItem.icon" :is="subItem.icon"
                                                               class="h-4 w-4 mr-2 inline-block text-gray-400"
                                                               aria-hidden="true" />
                                                    {{ subItem.label }}
                                                </DropdownLink>
                                            </template>
                                        </template>
                                    </Dropdown>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- ----------- Menú de Usuario (Desktop) ----------- -->
                    <div class="hidden sm:flex sm:items-center sm:ms-6">
                        <div class="ms-3 relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button type="button" aria-label="Menú de usuario"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition">
                                            {{ currentUser.name }}
                                            <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                 viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd"
                                                      d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                      clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">Cerrar Sesión
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <!-- ----------- Botón Hamburguesa (Mobile) ----------- -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none transition"
                                :aria-expanded="showingNavigationDropdown" aria-label="Abrir menú">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ================================================================== -->
            <!-- MENÚ DESPLEGABLE (MOBILE)                                          -->
            <!-- ================================================================== -->
            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">

                <!-- --- Links simples + Notificaciones --- -->
                <div class="pt-2 pb-3 space-y-1">
                    <template v-for="item in visibleMenu" :key="item.label">
                        <ResponsiveNavLink v-if="item.type === 'link' || item.type === 'notification'" :href="item.href"
                                           :active="isRouteActive(item.active)">
                            <component :is="item.icon" class="h-6 w-6 mr-2 inline-block" aria-hidden="true" />
                            {{ item.label }}
                            <span v-if="item.type === 'notification' && unreadCount > 0"
                                  class="ml-2 inline-flex items-center justify-center px-2 rounded-full bg-red-500 text-xs font-bold text-white">
                                {{ unreadCount }}
                            </span>
                        </ResponsiveNavLink>
                    </template>
                </div>

                <!-- --- Secciones de Dropdowns --- -->
                <template v-for="item in visibleMenu" :key="`dropdown-${item.label}`">
                    <div v-if="item.type === 'dropdown'" class="pt-4 pb-3 border-t border-gray-200 dark:border-gray-600">
                        <!-- Título de la sección -->
                        <div class="px-4 flex items-center">
                            <component :is="item.icon" class="h-6 w-6 mr-2 text-gray-400" aria-hidden="true" />
                            <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ item.label }}</div>
                        </div>
                        <!-- Sub-elementos -->
                        <div class="mt-3 space-y-1">
                            <template v-for="(subItem, subIndex) in item.items" :key="subIndex">
                                <div v-if="subItem.type === 'divider'"
                                     class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                <a v-else-if="subItem.type === 'external'" :href="subItem.href" target="_blank"
                                   rel="noopener noreferrer"
                                   class="block ps-3 pe-4 py-2 text-base font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <div class="ms-4">
                                        <div class="flex items-center">
                                            <component v-if="subItem.icon" :is="subItem.icon"
                                                       class="h-5 w-5 mr-3 inline-block text-gray-400" aria-hidden="true" />
                                            {{ subItem.label }}
                                        </div>
                                        <div v-if="subItem.recommendation"
                                             class="text-xs text-gray-500 dark:text-gray-400 mt-1 ps-8">
                                            {{ subItem.recommendation }}
                                        </div>
                                    </div>
                                </a>
                                <ResponsiveNavLink v-else :href="subItem.href" :active="isRouteActive(subItem.active)">
                                    <span class="ms-4">
                                        <component v-if="subItem.icon" :is="subItem.icon"
                                                   class="h-5 w-5 mr-3 inline-block text-gray-400" aria-hidden="true" />
                                        {{ subItem.label }}
                                    </span>
                                </ResponsiveNavLink>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- --- Menú de Usuario (Mobile) --- -->
                <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                    <div class="px-4">
                        <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ currentUser.name }}</div>
                        <div class="font-medium text-sm text-gray-500">{{ currentUser.email }}</div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">Perfil</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">Cerrar Sesión
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <!-- ================================================================== -->
        <!-- ENCABEZADO DE PÁGINA                                               -->
        <!-- ================================================================== -->
        <header class="bg-white dark:bg-gray-800 shadow" v-if="$slots.header">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- ================================================================== -->
        <!-- CONTENIDO PRINCIPAL                                                -->
        <!-- ================================================================== -->
        <main id="main-content">
            <slot />
        </main>

        <!-- ================================================================== -->
        <!-- GESTOR DE NOTIFICACIONES (TOASTS)                                  -->
        <!-- ================================================================== -->
        <ToastList /> <!-- <--- 2. COMPONENTE AÑADIDO -->

    </div>
</template>

