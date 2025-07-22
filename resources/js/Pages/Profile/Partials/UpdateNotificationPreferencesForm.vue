<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { Transition } from 'vue';

const user = usePage().props.auth.user;

const form = useForm({
    preferencias_notificacion: {
        email: user.preferencias_notificacion?.email ?? true,
        'in-app': user.preferencias_notificacion?.['in-app'] ?? true,
    },
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Preferencias de Notificación</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Seleccione cómo desea recibir las alertas automáticas del sistema.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div class="space-y-4">
                 <label class="flex items-center">
                    <Checkbox v-model="form.preferencias_notificacion['in-app']" :checked="form.preferencias_notificacion['in-app']" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">En la Aplicación (Campana de Notificaciones)</span>
                </label>
                <label class="flex items-center">
                    <Checkbox v-model="form.preferencias_notificacion.email" :checked="form.preferencias_notificacion.email" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Por Correo Electrónico</span>
                </label>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Guardar Preferencias</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Guardado.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
