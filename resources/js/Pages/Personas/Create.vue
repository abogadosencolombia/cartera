<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    nombre_completo: '',
    tipo_documento: 'CC',
    numero_documento: '',
    telefono_fijo: '',
    celular_1: '',
    celular_2: '',
    correo_1: '',
    correo_2: '',
    direccion: '',
    ciudad: '',
    empresa: '',
    cargo: '',
    observaciones: '',
});

const submit = () => {
    form.post(route('personas.store'));
};
</script>

<template>
    <Head title="Registrar Persona" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Registrar Nueva Persona
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 md:p-8">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium">Datos de la Persona</h3>
                             <Link :href="route('personas.index')" class="text-sm text-gray-600 hover:underline">&larr; Volver al listado</Link>
                        </div>
                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <InputLabel for="nombre_completo" value="Nombre Completo" />
                                    <TextInput v-model="form.nombre_completo" id="nombre_completo" type="text" class="mt-1 block w-full" required autofocus />
                                    <InputError :message="form.errors.nombre_completo" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="tipo_documento" value="Tipo de Documento" />
                                    <select v-model="form.tipo_documento" id="tipo_documento" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                        <option>CC</option>
                                        <option>CE</option>
                                        <option>NIT</option>
                                        <option>Pasaporte</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="numero_documento" value="Número de Documento" />
                                    <TextInput v-model="form.numero_documento" id="numero_documento" type="text" class="mt-1 block w-full" required />
                                    <InputError :message="form.errors.numero_documento" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="celular_1" value="Celular Principal" />
                                    <TextInput v-model="form.celular_1" id="celular_1" type="text" class="mt-1 block w-full" required />
                                    <InputError :message="form.errors.celular_1" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="celular_2" value="Celular Secundario (Opcional)" />
                                    <TextInput v-model="form.celular_2" id="celular_2" type="text" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel for="correo_1" value="Correo Principal" />
                                    <TextInput v-model="form.correo_1" id="correo_1" type="email" class="mt-1 block w-full" required />
                                    <InputError :message="form.errors.correo_1" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="correo_2" value="Correo Secundario (Opcional)" />
                                    <TextInput v-model="form.correo_2" id="correo_2" type="email" class="mt-1 block w-full" />
                                </div>
                                <div class="md:col-span-2">
                                    <InputLabel for="direccion" value="Dirección" />
                                    <TextInput v-model="form.direccion" id="direccion" type="text" class="mt-1 block w-full" required />
                                    <InputError :message="form.errors.direccion" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="ciudad" value="Ciudad" />
                                    <TextInput v-model="form.ciudad" id="ciudad" type="text" class="mt-1 block w-full" required />
                                    <InputError :message="form.errors.ciudad" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="empresa" value="Empresa (Opcional)" />
                                    <TextInput v-model="form.empresa" id="empresa" type="text" class="mt-1 block w-full" />
                                </div>
                                <div class="md:col-span-2">
                                    <InputLabel for="observaciones" value="Observaciones (Opcional)" />
                                    <Textarea v-model="form.observaciones" id="observaciones" class="mt-1 block w-full" rows="3"></Textarea>
                                </div>
                            </div>
                            <div class="flex justify-end mt-6">
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Guardar Persona
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
