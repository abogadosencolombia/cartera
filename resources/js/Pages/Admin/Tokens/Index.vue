<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
// ===== ¡LA CORRECCIÓN ESTÁ AQUÍ! =====
// Añadimos 'router' a la lista de importaciones desde Inertia.
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

// Define props received from the controller
const props = defineProps({
    tokens: Object,
});

// Flash message handling
const flash = ref(usePage().props.flash);
watch(() => usePage().props.flash, (newFlash) => {
    flash.value = newFlash;
    if (newFlash.success) {
        setTimeout(() => {
            flash.value.success = null;
        }, 3000);
    }
});

// Confirmation modal for deletion
const showConfirmModal = ref(false);
const tokenToDelete = ref(null);

const confirmDeletion = (token) => {
    tokenToDelete.value = token;
    showConfirmModal.value = true;
};

const deleteToken = () => {
    if (tokenToDelete.value) {
        // Ahora 'router' está definido y esta línea funcionará.
        router.delete(route('admin.tokens.destroy', tokenToDelete.value.id), {
            onFinish: () => {
                showConfirmModal.value = false;
                tokenToDelete.value = null;
            }
        });
    }
};
</script>

<template>
    <!-- El resto del template sigue exactamente igual, no necesita cambios -->
    <Head title="Gestión de Credenciales" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Gestión de Credenciales de Integración
                </h2>
                <Link :href="route('admin.tokens.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition">
                    Añadir Credencial
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Flash Message -->
                <div v-if="flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ flash.success }}</span>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Última Actualización</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                <tr v-for="token in tokens.data" :key="token.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ token.proveedor }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="token.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ token.activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ new Date(token.updated_at).toLocaleString() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium">
                                        <Link :href="route('admin.tokens.edit', token.id)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Editar</Link>
                                        <button @click="confirmDeletion(token)" class="ml-4 text-red-600 hover:text-red-900 dark:text-red-400">Eliminar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deletion Confirmation Modal -->
        <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Confirmar Eliminación</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        ¿Estás seguro de que deseas eliminar la credencial para "{{ tokenToDelete.proveedor }}"? Esta acción no se puede deshacer.
                    </p>
                    <div class="mt-6 flex justify-end space-x-4">
                        <button @click="showConfirmModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancelar</button>
                        <button @click="deleteToken" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
