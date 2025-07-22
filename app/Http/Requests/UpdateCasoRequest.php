<?php

namespace App\Http\Requests;

use App\Models\Cooperativa;
use App\Models\RequisitoDocumento; // ¡Importante!
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCasoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('caso'));
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
            'user_id' => ['required', 'exists:users,id'],
            'referencia_credito' => ['nullable', 'string', 'max:255'],
            'tipo_proceso' => ['required', Rule::in(['ejecutivo singular', 'hipotecario', 'prendario', 'libranza'])],
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
            
            // --- LÓGICA DE CONTROL Y BLOQUEO ---
            'etapa_actual' => ['nullable', 'string', 'max:255'],
            'bloqueado' => ['required', 'boolean'],
            'motivo_bloqueo' => ['nullable', 'string', 'max:1000', 'required_if:bloqueado,true'],

            // --- REGLA DEL GUARDIÁN DEL PROCESO ---
            'estado_proceso' => [
                'required', 
                Rule::in(['prejurídico', 'demandado', 'en ejecución', 'sentencia', 'cerrado']),
                function ($attribute, $value, $fail) {
                    $caso = $this->route('caso');
                    // La validación solo se activa si se intenta cambiar el estado y no es para volver a 'prejurídico'.
                    if ($value !== $caso->estado_proceso && $value !== 'prejurídico') {
                        // Buscamos en la biblioteca todas las reglas que aplican a este caso.
                        $requisitos = RequisitoDocumento::where('tipo_proceso', $caso->tipo_proceso)
                            ->where(function($query) use ($caso) {
                                $query->where('cooperativa_id', $caso->cooperativa_id)
                                      ->orWhereNull('cooperativa_id');
                            })
                            ->pluck('tipo_documento_requerido')->unique();

                        if ($requisitos->isEmpty()) {
                            return; // No hay requisitos, se puede avanzar.
                        }
                        
                        $documentosDelCaso = $caso->documentos()->pluck('tipo_documento')->unique();
                        $documentosFaltantes = $requisitos->diff($documentosDelCaso);

                        if ($documentosFaltantes->isNotEmpty()) {
                            $fail('No se puede avanzar el estado. Faltan los siguientes documentos obligatorios: ' . $documentosFaltantes->implode(', '));
                        }
                    }
                }
            ],
        ];
    }
}
