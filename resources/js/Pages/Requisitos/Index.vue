<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    requisitos: Array,
    cooperativas: Array,
    tipos_proceso: Array,
    tipos_documento: Array,
});

const page = usePage();

// Formulario para crear un nuevo requisito
const form = useForm({
    cooperativa_id: null, // null significa que aplica a todas
    tipo_proceso: props.tipos_proceso[0],
    tipo_documento_requerido: props.tipos_documento[0],
});

const submit = () => {
    form.post(route('requisitos.store'), {
        onSuccess: () => form.reset('tipo_documento_requerido'),
    });
};

// Lógica para el modal de confirmación de borrado
const confirmingDeletion = ref(false);
const itemToDelete = ref(null);

const confirmDeletion = (requisito) => {
    itemToDelete.value = requisito;
    confirmingDeletion.value = true;
};

const deleteItem = () => {
    useForm({}).delete(route('requisitos.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    confirmingDeletion.value = false;
    itemToDelete.value = null;
};

</script>

<template>
    <Head title="Gestión de Requisitos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Gestión de Requisitos de Documentos
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Columna del Formulario para Crear Reglas -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Crear Nueva Regla
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Define qué documentos son obligatorios para cada tipo de proceso.
                        </p>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="cooperativa_id" value="Aplicar a Cooperativa (Opcional)" />
                                <select v-model="form.cooperativa_id" id="cooperativa_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                    <option :value="null">Todas las Cooperativas</option>
                                    <option v-for="coop in cooperativas" :key="coop.id" :value="coop.id">{{ coop.nombre }}</option>
                                </select>
                            </div>

                            <div>
                                <InputLabel for="tipo_proceso" value="Para el Tipo de Proceso" />
                                <select v-model="form.tipo_proceso" id="tipo_proceso" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                    <option v-for="tipo in tipos_proceso" :key="tipo" :value="tipo">{{ tipo }}</option>
                                </select>
                            </div>

                            <div>
                                <InputLabel for="tipo_documento_requerido" value="El Documento Requerido es" />
                                <select v-model="form.tipo_documento_requerido" id="tipo_documento_requerido" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                    <option v-for="tipo in tipos_documento" :key="tipo" :value="tipo">{{ tipo }}</option>
                                </select>
                            </div>

                            <div class="flex items-center justify-end">
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Guardar Regla
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Columna de la Tabla de Reglas Existentes -->
                <div class="lg:col-span-2">
                    <div v-if="page.props.flash.success" class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md shadow-md" role="alert">
                        <p class="font-bold">¡Éxito!</p>
                        <p>{{ page.props.flash.success }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            <h3 class="text-lg font-medium mb-4">Reglas Actuales</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aplica a</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo de Proceso</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Documento Requerido</th>
                                            <th class="relative px-6 py-3"><span class="sr-only">Eliminar</span></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-if="requisitos.length === 0">
                                            <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                                No hay requisitos definidos.
                                            </td>
                                        </tr>
                                        <tr v-else v-for="req in requisitos" :key="req.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                {{ req.cooperativa ? req.cooperativa.nombre : 'Todas' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ req.tipo_proceso }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ req.tipo_documento_requerido }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button @click="confirmDeletion(req)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
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
        </div>

        <!-- Modal de Confirmación -->
        <Modal :show="confirmingDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    ¿Eliminar Requisito?
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400" v-if="itemToDelete">
                    Estás a punto de eliminar la regla que requiere el documento <span class="font-bold">{{ itemToDelete.tipo_documento_requerido }}</span> para los casos de tipo <span class="font-bold">{{ itemToDelete.tipo_proceso }}</span>. Esta acción no se puede deshacer.
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cancelar </SecondaryButton>
                    <DangerButton class="ms-3" @click="deleteItem">
                        Sí, Eliminar
                    </DangerButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>