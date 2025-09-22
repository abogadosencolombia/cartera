<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Multiselect from 'vue-multiselect'; // Importamos el componente multiselect

// Cambiamos el nombre del prop para evitar conflictos y ser más claros
defineProps({
    allCooperativas: Array,
    personas: Array,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    tipo_usuario: 'cliente', // El rol por defecto ahora es 'cliente'
    // El campo debe ser 'cooperativas' (plural) y un array para el multiselect
    cooperativas: [], 
    persona_id: null,
});

const submit = () => {
    form.post(route('admin.users.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<!-- Es importante importar los estilos para vue-multiselect -->
<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<template>
    <Head title="Crear Usuario" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Registrar Nuevo Usuario
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 md:p-8 text-gray-900 dark:text-gray-100">
                        
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
                                Complete los datos del nuevo usuario
                            </h3>
                            <Link :href="route('admin.users.index')" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                                &larr; Volver al listado
                            </Link>
                        </div>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <!-- Campos de Nombre, Email, Contraseña (sin cambios) -->
                            <div>
                                <InputLabel for="name" value="Nombre Completo" />
                                <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>
                            <div>
                                <InputLabel for="email" value="Email" />
                                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>
                            <div>
                                <InputLabel for="password" value="Contraseña" />
                                <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required />
                                <InputError class="mt-2" :message="form.errors.password" />
                            </div>
                             <div>
                                <InputLabel for="password_confirmation" value="Confirmar Contraseña" />
                                <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" required />
                                <InputError class="mt-2" :message="form.errors.password_confirmation" />
                            </div>
                            
                            <!-- Selector de Rol (sin cambios) -->
                            <div>
                                <InputLabel for="tipo_usuario" value="Tipo de Usuario" />
                                <select id="tipo_usuario" v-model="form.tipo_usuario" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                    <option value="admin">Administrador</option>
                                    <option value="gestor">Gestor</option>
                                    <option value="abogado">Abogado</option>
                                    <option value="cliente">Cliente</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.tipo_usuario" />
                            </div>
                            
                            <!-- ===== ¡LA CORRECCIÓN ESTÁ AQUÍ! ===== -->
                            <!-- El selector de cooperativas ahora se muestra para todos los roles excepto 'admin' -->
                            <div v-if="form.tipo_usuario !== 'admin'">
                                <InputLabel value="Asignar a Cooperativa(s)" />
                                <Multiselect
                                    v-model="form.cooperativas"
                                    :options="allCooperativas.map(c => c.id)"
                                    :custom-label="opt => allCooperativas.find(c => c.id === opt)?.nombre"
                                    :multiple="true"
                                    placeholder="Seleccione una o más cooperativas"
                                />
                                <InputError :message="form.errors.cooperativas" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Registrar Usuario
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>