<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Textarea from '@/Components/Textarea.vue';
import { ClockIcon, LockClosedIcon, DocumentDuplicateIcon, ArrowDownTrayIcon, BanknotesIcon, BellAlertIcon } from '@heroicons/vue/24/outline';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import CumplimientoLegal from '@/Components/CumplimientoLegal.vue';
import HistorialAuditoria from '@/Components/HistorialAuditoria.vue';

// Props definidos una sola vez y correctamente
const props = defineProps({
    caso: { type: Object, required: true },
    can: { type: Object, required: true },
    plantillas: { type: Array, default: () => [] },
});

const page = usePage();

// --- Lógica de Modales ---
const confirmingDocumentUpload = ref(false);
const docForm = useForm({ tipo_documento: 'pagaré', fecha_carga: new Date().toISOString().slice(0, 10), archivo: null });
const openUploadModal = () => { confirmingDocumentUpload.value = true; };
const closeUploadModal = () => { confirmingDocumentUpload.value = false; docForm.reset(); };
const submitDocument = () => { docForm.post(route('casos.documentos.store', props.caso.id), { preserveScroll: true, onSuccess: () => closeUploadModal() }); };

const confirmingDocumentDeletion = ref(false);
const documentToDelete = ref(null);
const confirmDocumentDeletion = (documento) => { documentToDelete.value = documento; confirmingDocumentDeletion.value = true; };
const closeDeleteModal = () => { confirmingDocumentDeletion.value = false; documentToDelete.value = null; };
const deleteDocument = () => { useForm({}).delete(route('documentos-caso.destroy', documentToDelete.value.id), { preserveScroll: true, onSuccess: () => closeDeleteModal() }); };

const mostrandoModalGenerar = ref(false);
const generarDocForm = useForm({ plantilla_id: null, caso_id: props.caso.id, es_confidencial: false, observaciones: '' });
const abrirModalGenerar = () => { generarDocForm.reset(); generarDocForm.caso_id = props.caso.id; mostrandoModalGenerar.value = true; };
const cerrarModalGenerar = () => { mostrandoModalGenerar.value = false; };
const submitGenerarDocumento = () => { generarDocForm.post(route('documentos.generar'), { preserveScroll: true, onSuccess: () => cerrarModalGenerar() }); };

const mostrandoModalPago = ref(false);
const pagoForm = useForm({
    monto_pagado: '',
    fecha_pago: new Date().toISOString().slice(0, 10),
    motivo_pago: 'parcial',
});
const abrirModalPago = () => { pagoForm.reset({ fecha_pago: new Date().toISOString().slice(0, 10), motivo_pago: 'parcial' }); mostrandoModalPago.value = true; };
const cerrarModalPago = () => { mostrandoModalPago.value = false; };
const submitPago = () => {
    pagoForm.post(route('casos.pagos.store', props.caso.id), {
        preserveScroll: true,
        onSuccess: () => cerrarModalPago(),
    });
};

const mostrandoModalAlerta = ref(false);
const alertaForm = useForm({
    mensaje: '',
    programado_para: '',
});
const abrirModalAlerta = () => { alertaForm.reset(); mostrandoModalAlerta.value = true; };
const cerrarModalAlerta = () => { mostrandoModalAlerta.value = false; };
const submitAlerta = () => {
    alertaForm.post(route('casos.notificaciones.store', props.caso.id), {
        preserveScroll: true,
        onSuccess: () => cerrarModalAlerta(),
    });
};

// --- Funciones de formato ---
const formatDate = (dateString) => {
    if (!dateString) return 'No especificada';
    const date = new Date(dateString);
    const userTimezoneOffset = date.getTimezoneOffset() * 60000;
    return new Date(date.getTime() + userTimezoneOffset).toLocaleDateString('es-CO', { year: 'numeric', month: 'long', day: 'numeric' });
};
const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleString('es-CO', options);
};
const formatCurrency = (value) => {
    if (value === null || value === undefined) return '$ 0';
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(value);
};

const statusColorClasses = {
    red: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    green: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    gray: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
};
</script>

<template>
    <Head :title="'Ficha del Caso #' + caso.id" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Ficha del Caso <span class="text-indigo-500">#{{ caso.id }}</span>
                </h2>
                <div class="flex items-center space-x-4">
                    <Link v-if="can.update" :href="route('casos.clonar', caso.id)" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">Clonar</Link>
                    <Link v-if="can.update" :href="route('casos.edit', caso.id)" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">Editar Caso</Link>
                    <Link :href="route('casos.index')" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">&larr; Volver al listado</Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                
                <div class="lg:col-span-2 space-y-8">
                    
                    <div v-if="page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md shadow-md" role="alert">
                        <p class="font-bold">¡Éxito!</p>
                        <p>{{ page.props.flash.success }}</p>
                    </div>
                     <div v-if="page.props.flash.error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md shadow-md" role="alert">
                        <p class="font-bold">¡Error!</p>
                        <p>{{ page.props.flash.error }}</p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                         <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                             <div class="flex justify-between items-center">
                                 <h3 class="text-xl font-bold text-gray-900 dark:text-white">Deudor: {{ caso.deudor ? caso.deudor.nombre_completo : 'No Asignado' }}</h3>
                                 <span v-if="caso.semaforo" class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full" :class="statusColorClasses[caso.semaforo.color] || statusColorClasses['gray']">{{ caso.semaforo.text }}</span>
                             </div>
                             <p class="text-sm text-gray-500 dark:text-gray-400">Cooperativa: {{ caso.cooperativa ? caso.cooperativa.nombre : 'N/A' }}</p>
                         </div>
                         <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                             <div><p class="text-sm text-gray-500">Monto Total</p><p class="font-semibold text-lg">{{ formatCurrency(caso.monto_total) }}</p></div>
                             <div><p class="text-sm text-gray-500">Referencia del Crédito</p><p class="font-semibold">{{ caso.referencia_credito || 'N/A' }}</p></div>
                             <div><p class="text-sm text-gray-500">Tipo de Proceso</p><p class="font-semibold">{{ caso.tipo_proceso }}</p></div>
                             <div><p class="text-sm text-gray-500">Tipo de Garantía</p><p class="font-semibold">{{ caso.tipo_garantia_asociada }}</p></div>
                             <div><p class="text-sm text-gray-500">Fecha de Apertura</p><p class="font-semibold">{{ formatDate(caso.fecha_apertura) }}</p></div>
                             <div><p class="text-sm text-gray-500">Fecha de Vencimiento</p><p class="font-semibold">{{ formatDate(caso.fecha_vencimiento) }}</p></div>
                             <div v-if="caso.dias_en_mora > 0" class="md:col-span-2 bg-red-50 dark:bg-red-900/50 p-4 rounded-lg"><p class="text-sm text-red-600 dark:text-red-300">Días en Mora</p><p class="font-bold text-lg text-red-700 dark:text-red-200">{{ caso.dias_en_mora }} días</p></div>
                         </div>
                     </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <CumplimientoLegal :validaciones="caso.validaciones_legales" />
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-bold flex items-center"><BanknotesIcon class="h-6 w-6 mr-2 text-green-500"/>Historial de Pagos</h3>
                                <PrimaryButton v-if="can.update" @click="abrirModalPago">Registrar Pago +</PrimaryButton>
                            </div>
                            <div v-if="!caso.pagos || caso.pagos.length === 0" class="text-center py-6 text-gray-500 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">No hay pagos registrados para este caso.</div>
                            <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                                <li v-for="pago in caso.pagos" :key="pago.id" class="py-3 flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-green-600 dark:text-green-400">{{ formatCurrency(pago.monto_pagado) }}</p>
                                        <p class="text-xs text-gray-500 capitalize">Registrado el {{ formatDate(pago.fecha_pago) }} ({{ pago.motivo_pago }})</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-bold flex items-center"><DocumentDuplicateIcon class="h-6 w-6 mr-2 text-indigo-500"/>Arsenal de Documentos Generados</h3>
                                <PrimaryButton v-if="can.update" @click="abrirModalGenerar">Generar Documento +</PrimaryButton>
                            </div>
                            <div v-if="!caso.documentos_generados || caso.documentos_generados.length === 0" class="text-center py-6 text-gray-500 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">No hay documentos generados para este caso.</div>
                            <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                                <li v-for="doc in caso.documentos_generados" :key="doc.id" class="py-3 flex items-center justify-between">
                                    <div class="flex items-center">
                                        <LockClosedIcon v-if="doc.es_confidencial" class="h-5 w-5 text-yellow-500 mr-3 flex-shrink-0" title="Documento Confidencial" />
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ doc.nombre_base }}.docx</p>
                                            <p class="text-xs text-gray-500">Generado por {{ doc.usuario.name }} el {{ formatDate(doc.created_at) }} (v{{ doc.version_plantilla }})</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <a :href="route('documentos.descargar.docx', doc.id)" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-semibold" title="Descargar .docx"><img src="https://img.icons8.com/color/48/microsoft-word-2019--v1.png" class="h-6 w-6 mr-1"/><span>DOCX</span></a>
                                        <a :href="route('documentos.descargar.pdf', doc.id)" class="inline-flex items-center text-red-600 hover:text-red-800 text-sm font-semibold" title="Descargar .pdf"><img src="https://img.icons8.com/color/48/pdf-2.png" class="h-6 w-6 mr-1"/><span>PDF</span></a>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-bold">Documentos Adjuntos (Pruebas)</h3>
                                <button v-if="can.update" @click="openUploadModal" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Subir Prueba +</button>
                            </div>
                            <div v-if="!caso.documentos || caso.documentos.length === 0" class="text-center py-6 text-gray-500 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">No hay documentos adjuntos.</div>
                            <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                                <li v-for="doc in caso.documentos" :key="doc.id" class="py-3 flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ doc.tipo_documento }}</p>
                                        <p class="text-xs text-gray-500">Subido el {{ formatDate(doc.fecha_carga) }}</p>
                                    </div>
                                    <div>
                                        <a :href="route('documentos-caso.view', doc.id)" target="_blank" class="text-indigo-600 hover:underline text-sm mr-4">Ver</a>
                                        <button v-if="can.update" @click="confirmDocumentDeletion(doc)" class="text-red-600 hover:underline text-sm">Eliminar</button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- ======================================================= -->
                    <!-- ===== AQUÍ VA LA CAJA NEGRA DEL CASO ===== -->
                    <!-- ======================================================= -->
                    <HistorialAuditoria :eventos="caso.auditoria" />

                </div>

                <div class="lg:col-span-1 space-y-8">
                     <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                         <div class="p-6">
                             <h3 class="text-lg font-bold mb-4">Partes Involucradas</h3>
                             <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                                 <div class="py-3"><dt class="text-sm font-medium text-gray-500">Deudor</dt><dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ caso.deudor ? caso.deudor.nombre_completo : 'No Asignado' }}</dd></div>
                                 <div class="py-3"><dt class="text-sm font-medium text-gray-500">Codeudor 1</dt><dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ caso.codeudor1 ? caso.codeudor1.nombre_completo : 'No aplica' }}</dd></div>
                                 <div class="py-3"><dt class="text-sm font-medium text-gray-500">Codeudor 2</dt><dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ caso.codeudor2 ? caso.codeudor2.nombre_completo : 'No aplica' }}</dd></div>
                                 <div class="py-3"><dt class="text-sm font-medium text-gray-500">Abogado/Gestor</dt><dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ caso.user ? caso.user.name : 'No Asignado' }}</dd></div>
                             </dl>
                         </div>
                     </div>
                     <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                         <div class="p-6">
                             <PrimaryButton @click="abrirModalAlerta" class="w-full justify-center">
                                 <BellAlertIcon class="h-5 w-5 mr-2" />
                                 Programar Alerta / Recordatorio
                             </PrimaryButton>
                         </div>
                     </div>
                     <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                         <div class="p-6">
                             <h3 class="text-lg font-bold mb-4 flex items-center"><ClockIcon class="h-6 w-6 mr-2 text-gray-500 dark:text-gray-400" />Bitácora de Actividad</h3>
                             <div class="relative border-l-2 border-gray-200 dark:border-gray-700 ml-3">
                                 <div v-if="!caso.bitacoras || caso.bitacoras.length === 0" class="pl-8 pb-4"><p class="text-sm text-gray-500">No hay actividades registradas.</p></div>
                                 <ol v-else class="space-y-6">
                                     <li v-for="item in caso.bitacoras" :key="item.id" class="relative pl-8">
                                         <div class="absolute -left-[7px] top-1 h-3 w-3 rounded-full bg-indigo-500 ring-4 ring-white dark:ring-gray-800"></div>
                                         <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ item.accion }}</p>
                                         <p v-if="item.comentario" class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ item.comentario }}</p>
                                         <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">Por <span class="font-medium">{{ item.user ? item.user.name : 'Usuario no encontrado' }}</span></p>
                                         <p class="text-xs text-gray-400 dark:text-gray-500">{{ formatDateTime(item.created_at) }}</p>
                                     </li>
                                 </ol>
                             </div>
                         </div>
                     </div>
                </div>
            </div>
        </div>

        <Modal :show="confirmingDocumentUpload" @close="closeUploadModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Subir Nuevo Documento de Prueba</h2>
                <form @submit.prevent="submitDocument" class="mt-6 space-y-6">
                    <div>
                        <InputLabel for="tipo_documento_upload" value="Tipo de Documento" />
                        <select v-model="docForm.tipo_documento" id="tipo_documento_upload" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                            <option>pagaré</option><option>carta instrucciones</option><option>certificación saldo</option><option>libranza</option><option>cédula deudor</option><option>cédula codeudor</option><option>otros</option>
                        </select>
                        <InputError :message="docForm.errors.tipo_documento" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="fecha_carga" value="Fecha de Carga" />
                        <TextInput v-model="docForm.fecha_carga" id="fecha_carga" type="date" class="mt-1 block w-full" required />
                        <InputError :message="docForm.errors.fecha_carga" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="archivo_upload" value="Archivo (Máx 5MB)" />
                        <input id="archivo_upload" type="file" @input="docForm.archivo = $event.target.files[0]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"/>
                        <InputError :message="docForm.errors.archivo" class="mt-2" />
                    </div>
                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="closeUploadModal"> Cancelar </SecondaryButton>
                        <PrimaryButton class="ms-3" :class="{ 'opacity-25': docForm.processing }" :disabled="docForm.processing">Guardar Documento</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
        
        <Modal :show="confirmingDocumentDeletion" @close="closeDeleteModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">¿Eliminar Documento de Prueba?</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" v-if="documentToDelete">¿Estás seguro de que quieres eliminar permanentemente el documento: <span class="font-medium">{{ documentToDelete.tipo_documento }}</span>?</p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeDeleteModal"> Cancelar </SecondaryButton>
                    <DangerButton class="ms-3" @click="deleteDocument">Eliminar Documento</DangerButton>
                </div>
            </div>
        </Modal>

        <Modal :show="mostrandoModalGenerar" @close="cerrarModalGenerar">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Generar Documento desde Plantilla</h2>
                <form @submit.prevent="submitGenerarDocumento" class="mt-6 space-y-6">
                    <div>
                        <InputLabel for="plantilla_id_generate" value="Plantilla a utilizar" />
                        <SelectInput id="plantilla_id_generate" class="mt-1 block w-full" v-model="generarDocForm.plantilla_id" required>
                            <option disabled :value="null">-- Seleccione una plantilla --</option>
                            <option v-for="plantilla in plantillas" :key="plantilla.id" :value="plantilla.id">{{ plantilla.nombre }} (v{{ plantilla.version }})</option>
                        </SelectInput>
                        <InputError class="mt-2" :message="generarDocForm.errors.plantilla_id" />
                    </div>
                    <div>
                        <InputLabel for="observaciones_generate" value="Observaciones (Opcional)" />
                        <Textarea id="observaciones_generate" class="mt-1 block w-full" v-model="generarDocForm.observaciones" rows="3" />
                        <InputError class="mt-2" :message="generarDocForm.errors.observaciones" />
                    </div>
                    <div class="flex items-start">
                        <div class="flex items-center h-5"><Checkbox id="es_confidencial_generate" v-model:checked="generarDocForm.es_confidencial" /></div>
                        <div class="ml-3 text-sm">
                            <InputLabel for="es_confidencial_generate" value="Documento Confidencial" />
                            <p class="text-xs text-gray-500 dark:text-gray-400">Si se marca, no será visible para usuarios con rol de cliente.</p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="cerrarModalGenerar"> Cancelar </SecondaryButton>
                        <PrimaryButton class="ms-3" :class="{ 'opacity-25': generarDocForm.processing }" :disabled="generarDocForm.processing">Generar Documento</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="mostrandoModalPago" @close="cerrarModalPago">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Registrar Nuevo Pago</h2>
                <form @submit.prevent="submitPago" class="mt-6 space-y-6">
                    <div>
                        <InputLabel for="monto_pagado" value="Monto Pagado" />
                        <TextInput v-model="pagoForm.monto_pagado" id="monto_pagado" type="number" step="0.01" class="mt-1 block w-full" required />
                        <InputError :message="pagoForm.errors.monto_pagado" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="fecha_pago" value="Fecha del Pago" />
                        <TextInput v-model="pagoForm.fecha_pago" id="fecha_pago" type="date" class="mt-1 block w-full" required />
                        <InputError :message="pagoForm.errors.fecha_pago" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="motivo_pago" value="Motivo del Pago" />
                        <SelectInput v-model="pagoForm.motivo_pago" id="motivo_pago" class="mt-1 block w-full" required>
                            <option value="parcial">Pago Parcial</option>
                            <option value="acuerdo">Pago por Acuerdo</option>
                            <option value="sentencia">Pago por Sentencia</option>
                            <option value="total">Pago Total (Cierre de Caso)</option>
                        </SelectInput>
                        <InputError :message="pagoForm.errors.motivo_pago" class="mt-2" />
                    </div>
                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="cerrarModalPago">Cancelar</SecondaryButton>
                        <PrimaryButton class="ms-3" :class="{ 'opacity-25': pagoForm.processing }" :disabled="pagoForm.processing">Registrar Pago</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="mostrandoModalAlerta" @close="cerrarModalAlerta">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Programar Alerta Manual</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Cree un recordatorio para usted mismo sobre este caso. La alerta aparecerá en su bandeja de notificaciones.
                </p>
                <form @submit.prevent="submitAlerta" class="mt-6 space-y-6">
                    <div>
                        <InputLabel for="mensaje_alerta" value="Mensaje del Recordatorio" />
                        <Textarea v-model="alertaForm.mensaje" id="mensaje_alerta" class="mt-1 block w-full" required rows="4" />
                        <InputError :message="alertaForm.errors.mensaje" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="programado_para" value="Fecha de la Alerta (Opcional)" />
                        <TextInput v-model="alertaForm.programado_para" id="programado_para" type="date" class="mt-1 block w-full" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Si no selecciona fecha, la alerta se creará inmediatamente.</p>
                        <InputError :message="alertaForm.errors.programado_para" class="mt-2" />
                    </div>
                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="cerrarModalAlerta">Cancelar</secondaryButton>
                        <PrimaryButton class="ms-3" :class="{ 'opacity-25': alertaForm.processing }" :disabled="alertaForm.processing">Programar Alerta</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>