<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// --- TÁCTICA DEFENSIVA: AÑADIMOS VALORES POR DEFECTO ---
// Si alguna de estas listas no llega a tiempo, el componente
// las tratará como una lista vacía en lugar de colapsar.
const props = defineProps({
    caso: { type: Object, required: true },
    cooperativas: { type: Array, default: () => [] },
    abogadosYGestores: { type: Array, default: () => [] },
    personas: { type: Array, default: () => [] },
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
    bloqueado: props.caso.bloqueado,
    motivo_bloqueo: props.caso.motivo_bloqueo,
    medio_contacto: props.caso.medio_contacto,
    notas_legales: props.caso.notas_legales,
});

const submit = () => {
    form.put(route('casos.update', props.caso.id));
};
</script>

<template>
    <Head :title="'Editar Caso #' + caso.id" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Editando Caso <span class="text-indigo-500">#{{ caso.id }}</span>
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div v-if="$page.props.flash.success" class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md shadow-md" role="alert">
                    <p class="font-bold">¡Éxito!</p>
                    <p>{{ $page.props.flash.success }}</p>
                </div>
                <div v-if="$page.props.flash.error" class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md shadow-md" role="alert">
                    <p class="font-bold">¡Acción Bloqueada!</p>
                    <p>{{ $page.props.flash.error }}</p>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 md:p-8 text-gray-900 dark:text-gray-100">
                        <form @submit.prevent="submit" class="space-y-8">
                            
                            <fieldset :disabled="isFormDisabled" class="space-y-8">
                                <section>
                                    <h3 class="text-lg font-medium border-b pb-2 mb-6">Partes Involucradas</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <InputLabel for="cooperativa_id" value="Cooperativa" />
                                            <select v-model="form.cooperativa_id" id="cooperativa_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                                <option v-for="coop in cooperativas" :key="coop.id" :value="coop.id">{{ coop.nombre }}</option>
                                            </select>
                                            <InputError :message="form.errors.cooperativa_id" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="user_id" value="Abogado/Gestor a Cargo" />
                                            <select v-model="form.user_id" id="user_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                                <option v-for="user in abogadosYGestores" :key="user.id" :value="user.id">{{ user.name }}</option>
                                            </select>
                                            <InputError :message="form.errors.user_id" class="mt-2" />
                                        </div>
                                        <div class="md:col-span-2">
                                            <InputLabel for="deudor_id" value="Deudor Principal" />
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
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <InputLabel for="referencia_credito" value="Referencia del Crédito" />
                                            <TextInput v-model="form.referencia_credito" id="referencia_credito" type="text" class="mt-1 block w-full" />
                                            <InputError :message="form.errors.referencia_credito" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="monto_total" value="Monto Total" />
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
                                            <InputLabel for="fecha_apertura" value="Fecha de Apertura" />
                                            <TextInput v-model="form.fecha_apertura" id="fecha_apertura" type="date" class="mt-1 block w-full" />
                                            <InputError :message="form.errors.fecha_apertura" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="fecha_vencimiento" value="Fecha de Vencimiento (Opcional)" />
                                            <TextInput v-model="form.fecha_vencimiento" id="fecha_vencimiento" type="date" class="mt-1 block w-full" />
                                            <InputError :message="form.errors.fecha_vencimiento" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="tipo_proceso" value="Tipo de Proceso" />
                                            <select v-model="form.tipo_proceso" id="tipo_proceso" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                                <option>ejecutivo singular</option>
                                                <option>hipotecario</option>
                                                <option>prendario</option>
                                                <option>libranza</option>
                                            </select>
                                            <InputError :message="form.errors.tipo_proceso" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="estado_proceso" value="Estado del Proceso" />
                                            <select v-model="form.estado_proceso" id="estado_proceso" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                                <option value="prejurídico">Prejurídico</option>
                                                <option value="demandado">Demandado</option>
                                                <option value="en ejecución">En Ejecución</option>
                                                <option value="sentencia">Sentencia</option>
                                                <option value="cerrado">Cerrado</option>
                                            </select>
                                            <InputError :message="form.errors.estado_proceso" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="tipo_garantia_asociada" value="Tipo de Garantía" />
                                            <select v-model="form.tipo_garantia_asociada" id="tipo_garantia_asociada" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                                <option>codeudor</option>
                                                <option>hipotecaria</option>
                                                <option>prendaria</option>
                                                <option>sin garantía</option>
                                            </select>
                                            <InputError :message="form.errors.tipo_garantia_asociada" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="origen_documental" value="Origen Documental" />
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
                            </fieldset>

                             <section>
                                <h3 class="text-lg font-medium border-b pb-2 mb-6">Estado y Control del Caso</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel for="etapa_actual" value="Etapa Actual del Proceso" />
                                        <TextInput v-model="form.etapa_actual" id="etapa_actual" type="text" class="mt-1 block w-full" :disabled="isFormDisabled" />
                                        <InputError :message="form.errors.etapa_actual" class="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel for="medio_contacto" value="Medio de Contacto Preferido" />
                                        <TextInput v-model="form.medio_contacto" id="medio_contacto" type="text" class="mt-1 block w-full" :disabled="isFormDisabled" />
                                        <InputError :message="form.errors.medio_contacto" class="mt-2" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <InputLabel for="notas_legales" value="Notas Legales / Internas" />
                                        <Textarea v-model="form.notas_legales" id="notas_legales" class="mt-1 block w-full" rows="4" :disabled="isFormDisabled"></Textarea>
                                        <InputError :message="form.errors.notas_legales" class="mt-2" />
                                    </div>
                                </div>

                                <div v-if="user.tipo_usuario === 'admin'" class="mt-6 border-t pt-6 border-red-200 dark:border-red-900">
                                     <label class="flex items-center">
                                         <Checkbox v-model:checked="form.bloqueado" name="bloqueado" />
                                         <span class="ms-3 text-sm font-medium text-red-600 dark:text-red-400">BLOQUEAR EDICIÓN DE ESTE CASO</span>
                                     </label>
                                     <p class="text-xs text-gray-500 mt-1 ml-7">Si se marca, solo un administrador podrá modificar este caso.</p>
                                     <InputError :message="form.errors.bloqueado" class="mt-2" />

                                     <div v-if="form.bloqueado" class="mt-4">
                                         <InputLabel for="motivo_bloqueo" value="Motivo del Bloqueo (Requerido)" />
                                         <Textarea v-model="form.motivo_bloqueo" id="motivo_bloqueo" class="mt-1 block w-full" rows="3"></Textarea>
                                         <InputError :message="form.errors.motivo_bloqueo" class="mt-2" />
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
