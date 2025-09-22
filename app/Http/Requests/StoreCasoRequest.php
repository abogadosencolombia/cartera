<?php

namespace App\Http\Requests;

use App\Models\Cooperativa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCasoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Caso::class);
    }

    public function rules(): array
    {
        $subtipos_proceso = ['CURADURIA', 'DIVISORIO', 'GARANTIA REAL', 'HIPOTECARIO', 'INSOLVENCIA', 'LABORAL', 'MIXTO', 'MUEBLE', 'PAGO DIRECTO', 'PRENDARIO', 'SINGULAR', 'SUCESIÓN'];
        $etapas_procesales = ['Avalúo y Remate', 'Notificación', 'Contestación Demanda', 'Audiencia Inicial', 'Audiencia de Instrucción y Juzgamiento', 'Sentencia', 'Apelación', 'Ejecución de Sentencia', 'Liquidación', 'Terminación'];

        return [
            // --- DATOS BÁSICOS DEL CASO ---
            'cooperativa_id' => ['required', 'exists:cooperativas,id'],
            'user_id' => ['required', 'exists:users,id'],
            'referencia_credito' => ['nullable', 'string', 'max:255'],
            'tipo_proceso' => ['required', Rule::in(['ejecutivo singular', 'hipotecario', 'prendario', 'libranza'])],
            'estado_proceso' => ['required', Rule::in(['prejurídico', 'demandado', 'en ejecución', 'sentencia', 'cerrado'])],
            'tipo_garantia_asociada' => ['required', Rule::in(['codeudor', 'hipotecaria', 'prendaria', 'sin garantía'])],
            'fecha_apertura' => ['required', 'date', 'before_or_equal:today'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha_apertura'],
            'origen_documental' => ['required', Rule::in(['pagaré', 'libranza', 'contrato', 'otro'])],
            
            // --- DATOS FINANCIEROS ---
            'monto_total' => ['required', 'numeric', 'min:0'],
            'tasa_interes_corriente' => ['required', 'numeric', 'min:0', 'max:100'],
            'tasa_moratoria' => ['required', 'numeric', 'min:0'],

            // --- PERSONAS INVOLUCRADAS ---
            'deudor_id' => ['required', 'exists:personas,id'],
            'codeudor1_id' => ['nullable', 'exists:personas,id', 'different:deudor_id'],
            'codeudor2_id' => ['nullable', 'exists:personas,id', 'different:deudor_id', 'different:codeudor1_id'],
            
            'clonado_de_id' => ['nullable', 'exists:casos,id'],

            // --- NUEVAS REGLAS DE VALIDACIÓN ---
            'subtipo_proceso' => ['nullable', 'string', Rule::in($subtipos_proceso)],
            'etapa_procesal' => ['nullable', 'string', Rule::in($etapas_procesales)],
            'juzgado_id' => ['nullable', 'integer', 'exists:juzgados,id'],
        ];
    }
}
