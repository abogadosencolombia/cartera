<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import AsyncSelect from '@/Components/AsyncSelect.vue';

const props = defineProps({
  proceso: { type: Object, required: true },
});

// Helper para inicializar los valores de los selectores asíncronos
const getInitialValue = (relation, idKey, labelKey) => {
  if (!relation) return null;
  return { id: relation[idKey], label: relation[labelKey] };
};

// Helper para formatear fechas al formato YYYY-MM-DD que el input[type=date] requiere.
const formatDateForInput = (dateString) => {
  if (!dateString) return '';
  // Laravel envía la fecha como un string (ej. "2025-09-20 00:00:00").
  // Tomamos solo los primeros 10 caracteres.
  return dateString.substring(0, 10);
};

// Inicialización del formulario con los datos existentes del proceso
const form = useForm({
  _method: 'PATCH',
  abogado_id:              getInitialValue(props.proceso.abogado, 'id', 'name'),
  responsable_revision_id: getInitialValue(props.proceso.responsable_revision, 'id', 'name'),
  juzgado_id:              getInitialValue(props.proceso.juzgado, 'id', 'nombre'),
  tipo_proceso_id:         getInitialValue(props.proceso.tipo_proceso, 'id', 'nombre'),
  demandante_id:           getInitialValue(props.proceso.demandante, 'id', 'nombre_completo'),
  demandado_id:            getInitialValue(props.proceso.demandado, 'id', 'nombre_completo'),
  radicado: props.proceso.radicado ?? '',
  fecha_radicado: formatDateForInput(props.proceso.fecha_radicado),
  fecha_revision: formatDateForInput(props.proceso.fecha_revision),
  fecha_proxima_revision: formatDateForInput(props.proceso.fecha_proxima_revision),
  naturaleza: props.proceso.naturaleza ?? '',
  asunto: props.proceso.asunto ?? '',
  correo_radicacion: props.proceso.correo_radicacion ?? '',
  ultima_actuacion: props.proceso.ultima_actuacion ?? '',
  link_expediente: props.proceso.link_expediente ?? '',
  ubicacion_drive: props.proceso.ubicacion_drive ?? '',
  correos_juzgado: props.proceso.correos_juzgado ?? '',
  observaciones: props.proceso.observaciones ?? '',
});

// Función para manejar el envío del formulario
const submit = () => {
  // Construye el payload asegurándose de enviar solo los IDs de las relaciones
  const payload = {
    _method: 'PATCH',
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
  router.post(route('procesos.update', props.proceso.id), payload, { preserveScroll: true });
};
</script>

<template>
  <Head :title="`Editar radicado ${proceso.radicado ?? ''}`" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Editar Radicado <span class="text-indigo-600 dark:text-indigo-400">{{ proceso.radicado ?? '—' }}</span>
          </h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Actualiza la información y el estado del proceso.</p>
        </div>
        <!-- INICIO DE LA CORRECIÓN -->
        <div class="flex items-center gap-4">
        <!-- FIN DE LA CORRECIÓN -->
          <Link :href="route('procesos.show', proceso.id)">
            <SecondaryButton>Ver Detalle</SecondaryButton>
          </Link>
          <Link :href="route('procesos.index')">
            <SecondaryButton>Volver al Listado</SecondaryButton>
          </Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <form @submit.prevent="submit">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

            <!-- Columna Principal de Información (Izquierda) -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
              
              <!-- Sección: Datos Principales -->
              <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                  <span>Datos Principales</span>
                </h3>
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
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
                   <div>
                    <InputLabel for="abogado" value="Abogado / Gestor" />
                    <AsyncSelect id="abogado" v-model="form.abogado_id" :initial-value="proceso.abogado" option-label="name" :endpoint="route('search.usuarios')" placeholder="Buscar abogado..." />
                    <InputError :message="form.errors.abogado_id" class="mt-2" />
                  </div>
                  <div class="md:col-span-2">
                    <InputLabel for="juzgado" value="Juzgado / Entidad" />
                    <AsyncSelect id="juzgado" v-model="form.juzgado_id" :initial-value="proceso.juzgado" option-label="nombre" :endpoint="route('juzgados.search')" :min-chars="3" placeholder="Buscar juzgado..." />
                    <InputError :message="form.errors.juzgado_id" class="mt-2" />
                  </div>
                  <div>
                    <InputLabel for="tipo_proceso" value="Tipo de Proceso" />
                    <AsyncSelect id="tipo_proceso" v-model="form.tipo_proceso_id" :initial-value="proceso.tipo_proceso" option-label="nombre" :endpoint="route('search.tipos-proceso')" placeholder="Buscar tipo..." />
                    <InputError :message="form.errors.tipo_proceso_id" class="mt-2" />
                  </div>
                  <div class="md:col-span-3">
                    <InputLabel for="asunto" value="Asunto" />
                    <Textarea id="asunto" v-model="form.asunto" rows="3" class="mt-1 block w-full" />
                    <InputError :message="form.errors.asunto" class="mt-2" />
                  </div>
                </div>
              </div>

              <!-- Sección: Partes y Contacto -->
              <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                 <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21v-1a6 6 0 00-5.197-5.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-3.197 1.053m-2.454 4.092A3.986 3.986 0 013 18v-1a6 6 0 0112 0v1z" /></svg>
                    <span>Partes y Contacto</span>
                </h3>
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <InputLabel for="demandante" value="Demandante / Denunciante" />
                    <AsyncSelect id="demandante" v-model="form.demandante_id" :initial-value="proceso.demandante" option-label="nombre_completo" :endpoint="route('search.personas')" placeholder="Buscar persona..." />
                    <InputError :message="form.errors.demandante_id" class="mt-2" />
                  </div>
                  <div>
                    <InputLabel for="demandado" value="Demandado / Denunciado" />
                    <AsyncSelect id="demandado" v-model="form.demandado_id" :initial-value="proceso.demandado" option-label="nombre_completo" :endpoint="route('search.personas')" placeholder="Buscar persona..." />
                    <InputError :message="form.errors.demandado_id" class="mt-2" />
                  </div>
                  <div>
                    <InputLabel for="correo_radicacion" value="Correo de Radicación" />
                    <TextInput id="correo_radicacion" v-model="form.correo_radicacion" type="email" class="mt-1 block w-full" />
                    <InputError :message="form.errors.correo_radicacion" class="mt-2" />
                  </div>
                  <div>
                    <InputLabel for="correos_juzgado" value="Correos del Juzgado (opcional)" />
                    <TextInput id="correos_juzgado" v-model="form.correos_juzgado" class="mt-1 block w-full" placeholder="Separados por coma" />
                    <InputError :message="form.errors.correos_juzgado" class="mt-2" />
                  </div>
                </div>
              </div>

              <!-- Sección: Enlaces y Observaciones -->
              <div class="p-6">
                 <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                    <span>Enlaces y Anotaciones</span>
                </h3>
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                   <div>
                    <InputLabel for="link_expediente" value="Link de expediente digital" />
                    <TextInput id="link_expediente" v-model="form.link_expediente" type="url" class="mt-1 block w-full" placeholder="https://…" />
                    <InputError :message="form.errors.link_expediente" class="mt-2" />
                  </div>
                  <div>
                    <InputLabel for="ubicacion_drive" value="Ubicación en Drive (opcional)" />
                    <TextInput id="ubicacion_drive" v-model="form.ubicacion_drive" type="url" class="mt-1 block w-full" placeholder="https://drive.google.com/…" />
                    <InputError :message="form.errors.ubicacion_drive" class="mt-2" />
                  </div>
                  <div class="md:col-span-2">
                    <InputLabel for="observaciones" value="Observaciones (opcional)" />
                    <Textarea id="observaciones" v-model="form.observaciones" rows="4" class="mt-1 block w-full" />
                    <InputError :message="form.errors.observaciones" class="mt-2" />
                  </div>
                </div>
              </div>

            </div>

            <!-- Columna Lateral (Derecha) -->
            <div class="lg:col-span-1">
              <div class="sticky top-8 space-y-6">
                <!-- Card: Revisión y Seguimiento -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                  <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0h18M12 15.75h.008v.008H12v-.008z" /></svg>
                    <span>Revisión y Seguimiento</span>
                  </h3>
                  <div class="mt-6 space-y-6">
                    <div>
                      <InputLabel for="responsable" value="Responsable de Revisión" />
                      <AsyncSelect id="responsable" v-model="form.responsable_revision_id" :initial-value="proceso.responsable_revision" option-label="name" :endpoint="route('search.usuarios')" placeholder="Buscar responsable..." />
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
                    <div>
                      <InputLabel for="ultima_actuacion" value="Última Actuación" />
                      <Textarea id="ultima_actuacion" v-model="form.ultima_actuacion" rows="5" class="mt-1 block w-full" />
                      <InputError :message="form.errors.ultima_actuacion" class="mt-2" />
                    </div>
                  </div>
                </div>

                <!-- Card: Acciones -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                   <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                     <span>Acciones</span>
                   </h3>
                   <div class="mt-6 space-y-4">
                      <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }" class="w-full justify-center">
                        {{ form.processing ? 'Actualizando…' : 'Actualizar Radicado' }}
                      </PrimaryButton>
                      <Link :href="route('procesos.show', proceso.id)">
                        <SecondaryButton type="button" class="w-full justify-center">Cancelar</SecondaryButton>
                      </Link>
                   </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

