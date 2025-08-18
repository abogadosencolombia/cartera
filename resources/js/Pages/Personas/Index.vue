<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

import {
  TrashIcon,
  PencilIcon,
  ChatBubbleLeftEllipsisIcon,
  EnvelopeIcon,
} from '@heroicons/vue/24/outline';

defineProps({
  personas: Array,
  can: Object,
});

const page = usePage();

// --- Modal eliminar ---
const confirmingDeletion = ref(false);
const itemToDelete = ref(null);
const form = useForm({});

const confirmDeletion = (item) => {
  itemToDelete.value = item;
  confirmingDeletion.value = true;
};

const deleteItem = () => {
  form.delete(route('personas.destroy', itemToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
  });
};

const closeModal = () => {
  confirmingDeletion.value = false;
  itemToDelete.value = null;
};

// --- Contacto ---
const formatWhatsAppLink = (phone) => {
  if (!phone) return '#';
  const cleaned = String(phone).replace(/\D/g, '');
  return `https://wa.me/57${cleaned}`;
};

const emailSubject = encodeURIComponent('Contacto desde el CRM');
const emailBody = encodeURIComponent('Cordial saludo,');
const gmailCompose = (email) =>
  `https://mail.google.com/mail/?view=cm&fs=1&to=${encodeURIComponent(email)}&su=${emailSubject}&body=${emailBody}`;
const mailtoUrl = (email) => `mailto:${email}?subject=${emailSubject}&body=${emailBody}`;

// Abre Gmail; si falla, usa mailto
const sendEmail = (raw) => {
  if (!raw) return;
  const email = String(raw).trim().toLowerCase();
  const w = window.open(gmailCompose(email), '_blank', 'noopener,noreferrer');
  if (!w) {
    // fallback silencioso si el popup fue bloqueado
    window.location.href = mailtoUrl(email);
  }
};
</script>

<template>
  <Head title="Directorio de Personas" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          Gestión de Personas
        </h2>
        <Link
          :href="route('personas.create')"
          class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
        >
          Registrar Persona
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div
          v-if="page.props.flash.success"
          class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md shadow-md"
          role="alert"
        >
          <p class="font-bold">¡Éxito!</p>
          <p>{{ page.props.flash.success }}</p>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <h3 class="text-2xl font-bold mb-6">Directorio de Personas</h3>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Nombre Completo
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Documento
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Contacto Principal
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Acciones
                    </th>
                  </tr>
                </thead>

                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  <tr
                    v-for="persona in personas"
                    :key="persona.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                  >
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                      {{ persona.nombre_completo }}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {{ persona.tipo_documento }} - {{ persona.numero_documento }}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      <div v-if="persona.celular_1">{{ persona.celular_1 }}</div>
                      <div v-if="persona.correo_1">{{ persona.correo_1 }}</div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="flex justify-end items-center gap-2">
                        <!-- WhatsApp -->
                        <a
                          v-if="persona.celular_1"
                          :href="formatWhatsAppLink(persona.celular_1)"
                          target="_blank"
                          rel="noopener"
                          title="Enviar WhatsApp"
                          class="p-2 rounded-full text-gray-600 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700"
                        >
                          <ChatBubbleLeftEllipsisIcon class="h-5 w-5" />
                        </a>
                        <span v-else class="p-2 rounded-full text-gray-400 cursor-not-allowed" title="Sin celular">
                          <ChatBubbleLeftEllipsisIcon class="h-5 w-5" />
                        </span>

                        <!-- Correo: Gmail con fallback a mailto -->
                        <button
                          v-if="persona.correo_1"
                          type="button"
                          @click="sendEmail(persona.correo_1)"
                          title="Enviar Correo"
                          class="p-2 rounded-full text-gray-600 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700"
                        >
                          <EnvelopeIcon class="h-5 w-5" />
                        </button>
                        <span v-else class="p-2 rounded-full text-gray-400 cursor-not-allowed" title="Sin correo">
                          <EnvelopeIcon class="h-5 w-5" />
                        </span>

                        <!-- Editar -->
                        <Link
                          :href="route('personas.edit', persona.id)"
                          class="p-2 text-blue-600 hover:text-blue-800 rounded-full hover:bg-blue-100 dark:hover:bg-blue-900/50"
                          title="Editar"
                        >
                          <PencilIcon class="h-5 w-5" />
                        </Link>

                        <!-- Eliminar -->
                        <button
                          v-if="can.delete_personas"
                          @click="confirmDeletion(persona)"
                          class="p-2 text-red-600 hover:text-red-800 rounded-full hover:bg-red-100 dark:hover:bg-red-900/50"
                          title="Eliminar"
                        >
                          <TrashIcon class="h-5 w-5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
        </div>
      </div>
    </div>

    <Modal :show="confirmingDeletion" @close="closeModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">¿Estás seguro de que quieres eliminar a esta persona?</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" v-if="itemToDelete">
          Estás a punto de eliminar permanentemente a
          <span class="font-bold">{{ itemToDelete.nombre_completo }}</span>. Si esta persona está asociada a
          algún caso como deudor o codeudor, esa relación se perderá. Esta acción no se puede deshacer.
        </p>
        <div class="mt-6 flex justify-end">
          <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
          <DangerButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="deleteItem">
            Sí, Eliminar Persona
          </DangerButton>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
