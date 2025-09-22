<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { debounce } from 'lodash';

const props = defineProps({
  procesos: { type: Object, required: true },
  filtros: { type: Object, default: () => ({}) },
  abogados: { type: Array, default: () => [] },
});

const search = ref(props.filtros.search);

watch(search, debounce((value) => {
  router.get(route('procesos.index'), { search: value }, {
    preserveState: true,
    replace: true,
  });
}, 300));

// --- INICIO DE LA CORRECCIÓN: LÓGICA DE FECHAS ROBUSTA ---
const getRevisionStatus = (dateString) => {
  if (!dateString) {
    return { text: 'Sin fecha', classes: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 ring-1 ring-inset ring-gray-500/20' };
  }

  // 1. Crear un único objeto de fecha a partir del string de Laravel.
  const revisionDate = new Date(dateString);

  // 2. Verificar si la fecha creada es válida.
  if (isNaN(revisionDate.getTime())) {
    return { text: 'Fecha inválida', classes: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 ring-1 ring-inset ring-gray-500/20' };
  }

  // 3. Para el cálculo de días, normalizar ambas fechas a medianoche UTC para una comparación precisa.
  const revisionDay = new Date(Date.UTC(revisionDate.getUTCFullYear(), revisionDate.getUTCMonth(), revisionDate.getUTCDate()));
  const today = new Date();
  const todayDay = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate()));

  const diffTime = revisionDay.getTime() - todayDay.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  // 4. Para mostrar la fecha, usar el objeto de fecha válido y formatearlo en la zona horaria correcta.
  const formattedDate = revisionDate.toLocaleDateString('es-CO', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    timeZone: 'UTC', // Usar UTC para consistencia con el backend
  });

  // Lógica del Semáforo sin cambios
  if (diffDays <= 2) { // Rojo: 2 días, 1 día, hoy o vencido
    const label = diffDays < 0 ? 'Vencido' : 'Urgente';
    return { text: `${label} (${formattedDate})`, classes: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200 ring-1 ring-inset ring-red-600/20' };
  }
  if (diffDays <= 4) { // Amarillo: 3 o 4 días
    return { text: `Próximo (${formattedDate})`, classes: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200 ring-1 ring-inset ring-yellow-600/20' };
  }
  // Verde: 5 o más días
  return { text: formattedDate, classes: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200 ring-1 ring-inset ring-green-600/20' };
};
// --- FIN DE LA CORRECCIÓN ---

</script>

<template>
  <Head title="Gestión de Radicados" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-col md:flex-row items-center justify-between gap-4">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          Gestión de Radicados
        </h2>
        <div class="flex items-center gap-2">
          <Link :href="route('procesos.import')">
            <SecondaryButton>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
              Importar
            </SecondaryButton>
          </Link>
          <Link :href="route('procesos.create')">
            <PrimaryButton>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
              Registrar Radicado
            </PrimaryButton>
          </Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Panel de Control y Búsqueda -->
        <div class="mb-6 p-4 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
           <div class="relative">
             <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
             </div>
             <TextInput
                v-model="search"
                type="text"
                class="block w-full md:w-1/2 pl-10"
                placeholder="Buscar por radicado, asunto, demandante o demandado..."
              />
           </div>
        </div>

        <!-- Listado de Radicados -->
        <div v-if="procesos.data.length > 0" class="space-y-4">
          <div v-for="proceso in procesos.data" :key="proceso.id"
               class="bg-white dark:bg-gray-800 overflow-hidden shadow-md sm:rounded-lg p-5 transition duration-300 ease-in-out hover:shadow-xl dark:hover:ring-1 dark:hover:ring-gray-700">
            
            <div class="flex flex-col md:flex-row justify-between md:items-start gap-4">
              <!-- Información Principal -->
              <div class="flex-grow">
                <Link :href="route('procesos.show', proceso.id)" class="group">
                  <h3 class="text-lg font-bold text-indigo-600 dark:text-indigo-400 group-hover:underline">
                    {{ proceso.radicado }}
                  </h3>
                </Link>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 truncate" :title="proceso.asunto">
                  {{ proceso.asunto || 'Sin asunto' }}
                </p>

                <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3 text-sm">
                  <!-- Partes -->
                  <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21v-1a6 6 0 00-5.197-5.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-3.197 1.053m-2.454 4.092A3.986 3.986 0 013 18v-1a6 6 0 0112 0v1z" /></svg>
                    <span class="font-medium">{{ proceso.demandante?.nombre_completo || 'N/A' }}</span>
                    <span class="text-gray-400">vs.</span>
                    <span class="font-medium">{{ proceso.demandado?.nombre_completo || 'N/A' }}</span>
                  </div>
                  <!-- Juzgado -->
                  <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd" /></svg>
                    <span>{{ proceso.juzgado?.nombre || 'Juzgado no asignado' }}</span>
                  </div>
                   <!-- Tipo Proceso -->
                   <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0A2.25 2.25 0 015.625 7.5h12.75c1.135 0 2.099.774 2.231 1.879M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                      <span>{{ proceso.tipo_proceso?.nombre || 'Tipo no definido' }}</span>
                   </div>
                </div>
              </div>

              <!-- Estado y Acciones -->
              <div class="shrink-0 flex flex-col items-start md:items-end justify-between gap-4 min-w-[200px]">
                <div class="flex flex-col items-start md:items-end w-full">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Próxima Revisión</span>
                    <span
                      class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold"
                      :class="getRevisionStatus(proceso.fecha_proxima_revision).classes"
                    >
                      {{ getRevisionStatus(proceso.fecha_proxima_revision).text }}
                    </span>
                </div>
                
                <div class="flex items-center gap-2 self-stretch md:self-end">
                  <Link :href="route('procesos.edit', proceso.id)" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                    Editar
                  </Link>
                  <Link :href="route('procesos.show', proceso.id)">
                    <PrimaryButton small>Ver Detalles</PrimaryButton>
                  </Link>
                </div>
              </div>
            </div>
            
          </div>
        </div>

        <!-- Estado Vacío -->
        <div v-else class="text-center bg-white dark:bg-gray-800 rounded-lg shadow-sm p-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No hay radicados</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comienza registrando un nuevo radicado para gestionarlo.</p>
            <div class="mt-6">
               <Link :href="route('procesos.create')">
                 <PrimaryButton>
                   + Registrar Nuevo Radicado
                 </PrimaryButton>
               </Link>
            </div>
        </div>

        <!-- Paginación -->
        <Pagination v-if="procesos.links.length > 3" class="mt-6" :links="procesos.links" />

      </div>
    </div>
  </AuthenticatedLayout>
</template>

