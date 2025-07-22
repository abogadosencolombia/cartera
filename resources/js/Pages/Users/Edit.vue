<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Multiselect from 'vue-multiselect'; // Importamos el componente

const props = defineProps({
    user: Object,
    allCooperativas: Array,
    personas: Array,
});

const authUser = usePage().props.auth.user;
const isEditingSelf = computed(() => props.user.id === authUser.id);

const form = useForm({
    _method: 'patch', // Método para la actualización
    name: props.user.name,
    email: props.user.email,
    tipo_usuario: props.user.tipo_usuario,
    estado_activo: props.user.estado_activo,
    password: '',
    password_confirmation: '',
    // Inicializamos las cooperativas con los IDs que ya tiene el usuario
    cooperativas: props.user.cooperativas.map(c => c.id),
    persona_id: props.user.persona_id,
    // Aseguramos que las preferencias existan
    preferencias_notificacion: props.user.preferencias_notificacion || { 'email': true, 'in-app': true },
});

const submit = () => {
    // Usamos form.post porque el _method: 'patch' ya le dice a Laravel qué hacer
    form.post(route('admin.users.update', props.user.id), {
        preserveScroll: true,
    });
};
</script>

<!-- Importamos los estilos para vue-multiselect -->
<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<template>
    <Head :title="'Editar Usuario: ' + user.name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editando Usuario: {{ user.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 md:p-8">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Nombre y Email -->
                            <div>
                                <InputLabel for="name" value="Nombre Completo" />
                                <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div>
                                <InputLabel for="email" value="Email" />
                                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required />
                                <InputError :message="form.errors.email" />
                            </div>

                            <!-- Selector de Rol -->
                             <div>
                                <InputLabel for="tipo_usuario" value="Tipo de Usuario" />
                                <SelectInput v-model="form.tipo_usuario" id="tipo_usuario" class="mt-1 block w-full" :disabled="isEditingSelf">
                                    <option value="admin">Administrador</option>
                                    <option value="abogado">Abogado</option>
                                    <option value="gestor">Gestor</option>
                                    <option value="cliente">Cliente</option>
                                 </SelectInput>
                                 <p v-if="isEditingSelf" class="text-xs text-gray-500 mt-1">No puedes cambiar tu propio rol.</p>
                                <InputError :message="form.errors.tipo_usuario" />
                            </div>

                            <!-- Multi-select para Cooperativas -->
                            <div v-if="form.tipo_usuario === 'abogado' || form.tipo_usuario === 'gestor'">
                                <InputLabel value="Cooperativas Asignadas" />
                                <Multiselect
                                    v-model="form.cooperativas"
                                    :options="allCooperativas.map(c => c.id)"
                                    :custom-label="opt => allCooperativas.find(c => c.id === opt)?.nombre"
                                    :multiple="true"
                                    placeholder="Seleccione una o más cooperativas"
                                />
                                <InputError :message="form.errors.cooperativas" />
                            </div>

                            <!-- Selector de Persona Asociada -->
                             <div v-if="form.tipo_usuario === 'cliente'">
                                <InputLabel for="persona_id" value="Persona Asociada a esta Cuenta"/>
                                <SelectInput v-model="form.persona_id" id="persona_id" class="mt-1 block w-full">
                                    <option :value="null">Ninguna / Desvincular</option>
                                    <option v-for="p in personas" :key="p.id" :value="p.id">{{ p.nombre_completo }} ({{ p.numero_documento }})</option>
                                </SelectInput>
                                <InputError :message="form.errors.persona_id" class="mt-2" />
                            </div>

                            <!-- Preferencias de Notificación -->
                            <div class="border-t pt-6">
                                <h3 class="text-base font-semibold text-gray-900">Preferencias de Notificación</h3>
                                <div class="mt-4 space-y-3">
                                    <label class="flex items-center">
                                        <Checkbox v-model:checked="form.preferencias_notificacion['in-app']" />
                                        <span class="ms-2 text-sm text-gray-600">Recibir notificaciones en la aplicación (campana)</span>
                                    </label>
                                    <label class="flex items-center">
                                        <Checkbox v-model:checked="form.preferencias_notificacion.email" />
                                        <span class="ms-2 text-sm text-gray-600">Recibir notificaciones por correo electrónico</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Contraseña y Estado -->
                            <div>
                                <InputLabel for="password" value="Nueva Contraseña (Opcional)" />
                                <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" />
                                <InputError :message="form.errors.password" />
                            </div>
                            <div>
                                <InputLabel for="password_confirmation" value="Confirmar Nueva Contraseña" />
                                <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" />
                            </div>
                            <div class="block mt-4">
                                <label class="flex items-center">
                                    <Checkbox v-model:checked="form.estado_activo" :disabled="isEditingSelf" />
                                    <span class="ms-2 text-sm text-gray-600">Usuario Activo</span>
                                </label>
                                <p v-if="isEditingSelf" class="text-xs text-gray-500 mt-1">No puedes desactivar tu propia cuenta.</p>
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Actualizar Usuario
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
