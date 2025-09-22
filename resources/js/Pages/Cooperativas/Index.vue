<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    cooperativas: Array,
    can: Object, // Recibimos los permisos del controlador
});

const page = usePage();

// Lógica para el modal de confirmación de borrado
const confirmingDeletion = ref(false);
const itemToDelete = ref(null);
const form = useForm({});

const confirmDeletion = (item) => {
    itemToDelete.value = item;
    confirmingDeletion.value = true;
};

const deleteItem = () => {
    form.delete(route('cooperativas.destroy', itemToDelete.value.id), {
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
    <Head title="Módulo de Cooperativas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Módulo de Cooperativas
                </h2>
                <!-- El botón de crear ahora también es condicional -->
                <Link v-if="can.create_cooperativas" :href="route('cooperativas.create')" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Crear Cooperativa
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="page.props.flash.success" class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md shadow-md" role="alert">
                    <p class="font-bold">¡Éxito!</p>
                    <p>{{ page.props.flash.success }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-2xl font-bold mb-6">Listado de Cooperativas</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">NIT</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Representante Legal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contacto</th>
                                        <th class="relative px-6 py-3"><span class="sr-only">Acciones</span></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-if="cooperativas.length === 0">
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No hay cooperativas para mostrar.
                                        </td>
                                    </tr>
                                    <tr v-else v-for="coop in cooperativas" :key="coop.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ coop.nombre }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ coop.NIT }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ coop.representante_legal_nombre }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ coop.contacto_correo }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                            <Link :href="route('cooperativas.show', coop.id)" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">Ver</Link>
                                            
                                            <!-- BOTÓN DE ELIMINAR (SOLO PARA ADMINS) -->
                                            <button v-if="can.delete_cooperativas" @click="confirmDeletion(coop)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
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

        <!-- MODAL DE CONFIRMACIÓN DE ELIMINACIÓN -->
        <Modal :show="confirmingDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    ¿Estás seguro de que quieres eliminar esta cooperativa?
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400" v-if="itemToDelete">
                    Estás a punto de eliminar permanentemente la cooperativa <span class="font-bold">{{ itemToDelete.nombre }}</span>. Todos sus casos, usuarios y documentos asociados también serán eliminados. Esta acción no se puede deshacer.
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cancelar </SecondaryButton>
                    <DangerButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="deleteItem">
                        Sí, Eliminar Cooperativa
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
