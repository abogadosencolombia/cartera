<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import KpiCard from './Partials/KpiCard.vue';
import ChartCard from './Partials/ChartCard.vue';
import { Head, useForm, usePage, router, Link } from '@inertiajs/vue3';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { ref, computed, watch } from 'vue';
import { debounce, pickBy } from 'lodash';
import { Bar, Line, Pie } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement } from 'chart.js';
import { FolderIcon, ScaleIcon, BanknotesIcon, ShieldCheckIcon, UserGroupIcon, ArrowPathIcon, UserCircleIcon, ChatBubbleLeftRightIcon, EnvelopeIcon } from '@heroicons/vue/24/outline';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement);

const props = defineProps({
    kpis: Object,
    chartData: Object,
    cooperativas: Array,
    filters: Object,
    rankingAbogados: Array,
    userRole: String,
    kpis_comparison: {
        type: Object,
        default: () => ({
            casos_activos: { value: '+5.2%', direction: 'up' },
            casos_demandados: { value: '-1.0%', direction: 'down' },
            mora_total: { value: '+2.1%', direction: 'up' },
            cumplimiento_legal: { value: '+0.5%', direction: 'up' },
        })
    },
    sparkline_data: {
        type: Object,
        default: () => ({
            casos: [30, 41, 35, 51, 49, 62, 69],
            demanda: [1, 2, 1, 3, 2, 4, 3],
            mora: [10.8, 12.1, 9.5, 11.3, 13.0, 12.5, 14.2],
            cumplimiento: [28, 30, 31, 29, 32, 33, 31.3],
        })
    }
});

const user = computed(() => usePage().props.auth.user);
const form = useForm({
    cooperativa_id: props.filters?.cooperativa_id || '',
    fecha_desde: props.filters?.fecha_desde || '',
    fecha_hasta: props.filters?.fecha_hasta || '',
});
const resetFilters = () => form.reset();
watch(() => form.data(), debounce(function(currentForm) {
    router.get(route('dashboard'), pickBy(currentForm), { preserveState: true, replace: true });
}, 500), { deep: true });

const mostrandoModalContacto = ref(false);
const abrirModalContacto = () => {
    contactForm.asunto = `Consulta sobre mi caso - Cliente: ${user.value.name}`;
    mostrandoModalContacto.value = true;
};
const cerrarModalContacto = () => {
    mostrandoModalContacto.value = false;
    contactForm.reset();
};
const contactForm = useForm({
    asunto: '',
    mensaje: '',
});
const enviarMensaje = () => {
    contactForm.post(route('contacto.cliente.enviar'), {
        preserveScroll: true,
        onSuccess: () => cerrarModalContacto(),
    });
};

const formatCurrency = (value) => {
    const numberValue = parseFloat(value);
    if (!isFinite(numberValue)) return '$ 0';
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(numberValue);
};

const casosPorEstadoData = computed(() => {
    const data = props.chartData?.casosPorEstado ?? {};
    return {
        labels: Object.keys(data).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
        datasets: [{ label: 'Total de Casos', backgroundColor: '#4338CA', borderRadius: 6, data: Object.values(data) }],
    };
});
const incidentesChartData = computed(() => {
    const data = props.chartData?.incidentesPorMes ?? {};
    return {
        labels: Object.keys(data),
        datasets: [{ label: 'Incidentes', borderColor: '#F97316', backgroundColor: 'rgba(249, 115, 22, 0.1)', fill: true, tension: 0.4, data: Object.values(data) }],
    };
});
const validacionesPorEstadoData = computed(() => {
    const data = props.chartData?.validacionesPorEstado ?? {};
    return {
        labels: ['Cumple', 'Incumple'],
        datasets: [{ backgroundColor: ['#10B981', '#EF4444'], borderColor: ['#fff', '#fff'], borderWidth: 2, data: [ data.cumple || 0, data.incumple || 0 ] }],
    };
});
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true, pointStyle: 'circle' } } },
    interaction: { intersect: false, mode: 'index' },
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-900/50">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <template v-if="userRole !== 'cliente'">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-4">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Hola de nuevo, {{ user.name }}</h1>
                                <p class="text-gray-500 dark:text-gray-400 mt-1">Aquí tienes un resumen del estado actual de la cartera.</p>
                            </div>
                            <div class="flex-shrink-0">
                                <SecondaryButton @click="resetFilters">
                                    <ArrowPathIcon class="h-5 w-5 mr-2"/> Reiniciar Filtros
                                </SecondaryButton>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="cooperativa_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cooperativa</label>
                                    <select v-model="form.cooperativa_id" id="cooperativa_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">Todas</option>
                                        <option v-for="cooperativa in cooperativas" :key="cooperativa.id" :value="cooperativa.id">{{ cooperativa.nombre }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="fecha_desde" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Desde</label>
                                    <input type="date" v-model="form.fecha_desde" id="fecha_desde" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm">
                                </div>
                                <div>
                                    <label for="fecha_hasta" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hasta</label>
                                    <input type="date" v-model="form.fecha_hasta" id="fecha_hasta" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <KpiCard title="Casos Activos" :metric="kpis.casos_activos" :icon="FolderIcon" color="indigo" :trend="kpis_comparison.casos_activos" :chartData="sparkline_data.casos"/>
                        <KpiCard title="Casos en Demanda" :metric="kpis.casos_demandados" :icon="ScaleIcon" color="orange" :trend="kpis_comparison.casos_demandados" :chartData="sparkline_data.demanda"/>
                        <KpiCard title="Saldo Total Activo" :metric="formatCurrency(kpis.mora_total)" :icon="BanknotesIcon" color="red" :trend="kpis_comparison.mora_total" :chartData="sparkline_data.mora"/>
                        <KpiCard title="Nivel de Cumplimiento" :metric="`${kpis.cumplimiento_legal}%`" :icon="ShieldCheckIcon" color="green" :trend="kpis_comparison.cumplimiento_legal" :chartData="sparkline_data.cumplimiento"/>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <ChartCard title="Distribución de Casos por Estado"><Bar :data="casosPorEstadoData" :options="chartOptions" /></ChartCard>
                            <ChartCard title="Tendencia de Incidentes" subtitle="Registros durante el último año"><Line :data="incidentesChartData" :options="chartOptions" /></ChartCard>
                        </div>
                        <div class="lg:col-span-1 space-y-6">
                            <ChartCard title="Nivel de Cumplimiento Legal"><div class="h-64 flex items-center justify-center"><Pie :data="validacionesPorEstadoData" :options="chartOptions" /></div></ChartCard>
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
                                <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4 flex items-center"><UserGroupIcon class="h-6 w-6 mr-2 text-yellow-500"/> Ranking de Abogados</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Top 3 por monto recuperado.</p>
                                <ol class="space-y-3">
                                    <li v-for="(abogado, index) in rankingAbogados" :key="abogado.id" class="flex items-center p-3 rounded-lg transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center text-white font-bold text-lg" :class="{'bg-yellow-400': index === 0, 'bg-gray-400': index === 1, 'bg-amber-600': index === 2}">{{ index + 1 }}</div>
                                        <div class="ml-4"><p class="text-sm font-medium text-gray-900 dark:text-white">{{ abogado.name }}</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ formatCurrency(abogado.total_recuperado) }}</p></div>
                                    </li>
                                    <li v-if="!rankingAbogados || rankingAbogados.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">No hay datos de recuperación.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-if="userRole === 'cliente'">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Bienvenido(a), {{ user.name }}</h1>
                                <p class="text-gray-500 dark:text-gray-400 mt-1">Este es el resumen de tu estado de cuenta actual.</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-red-50 dark:bg-red-900/50 p-6 rounded-2xl shadow-lg border-l-4 border-red-500">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0 bg-red-100 dark:bg-red-900 p-3 rounded-full"><BanknotesIcon class="h-8 w-8 text-red-600 dark:text-red-400"/></div>
                                        <div>
                                            <h3 class="text-base font-medium text-red-800 dark:text-red-200">Saldo Pendiente Total</h3>
                                            <p class="mt-1 text-4xl font-bold text-red-700 dark:text-red-300">{{ formatCurrency(kpis.saldo_total_pendiente) }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border-l-4 border-indigo-500">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0 bg-indigo-100 dark:bg-indigo-900/50 p-3 rounded-full"><FolderIcon class="h-8 w-8 text-indigo-600 dark:text-indigo-400"/></div>
                                        <div>
                                            <h3 class="text-base font-medium text-gray-500 dark:text-gray-400">Casos Activos a tu nombre</h3>
                                            <p class="mt-1 text-4xl font-bold text-gray-900 dark:text-white">{{ kpis.casos_activos }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="lg:col-span-1 space-y-6">
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
                                <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4">Contacte a su Gestor</h3>
                                <div class="space-y-4">
                                    <a href="https://wa.me/573152819233" target="_blank" class="w-full group flex items-center p-3 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-700/50">
                                        <div class="flex-shrink-0 bg-green-100 dark:bg-green-900/50 p-2 rounded-full">
                                            <ChatBubbleLeftRightIcon class="h-6 w-6 text-green-600 dark:text-green-400"/>
                                        </div>
                                        <span class="ml-4 text-sm font-medium text-gray-800 dark:text-gray-200">Escribir por WhatsApp</span>
                                    </a>
                                    <button @click="abrirModalContacto" class="w-full text-left flex items-center p-3 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-700/50">
                                        <div class="flex-shrink-0 bg-blue-100 dark:bg-blue-900/50 p-2 rounded-full">
                                            <EnvelopeIcon class="h-6 w-6 text-blue-600 dark:text-blue-400"/>
                                        </div>
                                        <span class="ml-4 text-sm font-medium text-gray-800 dark:text-gray-200">Enviar un Correo</span>
                                    </button>
                                </div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
                                <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4">Mi Cuenta</h3>
                                <Link :href="route('profile.edit')" class="flex items-center p-3 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-700/50">
                                    <div class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 p-2 rounded-full">
                                        <UserCircleIcon class="h-6 w-6 text-gray-600 dark:text-gray-400"/>
                                    </div>
                                    <span class="ml-4 text-sm font-medium text-gray-800 dark:text-gray-200">Editar mi Perfil</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        
        <Modal :show="mostrandoModalContacto" @close="cerrarModalContacto">
            <form @submit.prevent="enviarMensaje" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Enviar Mensaje a mi Gestor</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Tu mensaje será enviado directamente a la firma de abogados.</p>
                <div class="mt-6">
                    <InputLabel for="asunto" value="Asunto" />
                    <TextInput id="asunto" v-model="contactForm.asunto" type="text" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="contactForm.errors.asunto" />
                </div>
                <div class="mt-6">
                    <InputLabel for="mensaje" value="Mensaje" />
                    <Textarea id="mensaje" v-model="contactForm.mensaje" class="mt-1 block w-full" rows="6" required />
                    <InputError class="mt-2" :message="contactForm.errors.mensaje" />
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="cerrarModalContacto"> Cancelar </SecondaryButton>
                    <PrimaryButton class="ms-3" :class="{ 'opacity-25': contactForm.processing }" :disabled="contactForm.processing">
                        Enviar Mensaje
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>