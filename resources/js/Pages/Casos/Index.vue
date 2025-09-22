<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { DocumentDuplicateIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    casos: Array,
    can: Object,
});

// Lógica para el modal de confirmación
const confirmingCaseDeletion = ref(false);
const caseToDelete = ref(null);
const form = useForm({});

const confirmCaseDeletion = (caso) => {
    caseToDelete.value = caso;
    confirmingCaseDeletion.value = true;
};

const deleteCase = () => {
    form.delete(route('casos.destroy', caseToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    confirmingCaseDeletion.value = false;
    caseToDelete.value = null;
};

// Lógica de colores y formato (sin cambios)
const statusColorClasses = {
    red: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    green: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    gray: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
};
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const userTimezoneOffset = date.getTimezoneOffset() * 60000;
    return new Date(date.getTime() + userTimezoneOffset).toLocaleDateString('es-CO', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>

<template>
    <Head title="Gestión de Casos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Gestión de Casos
                </h2>
                <Link :href="route('casos.create')" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Registrar Nuevo Caso
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Deudor</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cooperativa</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado Actual</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha Apertura</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Abogado/Gestor</th>
                                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Acciones</span></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="caso in casos" :key="caso.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div v-if="caso.clonado_de_id" class="mr-3 flex-shrink-0" :title="'Clon del caso #' + caso.clonado_de_id">
                                                    <DocumentDuplicateIcon class="h-5 w-5 text-blue-400" />
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ caso.deudor.nombre_completo }}</div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">Doc. {{ caso.deudor.numero_documento }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ caso.cooperativa ? caso.cooperativa.nombre : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="caso.semaforo" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="statusColorClasses[caso.semaforo.color] || statusColorClasses['gray']">
                                                {{ caso.semaforo.text }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ formatDate(caso.fecha_apertura) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ caso.user ? caso.user.name : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                            <Link :href="route('casos.show', caso.id)" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">Ver Ficha</Link>
                                            <button v-if="can.delete_cases" @click="confirmCaseDeletion(caso)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="confirmingCaseDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    ¿Estás seguro de que quieres eliminar este caso?
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" v-if="caseToDelete">
                    Estás a punto de eliminar permanentemente el Caso #{{ caseToDelete.id }} del deudor <span class="font-bold">{{ caseToDelete.deudor.nombre_completo }}</span>. Esta acción no se puede deshacer.
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cancelar </SecondaryButton>
                    <DangerButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="deleteCase">
                        Sí, Eliminar Caso
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
