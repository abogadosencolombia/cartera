<?php

namespace App\Http\Requests;

use App\Models\Cooperativa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCasoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Caso::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // --- DATOS BÁSICOS DEL CASO ---
            'cooperativa_id' => ['required', 'exists:cooperativas,id'],
            'user_id' => ['required', 'exists:users,id'], // El abogado/gestor asignado
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
            'tasa_moratoria' => [
                'required',
                'numeric',
                'min:0',
                // REGLA DE NEGOCIO AVANZADA #1:
                // La tasa de mora del caso no puede superar la tasa máxima permitida por la cooperativa.
                function ($attribute, $value, $fail) {
                    $cooperativa = Cooperativa::find($this->input('cooperativa_id'));
                    if ($cooperativa && $value > $cooperativa->tasa_maxima_moratoria) {
                        $fail("La tasa de mora ({$value}%) no puede superar el máximo de la cooperativa ({$cooperativa->tasa_maxima_moratoria}%).");
                    }
                },
            ],

            // --- PERSONAS INVOLUCRADAS ---
            'deudor_id' => ['required', 'exists:personas,id'],
            'codeudor1_id' => ['nullable', 'exists:personas,id', 'different:deudor_id'],
            'codeudor2_id' => ['nullable', 'exists:personas,id', 'different:deudor_id', 'different:codeudor1_id'],

            // --- ¡LA REGLA QUE FALTABA! ---
            // Le decimos al sistema que el campo 'clonado_de_id' es permitido.
            // Debe ser 'nullable' (porque los casos nuevos no lo tienen) y, si existe,
            // debe corresponder a un caso real en la base de datos.
            'clonado_de_id' => ['nullable', 'exists:casos,id'],
        ];
    }
}
