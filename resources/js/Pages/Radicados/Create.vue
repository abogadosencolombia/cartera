<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import AsyncSelect from '@/Components/AsyncSelect.vue';

const form = useForm({
  abogado_id: null,
  responsable_revision_id: null,
  juzgado_id: null,
  tipo_proceso_id: null,
  demandante_id: null,
  demandado_id: null,
  radicado: '',
  fecha_radicado: '',
  naturaleza: '',
  asunto: '',
  correo_radicacion: '',
  fecha_revision: '',
  fecha_proxima_revision: '',
  ultima_actuacion: '',
  link_expediente: '',
  ubicacion_drive: '',
  correos_juzgado: '',
  observaciones: '',
});

const submit = () => {
  // ===== PUNTO DE CONTROL 1: VERIFICAR EL ESTADO DEL FORMULARIO =====
  console.log('Paso 1: Estado del formulario ANTES de construir el payload:', form);
  // ===================================================================

  const payload = {
    radicado: form.radicado,
    fecha_radicado: form.fecha_radicado,
    naturaleza: form.naturaleza,
    asunto: form.asunto,
    correo_radicacion: form.correo_radicacion,
    fecha_revision: form.fecha_revision,
    fecha_proxima_revision: form.fecha_proxima_revision,
    ultima_actuacion: form.ultima_actuacion,
    link_expediente: form.link_expediente,
    ubicacion_drive: form.ubicacion_drive,
    correos_juzgado: form.correos_juzgado,
    observaciones: form.observaciones,
    abogado_id: form.abogado_id?.id ?? null,
    responsable_revision_id: form.responsable_revision_id?.id ?? null,
    juzgado_id: form.juzgado_id?.id ?? null,
    tipo_proceso_id: form.tipo_proceso_id?.id ?? null,
    demandante_id: form.demandante_id?.id ?? null,
    demandado_id: form.demandado_id?.id ?? null,
  };

  // ===== PUNTO DE CONTROL 2: VERIFICAR EL PAYLOAD FINAL =====
  console.log('Paso 2: Payload que se enviará al backend:', payload);
  // ============================================================
  
  router.post(route('procesos.store'), payload, {
    preserveScroll: true,
    onStart: () => { form.processing = true; },
    onFinish: () => { form.processing = false; },
    onError: (errors) => { form.errors = errors; },
  });
};
</script>

<template>
  <!-- El template no necesita cambios, se mantiene igual -->
  <Head title="Registrar Radicado (Modo Diagnóstico)" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          Registrar Radicado (Modo Diagnóstico)
        </h2>
        <Link
          :href="route('procesos.index')"
          class="inline-flex items-center px-3 py-2 rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100"
        >
          ← Volver al listado
        </Link>
      </div>
    </template>

    <div class="py-10">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <form @submit.prevent="submit" class="space-y-8">
          
          <!-- DATOS PRINCIPALES -->
          <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Datos Principales</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              
              <div>
                <InputLabel value="Abogado / Gestor" for="abogado" />
                <AsyncSelect
                  id="abogado"
                  v-model="form.abogado_id"
                  :endpoint="route('search.usuarios')"
                  placeholder="Buscar abogado o gestor…"
                />
                <InputError :message="form.errors.abogado_id" class="mt-2" />
              </div>

              <div>
                <InputLabel for="radicado" value="Radicado" />
                <TextInput id="radicado" v-model="form.radicado" class="mt-1 block w-full" />
                <InputError :message="form.errors.radicado" class="mt-2" />
              </div>

              <div>
                <InputLabel for="fecha_radicado" value="Fecha de Radicado" />
                <TextInput id="fecha_radicado" v-model="form.fecha_radicado" type="date" class="mt-1 block w-full" />
                <InputError :message="form.errors.fecha_radicado" class="mt-2" />
              </div>

              <div class="md:col-span-2">
                <InputLabel for="juzgado" value="Juzgado / Entidad" />
                <AsyncSelect
                  id="juzgado"
                  v-model="form.juzgado_id"
                  :endpoint="route('juzgados.search')"
                  :min-chars="3"
                  placeholder="Escribe al menos 3 letras del juzgado…"
                />
                <InputError :message="form.errors.juzgado_id" class="mt-2" />
              </div>

              <div>
                <InputLabel for="naturaleza" value="Naturaleza (opcional)" />
                <TextInput
                  id="naturaleza"
                  v-model="form.naturaleza"
                  class="mt-1 block w-full"
                  placeholder="Ej. CIVIL MPAL"
                />
                <InputError :message="form.errors.naturaleza" class="mt-2" />
              </div>

              <div>
                <InputLabel for="tipo_proceso" value="Tipo de Proceso" />
                <AsyncSelect
                  id="tipo_proceso"
                  v-model="form.tipo_proceso_id"
                  :endpoint="route('search.tipos-proceso')"
                  placeholder="Buscar tipo de proceso…"
                />
                <InputError :message="form.errors.tipo_proceso_id" class="mt-2" />
              </div>

              <div class="md:col-span-3">
                <InputLabel for="asunto" value="Asunto" />
                <Textarea id="asunto" v-model="form.asunto" rows="2" class="mt-1 block w-full" />
                <InputError :message="form.errors.asunto" class="mt-2" />
              </div>
            </div>
          </div>

          <!-- PARTES Y CONTACTO -->
          <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Partes y Contacto</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              
              <div>
                <InputLabel for="demandante" value="Demandante / Denunciante (Personas)" />
                <AsyncSelect
                  id="demandante"
                  v-model="form.demandante_id"
                  :endpoint="route('search.personas')"
                  placeholder="Buscar persona…"
                />
                <InputError :message="form.errors.demandante_id" class="mt-2" />
              </div>
              
              <div>
                <InputLabel for="demandado" value="Demandado / Denunciado (Personas)" />
                <AsyncSelect
                  id="demandado"
                  v-model="form.demandado_id"
                  :endpoint="route('search.personas')"
                  placeholder="Buscar persona…"
                />
                <InputError :message="form.errors.demandado_id" class="mt-2" />
              </div>

              <div>
                <InputLabel for="correo_radicacion" value="Correo de Radicación" />
                <TextInput id="correo_radicacion" v-model="form.correo_radicacion" type="email" class="mt-1 block w-full" />
                <InputError :message="form.errors.correo_radicacion" class="mt-2" />
              </div>

              <div>
                <InputLabel for="correos_juzgado" value="Correos del Juzgado / Entidad (opcional)" />
                <TextInput
                  id="correos_juzgado"
                  v-model="form.correos_juzgado"
                  class="mt-1 block w-full"
                  placeholder="Separados por coma si hay mas de uno"
                />
                <InputError :message="form.errors.correos_juzgado" class="mt-2" />
              </div>
            </div>
          </div>
          
          <!-- REVISIÓN Y SEGUIMIENTO -->
          <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Revisión y Seguimiento</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              
              <div>
                <InputLabel for="responsable" value="Responsable de Revisión" />
                <AsyncSelect
                  id="responsable"
                  v-model="form.responsable_revision_id"
                  :endpoint="route('search.usuarios')"
                  placeholder="Buscar responsable…"
                />
                <InputError :message="form.errors.responsable_revision_id" class="mt-2" />
              </div>

              <div>
                <InputLabel for="fecha_revision" value="Fecha de Revisión" />
                <TextInput id="fecha_revision" v-model="form.fecha_revision" type="date" class="mt-1 block w-full" />
                <InputError :message="form.errors.fecha_revision" class="mt-2" />
              </div>

              <div>
                <InputLabel for="fecha_proxima_revision" value="Fecha Próxima Revisión" />
                <TextInput id="fecha_proxima_revision" v-model="form.fecha_proxima_revision" type="date" class="mt-1 block w-full" />
                <InputError :message="form.errors.fecha_proxima_revision" class="mt-2" />
              </div>
              
              <div class="md:col-span-3">
                <InputLabel for="ultima_actuacion" value="Última Actuación" />
                <Textarea id="ultima_actuacion" v-model="form.ultima_actuacion" rows="2" class="mt-1 block w-full" />
                <InputError :message="form.errors.ultima_actuacion" class="mt-2" />
              </div>
            </div>
          </div>

          <!-- ENLACES Y OBSERVACIONES -->
          <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Enlaces y Observaciones</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              
              <div>
                <InputLabel for="link_expediente" value="Link de expediente digital (SharePoint, Rama, etc.)" />
                <TextInput
                  id="link_expediente"
                  v-model="form.link_expediente"
                  type="url"
                  class="mt-1 block w-full"
                  placeholder="https://…"
                />
                <InputError :message="form.errors.link_expediente" class="mt-2" />
              </div>

              <div>
                <InputLabel for="ubicacion_drive" value="Ubicación en Drive (opcional)" />
                <TextInput
                  id="ubicacion_drive"
                  v-model="form.ubicacion_drive"
                  type="url"
                  class="mt-1 block w-full"
                  placeholder="https://drive.google.com/…"
                />
                <InputError :message="form.errors.ubicacion_drive" class="mt-2" />
              </div>

              <div class="md:col-span-2">
                <InputLabel for="observaciones" value="Observaciones (opcional)" />
                <Textarea id="observaciones" v-model="form.observaciones" rows="3" class="mt-1 block w-full" />
                <InputError :message="form.errors.observaciones" class="mt-2" />
              </div>
            </div>
          </div>
          
          <!-- ACCIONES -->
          <div class="flex items-center justify-end">
            <SecondaryButton :href="route('procesos.index')" as="a" class="mr-3">Cancelar</SecondaryButton>
            <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">
              {{ form.processing ? 'Guardando…' : 'Guardar radicado' }}
            </PrimaryButton>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

