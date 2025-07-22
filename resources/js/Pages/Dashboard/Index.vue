<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { debounce, pickBy } from 'lodash';
import { Bar, Line, Pie } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement);

const props = defineProps({
    kpis: Object,
    chartData: Object,
    cooperativas: Array,
    filters: Object,
    rankingAbogados: Array,
});

const form = useForm({
    cooperativa_id: props.filters.cooperativa_id || '',
    fecha_desde: props.filters.fecha_desde || '',
    fecha_hasta: props.filters.fecha_hasta || '',
});

watch(form, debounce(function() {
    form.get(route('dashboard'), pickBy(form.data()), {
        preserveState: true,
        replace: true,
    });
}, 300), { deep: true });

// --- LÓGICA DE LOS GRÁFICOS (VERSIÓN ROBUSTA) ---

// Usamos el operador 'optional chaining' (?.) y el 'nullish coalescing' (??)
// para asegurarnos de que siempre tengamos un objeto con el que trabajar, incluso si los datos no vienen.
const casosPorEstadoData = props.chartData?.casosPorEstado ?? {};
const casosChartData = {
    labels: Object.keys(casosPorEstadoData).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
    datasets: [{
        label: 'Total de Casos',
        backgroundColor: '#4F46E5',
        borderRadius: 4,
        data: Object.values(casosPorEstadoData),
    }],
};

const incidentesPorMesData = props.chartData?.incidentesPorMes ?? {};
const incidentesChartData = {
    labels: Object.keys(incidentesPorMesData),
    datasets: [{
        label: 'Incidentes Registrados',
        borderColor: '#F97316',
        backgroundColor: '#F97316',
        tension: 0.3,
        data: Object.values(incidentesPorMesData),
    }],
};

const validacionesPorEstadoData = props.chartData?.validacionesPorEstado ?? {};
const validacionesChartData = {
    labels: ['Cumple', 'Incumple'],
    datasets: [{
        backgroundColor: ['#10B981', '#EF4444'],
        data: [
            validacionesPorEstadoData.cumple || 0,
            validacionesPorEstadoData.incumple || 0,
        ],
    }],
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
};

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '$ 0';
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(value);
};
</script>

<template>
    <Head title="Dashboard de Analítica" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard de Analítica General
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="cooperativa_id" class="block text-sm font-medium text-gray-700">Cooperativa</label>
                            <select v-model="form.cooperativa_id" id="cooperativa_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Todas</option>
                                <option v-for="cooperativa in cooperativas" :key="cooperativa.id" :value="cooperativa.id">
                                    {{ cooperativa.nombre }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label for="fecha_desde" class="block text-sm font-medium text-gray-700">Desde</label>
                            <input type="date" v-model="form.fecha_desde" id="fecha_desde" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="fecha_hasta" class="block text-sm font-medium text-gray-700">Hasta</label>
                            <input type="date" v-model="form.fecha_hasta" id="fecha_hasta" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <h3 class="text-sm font-medium text-gray-500">Casos Activos</h3>
                        <p class="mt-1 text-3xl font-semibold text-gray-900">{{ kpis.casos_activos }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <h3 class="text-sm font-medium text-gray-500">Casos en Demanda</h3>
                        <p class="mt-1 text-3xl font-semibold text-gray-900">{{ kpis.casos_demandados }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <h3 class="text-sm font-medium text-gray-500">Mora Total Activa</h3>
                        <p class="mt-1 text-3xl font-semibold text-gray-900">${{ kpis.mora_total }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm" :class="kpis.cumplimiento_legal < 90 ? 'bg-red-50' : 'bg-green-50'">
                        <h3 class="text-sm font-medium text-gray-500">Nivel de Cumplimiento</h3>
                        <p class="mt-1 text-3xl font-semibold" :class="kpis.cumplimiento_legal < 90 ? 'text-red-600' : 'text-green-600'">
                            {{ kpis.cumplimiento_legal }}%
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-lg shadow-sm">
                            <h3 class="font-semibold text-lg text-gray-900 mb-4">Distribución de Casos por Estado</h3>
                            <div class="h-80">
                                <Bar :data="casosChartData" :options="chartOptions" />
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-sm">
                            <h3 class="font-semibold text-lg text-gray-900 mb-4">Nivel de Cumplimiento Legal</h3>
                            <div class="h-80">
                                <Pie :data="validacionesChartData" :options="chartOptions" />
                            </div>
                        </div>
                        <div class="md:col-span-2 bg-white p-6 rounded-lg shadow-sm">
                            <h3 class="font-semibold text-lg text-gray-900 mb-4">Tendencia de Incidentes (Último Año)</h3>
                            <div class="h-80">
                                <Line :data="incidentesChartData" :options="chartOptions" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <h3 class="font-semibold text-lg text-gray-900 mb-4">Ranking de Abogados</h3>
                        <p class="text-sm text-gray-500 mb-4">Top 3 por monto recuperado en el período.</p>
                        <ol class="space-y-4">
                            <li v-for="(abogado, index) in rankingAbogados" :key="abogado.id" class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center text-white"
                                     :class="{
                                         'bg-yellow-400': index === 0,
                                         'bg-gray-400': index === 1,
                                         'bg-yellow-600': index === 2,
                                     }">
                                    <span class="font-bold">{{ index + 1 }}</span>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">{{ abogado.name }}</p>
                                    <p class="text-sm text-gray-500">{{ formatCurrency(abogado.total_recuperado) }}</p>
                                </div>
                            </li>
                            <li v-if="rankingAbogados.length === 0" class="text-center text-gray-500 py-8">
                                No hay datos de recuperación para mostrar.
                            </li>
                        </ol>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
