<script setup>
/**
 * Personas/Index.vue
 * Lista elegante con acciones rápidas (WhatsApp, Email, Redes, Editar, Eliminar)
 * y dropdown de enlaces sociales.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SocialLinksDropdown from '@/Components/SocialLinksDropdown.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

import {
  TrashIcon,
  PencilIcon,
  ChatBubbleLeftEllipsisIcon,
  EnvelopeIcon,
  LinkIcon,
  UserPlusIcon,
} from '@heroicons/vue/24/outline';

defineProps({
  personas: Array,
  can: Object,
});

const page = usePage();

/* ------------------------- Modal de eliminación ------------------------- */
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

/* ------------------------------ Utilidades ------------------------------ */
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

/** Abre Gmail; si no es posible (bloqueo pop-up), usa mailto */
const sendEmail = (raw) => {
  if (!raw) return;
  const email = String(raw).trim().toLowerCase();
  const w = window.open(gmailCompose(email), '_blank', 'noopener,noreferrer');
  if (!w) window.location.href = mailtoUrl(email);
};
</script>

<template>
  <Head title="Directorio de Personas" />

  <AuthenticatedLayout>
    <!-- HEADER -->
    <template #header>
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          Gestión de Personas
        </h2>

        <Link
          :href="route('personas.create')"
          class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-md shadow transition"
        >
          <UserPlusIcon class="h-5 w-5" />
          Registrar Persona
        </Link>
      </div>
    </template>

    <div class="py-10">
      <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
        <!-- FLASH -->
        <div
          v-if="page.props.flash.success"
          class="rounded-md border-l-4 border-green-500 bg-green-50 p-4 text-green-800 dark:bg-green-900/30 dark:text-green-100"
          role="alert"
        >
          <p class="font-semibold">¡Éxito!</p>
          <p class="text-sm">{{ page.props.flash.success }}</p>
        </div>

        <!-- CARD -->
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="border-b border-gray-100 p-4 dark:border-gray-700 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              Directorio de Personas
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Accede rápidamente a WhatsApp, correo y redes sociales de cada persona o su información.
            </p>
          </div>

          <!-- Tabla -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
              <thead class="bg-gray-50/60 dark:bg-gray-700/40">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                    Nombre completo
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                    Documento
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                    Contacto principal
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                    Acciones
                  </th>
                </tr>
              </thead>

              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <!-- FILA -->
                <tr
                  v-for="persona in personas"
                  :key="persona.id"
                  class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition"
                >
                  <!-- Nombre -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                      <div class="h-9 w-9 shrink-0 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200 grid place-content-center font-semibold">
                        {{ (persona.nombre_completo || '?')[0] }}
                      </div>
                      <div class="min-w-0">
                        <div class="truncate font-medium text-gray-900 dark:text-white">
                          {{ persona.nombre_completo }}
                        </div>
                        <div class="truncate text-xs text-gray-500 dark:text-gray-400">
                          ID #{{ persona.id }}
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Documento -->
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                    {{ persona.tipo_documento }} - {{ persona.numero_documento }}
                  </td>

                  <!-- Contacto principal -->
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex flex-col">
                      <span v-if="persona.celular_1" class="truncate">{{ persona.celular_1 }}</span>
                      <span v-if="persona.correo_1" class="truncate">{{ persona.correo_1 }}</span>
                    </div>
                  </td>

                  <!-- Acciones -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex justify-end items-center gap-1.5 sm:gap-2">
                      <!-- WhatsApp -->
                      <a
                        v-if="persona.celular_1"
                        :href="formatWhatsAppLink(persona.celular_1)"
                        target="_blank"
                        rel="noopener"
                        title="Enviar WhatsApp"
                        class="rounded-full p-2 text-gray-600 hover:bg-emerald-50 hover:text-emerald-700 dark:text-gray-300 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-200"
                      >
                        <ChatBubbleLeftEllipsisIcon class="h-5 w-5" />
                        <span class="sr-only">WhatsApp</span>
                      </a>
                      <span
                        v-else
                        class="rounded-full p-2 text-gray-400"
                        title="Sin celular"
                      >
                        <ChatBubbleLeftEllipsisIcon class="h-5 w-5" />
                      </span>

                      <!-- Email (Gmail con fallback) -->
                      <button
                        v-if="persona.correo_1"
                        type="button"
                        @click="sendEmail(persona.correo_1)"
                        title="Enviar correo"
                        class="rounded-full p-2 text-gray-600 hover:bg-sky-50 hover:text-sky-700 dark:text-gray-300 dark:hover:bg-sky-900/30 dark:hover:text-sky-200"
                      >
                        <EnvelopeIcon class="h-5 w-5" />
                        <span class="sr-only">Correo</span>
                      </button>
                      <span
                        v-else
                        class="rounded-full p-2 text-gray-400"
                        title="Sin correo"
                      >
                        <EnvelopeIcon class="h-5 w-5" />
                      </span>

                      <!-- Redes sociales -->
                      <div class="flex items-center">
                        <div v-if="persona.social_links && persona.social_links.length">
                          <SocialLinksDropdown :links="persona.social_links" />
                        </div>
                        <span
                          v-else
                          class="rounded-full p-2 text-gray-400"
                          title="Sin enlaces sociales"
                        >
                          <LinkIcon class="h-5 w-5" />
                        </span>
                      </div>

                      <!-- Editar -->
                      <Link
                        :href="route('personas.edit', persona.id)"
                        title="Editar"
                        class="rounded-full p-2 text-blue-600 hover:bg-blue-50 hover:text-blue-700 dark:text-blue-300 dark:hover:bg-blue-900/30 dark:hover:text-blue-200"
                      >
                        <PencilIcon class="h-5 w-5" />
                        <span class="sr-only">Editar</span>
                      </Link>

                      <!-- Eliminar -->
                      <button
                        v-if="can.delete_personas"
                        @click="confirmDeletion(persona)"
                        title="Eliminar"
                        class="rounded-full p-2 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-300 dark:hover:bg-red-900/30 dark:hover:text-red-200"
                      >
                        <TrashIcon class="h-5 w-5" />
                        <span class="sr-only">Eliminar</span>
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- VACÍO -->
                <tr v-if="!personas || personas.length === 0">
                  <td colspan="4" class="px-6 py-12">
                    <div class="text-center text-gray-500 dark:text-gray-400">
                      <div class="mx-auto mb-3 grid h-12 w-12 place-content-center rounded-full bg-gray-100 dark:bg-gray-700">
                        <UserPlusIcon class="h-6 w-6" />
                      </div>
                      <p class="font-medium">Aún no hay personas registradas</p>
                      <p class="mt-1 text-sm">
                        Crea tu primer contacto para empezar a usar el directorio.
                      </p>
                      <Link
                        :href="route('personas.create')"
                        class="mt-4 inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-white shadow hover:bg-indigo-700 active:bg-indigo-800 transition"
                      >
                        <UserPlusIcon class="h-5 w-5" />
                        Registrar Persona
                      </Link>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- /CARD -->
      </div>
    </div>

    <!-- MODAL ELIMINAR -->
    <Modal :show="confirmingDeletion" @close="closeModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
          ¿Eliminar esta persona?
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" v-if="itemToDelete">
          Se eliminará permanentemente
          <span class="font-semibold">{{ itemToDelete.nombre_completo }}</span>.
          Si está asociada a algún caso (deudor o codeudor), se perderá esa relación.
          Esta acción no se puede deshacer.
        </p>
        <div class="mt-6 flex justify-end">
          <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
          <DangerButton
            class="ms-3"
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
            @click="deleteItem"
          >
            Sí, eliminar
          </DangerButton>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
