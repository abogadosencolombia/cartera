<script setup>
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement } from 'chart.js';
import { ArrowUpIcon, ArrowDownIcon } from '@heroicons/vue/24/solid';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement);

const props = defineProps({
    title: String,
    metric: [String, Number],
    trend: Object, // { value: String, direction: 'up' | 'down' }
    chartData: {
        type: Array,
        default: () => [],
    },
    icon: Object,
    color: {
        type: String,
        default: 'indigo',
    }
});

// El mapa de colores funciona perfectamente, no necesita cambios.
const colorMap = {
    indigo: { container: 'bg-indigo-100 dark:bg-indigo-900/50', icon: 'text-indigo-600 dark:text-indigo-400', sparkline: '#4f46e5' },
    orange: { container: 'bg-orange-100 dark:bg-orange-900/50', icon: 'text-orange-600 dark:text-orange-400', sparkline: '#f97316' },
    red: { container: 'bg-red-100 dark:bg-red-900/50', icon: 'text-red-600 dark:text-red-400', sparkline: '#dc2626' },
    green: { container: 'bg-green-100 dark:bg-green-900/50', icon: 'text-green-600 dark:text-green-400', sparkline: '#16a34a' },
};
const selectedColor = computed(() => colorMap[props.color] || colorMap.indigo);

const trendClasses = computed(() => {
    return props.trend?.direction === 'up'
        ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300'
        : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300';
});

const sparklineData = {
    labels: Array(props.chartData.length).fill(''),
    datasets: [{
        data: props.chartData,
        borderColor: selectedColor.value.sparkline,
        borderWidth: 2,
        tension: 0.4,
        pointRadius: 0,
    }],
};

const sparklineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { enabled: false } },
    scales: { x: { display: false }, y: { display: false } }
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-1 flex flex-col justify-between">
        <div>
            <div class="flex justify-between items-center">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 flex items-center">
                    <component :is="icon" class="h-5 w-5 mr-2" :class="selectedColor.icon" />
                    {{ title }}
                </h3>
                <div v-if="trend" class="flex items-center text-xs font-semibold px-2 py-1 rounded-full" :class="trendClasses">
                    <ArrowUpIcon v-if="trend.direction === 'up'" class="h-4 w-4" />
                    <ArrowDownIcon v-else class="h-4 w-4" />
                    <span class="ml-1">{{ trend.value }}</span>
                </div>
            </div>

            <p class="mt-2 text-2xl xl:text-3xl font-bold text-gray-900 dark:text-white truncate">
                {{ metric }}
            </p>
        </div>
        
        <div v-if="chartData.length" class="mt-4 h-12">
            <Line :data="sparklineData" :options="sparklineOptions" />
        </div>
    </div>
</template>