<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import vSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';
import axios from 'axios';

const props = defineProps({
    cooperativas: { type: Array, default: () => [] },
    abogadosYGestores: { type: Array, default: () => [] },
    personas: { type: Array, default: () => [] },
    casoAClonar: { type: Object, default: null },
    subtipos_proceso: { type: Array, default: () => [] },
    etapas_procesales: { type: Array, default: () => [] },
});

const form = useForm({
    cooperativa_id: props.casoAClonar?.cooperativa_id || null,
    user_id: props.casoAClonar?.user_id || null,
    deudor_id: props.casoAClonar?.deudor_id || null,
    codeudor1_id: props.casoAClonar?.codeudor1_id || null,
    codeudor2_id: props.casoAClonar?.codeudor2_id || null,
    referencia_credito: props.casoAClonar?.referencia_credito || '',
    tipo_proceso: props.casoAClonar?.tipo_proceso || 'ejecutivo singular',
    estado_proceso: 'prejurídico',
    tipo_garantia_asociada: props.casoAClonar?.tipo_garantia_asociada || 'codeudor',
    fecha_apertura: new Date().toISOString().slice(0, 10),
    fecha_vencimiento: props.casoAClonar?.fecha_vencimiento || '',
    monto_total: props.casoAClonar?.monto_total || 0,
    tasa_interes_corriente: props.casoAClonar?.tasa_interes_corriente || 0,
    tasa_moratoria: props.casoAClonar?.tasa_moratoria || 0,
    origen_documental: props.casoAClonar?.origen_documental || 'pagaré',
    clonado_de_id: props.casoAClonar?.id || null,
    subtipo_proceso: props.casoAClonar?.subtipo_proceso || '',
    etapa_procesal: props.casoAClonar?.etapa_procesal || '',
    juzgado_id: props.casoAClonar?.juzgado_id || null,
});

const juzgadosOptions = ref(props.casoAClonar?.juzgado ? [props.casoAClonar.juzgado] : []);
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
    form.post(route('casos.store'));
};
</script>

<template>
    <Head :title="casoAClonar ? 'Clonando Caso' : 'Registrar Nuevo Caso'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ casoAClonar ? 'Clonando Caso #' + casoAClonar.id : 'Registrar Nuevo Caso' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 md:p-8 text-gray-900 dark:text-gray-100">
                        <form @submit.prevent="submit" class="space-y-8">
                             <section>
                                <h3 class="text-lg font-medium border-b pb-2 mb-6">Partes Involucradas</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel for="cooperativa_id" value="Cooperativa *" />
                                        <select v-model="form.cooperativa_id" id="cooperativa_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                            <option :value="null">-- Seleccione --</option>
                                            <option v-for="coop in cooperativas" :key="coop.id" :value="coop.id">{{ coop.nombre }}</option>
                                        </select>
                                        <InputError :message="form.errors.cooperativa_id" class="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel for="user_id" value="Abogado/Gestor a Cargo *" />
                                        <select v-model="form.user_id" id="user_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                             <option :value="null">-- Seleccione --</option>
                                            <option v-for="user in abogadosYGestores" :key="user.id" :value="user.id">{{ user.name }}</option>
                                        </select>
                                        <InputError :message="form.errors.user_id" class="mt-2" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <InputLabel for="deudor_id" value="Deudor Principal *" />
                                        <select v-model="form.deudor_id" id="deudor_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                            <option :value="null">-- Seleccione --</option>
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
                                        <InputLabel for="tasa_interes_corriente" value="Tasa Interés Corriente (%) *" />
                                        <TextInput v-model="form.tasa_interes_corriente" id="tasa_interes_corriente" type="number" step="0.01" class="mt-1 block w-full" />
                                        <InputError :message="form.errors.tasa_interes_corriente" class="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel for="tasa_moratoria" value="Tasa Moratoria (%) *" />
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
                                        <InputLabel for="juzgado_id" value="Juzgado" />
                                        <v-select
                                            id="juzgado_id"
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

                                    <div>
                                        <InputLabel for="tipo_garantia_asociada" value="Tipo de Garantía *" />
                                        <select v-model="form.tipo_garantia_asociada" id="tipo_garantia_asociada" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                            <option>codeudor</option>
                                            <option>hipotecaria</option>
                                            <option>prendaria</option>
                                            <option>sin garantía</option>
                                        </select>
                                        <InputError :message="form.errors.tipo_garantia_asociada" class="mt-2" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <InputLabel for="origen_documental" value="Origen Documental *" />
                                        <select v-model="form.origen_documental" id="origen_documental" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                            <option>pagaré</option>
                                            <option>libranza</option>
                                            <option>contrato</option>
                                            <option>otro</option>
                                        </select>
                                        <InputError :message="form.errors.origen_documental" class="mt-2" />
                                    </div>
                                </div>
                            </section>

                            <div class="flex items-center justify-end mt-8 border-t pt-6">
                                <Link :href="route('casos.index')" class="text-sm text-gray-600 hover:underline mr-4">Cancelar</Link>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    {{ casoAClonar ? 'Crear Copia del Caso' : 'Guardar Nuevo Caso' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<!-- ESTILOS PARA EL NUEVO SELECTOR -->
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
