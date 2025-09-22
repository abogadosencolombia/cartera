<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import KpiCard from '@/Pages/Dashboard/Partials/KpiCard.vue'; // Reutilizamos el KpiCard del Dashboard
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce, pickBy } from 'lodash';

// --- Iconos para una UI más rica ---
import { UserGroupIcon, BanknotesIcon, FolderOpenIcon, BuildingOffice2Icon, ArrowRightIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    filters: Object,
    rows: Array, // Cambiado de 'users' a 'rows' para coincidir con tu código original
});

// --- Estado de Filtros y UI ---
const q = ref(props.filters?.q ?? '');
const sort = ref(props.filters?.sort ?? 'total_recovered');
const dir = ref(props.filters?.dir ?? 'desc');
const openRowId = ref(null); // Para controlar la fila de detalles abierta

// --- Lógica de Filtros Automáticos ---
// Observa los cambios en los filtros y aplica la búsqueda después de 500ms de inactividad
watch([q, sort, dir], debounce(() => {
    router.get(route('admin.gestores.index'), pickBy({ 
        q: q.value, 
        sort: sort.value, 
        dir: dir.value 
    }), { 
        preserveState: true, 
        replace: true, 
        only: ['rows', 'filters'] 
    });
}, 500));

// --- Datos Calculados para KPIs ---
const totals = computed(() => {
    const totalRecovered = (props.rows || []).reduce((sum, row) => sum + (row.total_recovered || 0), 0);
    const totalCasos = (props.rows || []).reduce((sum, row) => sum + (row.casos_count || 0), 0);
    const coopIds = new Set();
    (props.rows || []).forEach(row => (row.cooperativas || []).forEach(coop => coopIds.add(coop.id)));
    return { totalRecovered, totalCasos, totalCoops: coopIds.size };
});

// --- Funciones de Ayuda ---
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value || 0);
};
</script>

<template>
    <Head title="Reporte de Gestores" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center">
                    <UserGroupIcon class="h-6 w-6 mr-2" />
                    Reporte de Rendimiento de Gestores y Abogados
                </h2>
                </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <KpiCard title="Total Recuperado" :metric="formatCurrency(totals.totalRecovered)" :icon="BanknotesIcon" color="green" />
                    <KpiCard title="Total de Casos Asignados" :metric="totals.totalCasos" :icon="FolderOpenIcon" color="indigo" />
                    <KpiCard title="Cooperativas Atendidas" :metric="totals.totalCoops" :icon="BuildingOffice2Icon" color="orange" />
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div class="md:col-span-1">
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filtrar por nombre</label>
                            <input v-model="q" id="search" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Buscar abogado...">
                        </div>
                        <div>
                            <label for="sort" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ordenar por</label>
                            <select v-model="sort" id="sort" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="total_recovered">Monto recuperado</option>
                                <option value="name">Nombre (A-Z)</option>
                            </select>
                        </div>
                        <div>
                            <label for="dir" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dirección</label>
                            <select v-model="dir" id="dir" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="desc">Descendente</option>
                                <option value="asc">Ascendente</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="space-y-2 p-4">
                        <div v-if="rows.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <p>No se encontraron gestores con los filtros actuales.</p>
                        </div>
                        
                        <div v-for="row in rows" :key="row.id" class="bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                            <div class="p-4 flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-4">
                                <div class="flex-grow flex items-center">
                                    <img :src="`https://ui-avatars.com/api/?name=${row.name}&background=random`" alt="Avatar" class="h-12 w-12 rounded-full mr-4">
                                    <div>
                                        <div class="font-bold text-lg text-gray-900 dark:text-white">{{ row.name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ row.email }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-4 text-center w-full md:w-auto">
                                    <div>
                                        <div class="text-xs text-gray-500">Recuperado</div>
                                        <div class="font-semibold text-gray-800 dark:text-gray-200">{{ formatCurrency(row.total_recovered) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500"># Casos</div>
                                        <div class="font-semibold text-gray-800 dark:text-gray-200">{{ row.casos_count }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500"># Coops</div>
                                        <div class="font-semibold text-gray-800 dark:text-gray-200">{{ row.cooperativas_count }}</div>
                                    </div>
                                </div>

                                <div class="flex-shrink-0">
                                    <SecondaryButton @click="openRowId = openRowId === row.id ? null : row.id">
                                        {{ openRowId === row.id ? 'Ocultar' : 'Ver Detalle' }}
                                    </SecondaryButton>
                                </div>
                            </div>
                            
                            <transition name="fade">
                                <div v-if="openRowId === row.id" class="border-t border-gray-200 dark:border-gray-700 p-4">
                                    <div class="grid md:grid-cols-2 gap-6">
                                        <div>
                                            <h3 class="font-semibold mb-2 text-gray-800 dark:text-gray-200">Casos Asignados</h3>
                                            <ul class="space-y-2 text-sm max-h-60 overflow-y-auto pr-2">
                                                <li v-for="caso in row.cases" :key="caso.id" class="flex justify-between items-center p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    <div>
                                                        <Link :href="route('casos.show', caso.id)" class="font-medium text-indigo-600 hover:underline">Caso #{{ caso.id }}</Link>
                                                        <span class="text-gray-500 text-xs block">{{ caso.cooperativa?.name || 'N/A' }}</span>
                                                    </div>
                                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ formatCurrency(caso.recovered) }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold mb-2 text-gray-800 dark:text-gray-200">Cooperativas Asignadas</h3>
                                            <ul class="space-y-1 text-sm">
                                                <li v-for="coop in row.cooperativas" :key="coop.id">
                                                    <Link :href="route('cooperativas.show', coop.id)" class="text-indigo-600 hover:underline">{{ coop.name }}</Link>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>