<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';


// --- ¡IMPORTAMOS EL NUEVO COMPONENTE! ---
import vSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';
import axios from 'axios';

const props = defineProps({
    caso: { type: Object, required: true },
    cooperativas: { type: Array, default: () => [] },
    abogadosYGestores: { type: Array, default: () => [] },
    personas: { type: Array, default: () => [] },
    subtipos_proceso: { type: Array, default: () => [] },
    etapas_procesales: { type: Array, default: () => [] },
});

const user = usePage().props.auth.user;

const isFormDisabled = computed(() => {
    return props.caso.bloqueado && user.tipo_usuario !== 'admin';
});

const form = useForm({
    _method: 'PATCH',
    cooperativa_id: props.caso.cooperativa_id,
    user_id: props.caso.user_id,
    deudor_id: props.caso.deudor_id,
    codeudor1_id: props.caso.codeudor1_id,
    codeudor2_id: props.caso.codeudor2_id,
    referencia_credito: props.caso.referencia_credito,
    tipo_proceso: props.caso.tipo_proceso,
    estado_proceso: props.caso.estado_proceso,
    tipo_garantia_asociada: props.caso.tipo_garantia_asociada,
    fecha_apertura: props.caso.fecha_apertura ? new Date(props.caso.fecha_apertura).toISOString().slice(0, 10) : '',
    fecha_vencimiento: props.caso.fecha_vencimiento ? new Date(props.caso.fecha_vencimiento).toISOString().slice(0, 10) : '',
    monto_total: props.caso.monto_total,
    tasa_interes_corriente: props.caso.tasa_interes_corriente,
    tasa_moratoria: props.caso.tasa_moratoria,
    origen_documental: props.caso.origen_documental,
    etapa_actual: props.caso.etapa_actual,
    bloqueado: !!props.caso.bloqueado,
    motivo_bloqueo: props.caso.motivo_bloqueo ?? '',
    medio_contacto: props.caso.medio_contacto,
    notas_legales: props.caso.notas_legales,
    subtipo_proceso: props.caso.subtipo_proceso || '',
    etapa_procesal: props.caso.etapa_procesal || '',
    juzgado_id: props.caso.juzgado_id || null,
});

// Si se desbloquea, limpiar motivo
watch(() => form.bloqueado, v => { if (!v) form.motivo_bloqueo = ''; });

const juzgadosOptions = ref(props.caso.juzgado ? [props.caso.juzgado] : []);
let searchTimeout = null;

const searchJuzgados = (search, loading) => {
    clearTimeout(searchTimeout);
    if (search.length >= 3) {
        loading(true);
        searchTimeout = setTimeout(() => {
            axios.get(route('juzgados.search', { q: search }))
                .then(response => {
                    juzgadosOptions.value = response.data;
                })
                .catch(error => console.error("Error buscando juzgados:", error))
                .finally(() => loading(false));
        }, 500);
    }
};

const submit = () => {
    form.put(route('casos.update', props.caso.id));
};
</script>

<template>
    <Head :title="'Editar Caso #' + caso.id" />

    <AuthenticatedLayout>
         <template #header>
           <div class="flex items-center gap-3">
             <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
               Editando Caso <span class="text-indigo-500">#{{ caso.id }}</span>
             </h2>
             <span v-if="caso.bloqueado" class="px-2 py-0.5 text-xs rounded bg-red-100 text-red-700">Bloqueado</span>
           </div>
         </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 md:p-8 text-gray-900 dark:text-gray-100">
                        <form @submit.prevent="submit" class="space-y-8">
                            <fieldset :disabled="isFormDisabled" class="space-y-8">
                                <section>
                                    <h3 class="text-lg font-medium border-b pb-2 mb-6">Partes Involucradas</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <InputLabel for="cooperativa_id" value="Cooperativa *" />
                                            <select v-model="form.cooperativa_id" id="cooperativa_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                                <option v-for="coop in cooperativas" :key="coop.id" :value="coop.id">{{ coop.nombre }}</option>
                                            </select>
                                            <InputError :message="form.errors.cooperativa_id" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="user_id" value="Abogado/Gestor a Cargo *" />
                                            <select v-model="form.user_id" id="user_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                                <option v-for="user in abogadosYGestores" :key="user.id" :value="user.id">{{ user.name }}</option>
                                            </select>
                                            <InputError :message="form.errors.user_id" class="mt-2" />
                                        </div>
                                        <div class="md:col-span-2">
                                            <InputLabel for="deudor_id" value="Deudor Principal *" />
                                            <select v-model="form.deudor_id" id="deudor_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                                <option v-for="p in personas" :key="p.id" :value="p.id">{{ p.nombre_completo }} ({{ p.numero_documento }})</option>
                                            </select>
                                            <InputError :message="form.errors.deudor_id" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="codeudor1_id" value="Codeudor 1 (Opcional)" />
                                            <select v-model="form.codeudor1_id" id="codeudor1_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                                <option :value="null">Ninguno</option>
                                                <option v-for="p in personas" :key="p.id" :value="p.id">{{ p.nombre_completo }} ({{ p.numero_documento }})</option>
                                            </select>
                                            <InputError :message="form.errors.codeudor1_id" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="codeudor2_id" value="Codeudor 2 (Opcional)" />
                                            <select v-model="form.codeudor2_id" id="codeudor2_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                                <option :value="null">Ninguno</option>
                                                <option v-for="p in personas" :key="p.id" :value="p.id">{{ p.nombre_completo }} ({{ p.numero_documento }})</option>
                                            </select>
                                            <InputError :message="form.errors.codeudor2_id" class="mt-2" />
                                        </div>
                                    </div>
                                </section>

                                <section>
                                    <h3 class="text-lg font-medium border-b pb-2 mb-6">Información del Crédito y Proceso</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                         <div>
                                            <InputLabel for="referencia_credito" value="Referencia del Crédito" />
                                            <TextInput v-model="form.referencia_credito" id="referencia_credito" type="text" class="mt-1 block w-full" />
                                            <InputError :message="form.errors.referencia_credito" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="monto_total" value="Monto Total *" />
                                            <TextInput v-model="form.monto_total" id="monto_total" type="number" step="0.01" class="mt-1 block w-full" />
                                            <InputError :message="form.errors.monto_total" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="tasa_interes_corriente" value="Tasa Interés Corriente (%)" />
                                            <TextInput v-model="form.tasa_interes_corriente" id="tasa_interes_corriente" type="number" step="0.01" class="mt-1 block w-full" />
                                            <InputError :message="form.errors.tasa_interes_corriente" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="tasa_moratoria" value="Tasa Moratoria (%)" />
                                            <TextInput v-model="form.tasa_moratoria" id="tasa_moratoria" type="number" step="0.01" class="mt-1 block w-full" />
                                            <InputError :message="form.errors.tasa_moratoria" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="fecha_apertura" value="Fecha de Apertura *" />
                                            <TextInput v-model="form.fecha_apertura" id="fecha_apertura" type="date" class="mt-1 block w-full" />
                                            <InputError :message="form.errors.fecha_apertura" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="fecha_vencimiento" value="Fecha de Vencimiento" />
                                            <TextInput v-model="form.fecha_vencimiento" id="fecha_vencimiento" type="date" class="mt-1 block w-full" />
                                            <InputError :message="form.errors.fecha_vencimiento" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="tipo_proceso" value="Tipo de Proceso *" />
                                            <select v-model="form.tipo_proceso" id="tipo_proceso" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                                <option>ejecutivo singular</option><option>hipotecario</option><option>prendario</option><option>libranza</option>
                                            </select>
                                            <InputError :message="form.errors.tipo_proceso" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="subtipo_proceso" value="Subtipo de Proceso" />
                                            <select v-model="form.subtipo_proceso" id="subtipo_proceso" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                                <option value="">-- Sin especificar --</option>
                                                <option v-for="subtipo in subtipos_proceso" :key="subtipo" :value="subtipo">{{ subtipo }}</option>
                                            </select>
                                            <InputError class="mt-2" :message="form.errors.subtipo_proceso" />
                                        </div>
                                        <div>
                                            <InputLabel for="etapa_procesal" value="Etapa Procesal" />
                                            <select v-model="form.etapa_procesal" id="etapa_procesal" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                                <option value="">-- Sin especificar --</option>
                                                <option v-for="etapa in etapas_procesales" :key="etapa" :value="etapa">{{ etapa }}</option>
                                            </select>
                                            <InputError class="mt-2" :message="form.errors.etapa_procesal" />
                                        </div>
                                        
                                        <div class="lg:col-span-3">
                                            <InputLabel for="juzgado_id_edit" value="Juzgado" />
                                            <v-select
                                                id="juzgado_id_edit"
                                                v-model="form.juzgado_id"
                                                :options="juzgadosOptions"
                                                label="nombre"
                                                :reduce="juzgado => juzgado.id"
                                                placeholder="Escribe para buscar un juzgado..."
                                                @search="searchJuzgados"
                                                :filterable="false"
                                                :appendToBody="true"
                                                class="mt-1 block w-full vue-select-style"
                                            >
                                                <template #no-options>
                                                    Escribe al menos 3 caracteres para buscar...
                                                </template>
                                            </v-select>
                                            <InputError class="mt-2" :message="form.errors.juzgado_id" />
                                        </div>
                                    </div>
                                </section>
                            </fieldset>

                            <section>
                                <h3 class="text-lg font-medium border-b pb-2 mb-6">Estado y Control del Caso</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                    <InputLabel for="estado_proceso" value="Estado del Proceso *" />
                                    <select v-model="form.estado_proceso" id="estado_proceso"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm"
                                            :disabled="isFormDisabled">
                                        <option value="prejurídico">Prejurídico</option>
                                        <option value="demandado">Demandado</option>
                                        <option value="en ejecución">En Ejecución</option>
                                        <option value="sentencia">Sentencia</option>
                                        <option value="cerrado">Cerrado</option>
                                    </select>
                                    <InputError :message="form.errors.estado_proceso" class="mt-2" />
                                    </div>

                                    <div>
                                    <InputLabel for="etapa_actual" value="Etapa Actual del Proceso" />
                                    <TextInput v-model="form.etapa_actual" id="etapa_actual" type="text"
                                                class="mt-1 block w-full" :disabled="isFormDisabled" />
                                    <InputError :message="form.errors.etapa_actual" class="mt-2" />
                                    </div>

                                    <div class="md:col-span-2">
                                    <InputLabel for="notas_legales" value="Notas Legales / Internas" />
                                    <Textarea v-model="form.notas_legales" id="notas_legales"
                                                class="mt-1 block w-full" rows="4" :disabled="isFormDisabled" />
                                    <InputError :message="form.errors.notas_legales" class="mt-2" />
                                    </div>

                                    <!-- Bloqueo (card) -->
                                    <div class="md:col-span-2 rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/40">
                                    <div class="flex items-start gap-3">
                                        <Checkbox id="bloqueado" v-model:checked="form.bloqueado"
                                                :disabled="user.tipo_usuario !== 'admin'" />
                                        <div class="grow">
                                        <InputLabel for="bloqueado" value="Caso bloqueado" />
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Cuando está bloqueado solo un administrador puede modificar el caso.
                                        </p>
                                        <InputError :message="form.errors.bloqueado" class="mt-2" />
                                        </div>
                                    </div>

                                    <div v-if="form.bloqueado" class="mt-4">
                                        <InputLabel for="motivo_bloqueo" value="Motivo del bloqueo *" />
                                        <Textarea
                                            id="motivo_bloqueo"
                                            v-model.trim="form.motivo_bloqueo"
                                            class="mt-1 block w-full resize-y min-h-[96px]"
                                            rows="3"
                                            maxlength="1000"
                                            placeholder="Describe la razón del bloqueo…"
                                            :disabled="user.tipo_usuario !== 'admin'"/>
                                        <InputError :message="form.errors.motivo_bloqueo" class="mt-2" />
                                    </div>
                                    </div>
                                </div>
                            </section>

                            <div class="flex items-center justify-end mt-8 border-t pt-6">
                                <Link :href="route('casos.show', caso.id)" class="text-sm text-gray-600 hover:underline mr-4">Cancelar</Link>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing || isFormDisabled">
                                    Actualizar Caso
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<!-- ESTILOS PARA EL NUEVO SELECTOR (igual que en Create.vue) -->
<style>
.vue-select-style .vs__dropdown-toggle {
    border-color: #d1d5db;
    background-color: white;
    border-radius: 0.375rem;
}
.dark .vue-select-style .vs__dropdown-toggle {
    border-color: #4b5563;
    background-color: #1f2937;
}
.vue-select-style .vs__search, .vue-select-style .vs__selected {
    color: #111827;
}
.dark .vue-select-style .vs__search, .dark .vue-select-style .vs__selected {
    color: #d1d5db;
}
.vue-select-style .vs__dropdown-menu {
    background-color: white;
    border-color: #d1d5db;
}
.dark .vue-select-style .vs__dropdown-menu {
    background-color: #1f2937;
    border-color: #4b5563;
}
.vue-select-style .vs__option {
    color: #374151;
}
.dark .vue-select-style .vs__option {
    color: #9ca3af;
}
.vue-select-style .vs__option--highlight {
    background-color: #6366f1;
    color: white;
}
</style>
