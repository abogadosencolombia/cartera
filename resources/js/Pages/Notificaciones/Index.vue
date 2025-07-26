<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';

// Importación de iconos limpia y unificada
import { CheckCircleIcon, EyeIcon, BellAlertIcon, InboxIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    notificaciones: Object,
    filtros: Object,
    tipos_alerta: Array,
});

// --- Lógica de Acciones ---
const marcarComoLeida = (notificacionId) => {
    router.patch(route('notificaciones.leer', notificacionId), {}, {
        preserveScroll: true,
    });
};

const marcarComoAtendida = (notificacionId) => {
    router.patch(route('notificaciones.atender', notificacionId), {}, {
        preserveScroll: true,
    });
};

// --- Lógica de Filtros ---
const localFiltros = ref({
    leido: props.filtros.leido || '',
    tipo: props.filtros.tipo || '',
});

watch(localFiltros, (newFilters) => {
    router.get(route('notificaciones.index'), newFilters, {
        preserveState: true,
        replace: true,
    });
}, { deep: true });

// --- Ayudantes de Estilo y Formato ---
const prioridadClasses = {
    alta: 'border-red-500',
    media: 'border-yellow-500',
    baja: 'border-blue-500',
};

const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleString('es-CO', options);
};
</script>

<template>
    <Head title="Bandeja de Notificaciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center">
                <BellAlertIcon class="h-6 w-6 mr-2" />
                Bandeja de Notificaciones
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                            <div>
                                <label for="filtro_leido" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                                <select v-model="localFiltros.leido" id="filtro_leido" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                    <option value="">Todos</option>
                                    <option value="no">No Leídas</option>
                                    <option value="si">Leídas</option>
                                </select>
                            </div>
                            <div>
                                <label for="filtro_tipo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Alerta</label>
                                <select v-model="localFiltros.tipo" id="filtro_tipo" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm capitalize">
                                    <option value="">Todos</option>
                                    <option v-for="tipo in tipos_alerta" :key="tipo" :value="tipo">{{ tipo.replace(/_/g, ' ') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div v-if="notificaciones.data.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                                <InboxIcon class="h-16 w-16 mx-auto text-gray-400" />
                                <p class="mt-2 font-semibold">Tu bandeja de entrada está vacía.</p>
                            </div>
                            <div v-else v-for="notificacion in notificaciones.data" :key="notificacion.id"
                                 class="p-4 rounded-lg flex items-start space-x-4 border-l-4 transition-colors duration-300"
                                 :class="[prioridadClasses[notificacion.prioridad] || 'border-gray-300', notificacion.leido ? 'bg-gray-50 dark:bg-gray-800/50' : 'bg-white dark:bg-gray-800 shadow-md']">
                                
                                <div class="flex-shrink-0 pt-1">
                                    <CheckCircleIcon v-if="notificacion.leido" class="h-6 w-6 text-green-500" />
                                    <BellAlertIcon v-else class="h-6 w-6" :class="{ 'text-red-500': notificacion.prioridad === 'alta', 'text-yellow-500': notificacion.prioridad === 'media', 'text-blue-500': notificacion.prioridad === 'baja' }" />
                                </div>

                                <div class="flex-grow">
                                    <p class="font-medium text-gray-800 dark:text-gray-200">{{ notificacion.mensaje }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        <Link :href="route('casos.show', notificacion.caso_id)" class="hover:underline font-semibold">
                                            Ver Caso #{{ notificacion.caso_id }}
                                        </Link>
                                        <span class="mx-2">|</span>
                                        <span>{{ formatDateTime(notificacion.created_at) }}</span>
                                    </p>
                                </div>

                                <div class="flex-shrink-0">
                                    <button v-if="!notificacion.leido" @click="marcarComoLeida(notificacion.id)" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <EyeIcon class="h-4 w-4 mr-1.5" />
                                        Marcar como Leída
                                    </button>
                                    
                                    <button v-if="notificacion.leido && !notificacion.atendida_en" @click="marcarComoAtendida(notificacion.id)" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                        <CheckBadgeIcon class="h-4 w-4 mr-1.5" />
                                        Marcar como Atendida
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="notificaciones.data.length > 0" class="mt-6">
                            <Pagination :links="notificaciones.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>