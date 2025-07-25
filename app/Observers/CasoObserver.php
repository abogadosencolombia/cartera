<?php

namespace App\Observers;

use App\Models\Caso;
use App\Models\RequisitoDocumento;
use App\Models\ValidacionLegal;

class CasoObserver
{
    /**
     * Handle the Caso "created" event.
     */
    public function created(Caso $caso): void
    {
        // Si el caso es un clon, copiamos las validaciones del original.
        if (!is_null($caso->clonado_de_id)) {
            $this->clonarValidaciones($caso);
        } else {
            // Si es un caso nuevo, las generamos desde los requisitos.
            $this->generarValidacionesDesdeRequisitos($caso);
        }
    }

    /**
     * Clona las validaciones de un caso original al nuevo.
     */
    protected function clonarValidaciones(Caso $nuevoCaso): void
    {
        $casoOriginal = Caso::with('validacionesLegales.requisito')->find($nuevoCaso->clonado_de_id);

        if ($casoOriginal && $casoOriginal->validacionesLegales->isNotEmpty()) {
            foreach ($casoOriginal->validacionesLegales as $validacionOriginal) {
                $nuevoCaso->validacionesLegales()->create([
                    'requisito_id' => $validacionOriginal->requisito_id,
                    'estado' => $validacionOriginal->estado,
                    'nivel_riesgo' => $validacionOriginal->requisito->nivel_riesgo ?? 'medio',
                    'observacion' => 'Validación clonada desde el caso #' . $casoOriginal->id,
                    'accion_correctiva' => $validacionOriginal->accion_correctiva,
                ]);
            }
        }
    }

    /**
     * Genera las validaciones para un caso nuevo basado en los requisitos.
     */
    protected function generarValidacionesDesdeRequisitos(Caso $caso): void
    {
        $requisitos = RequisitoDocumento::where('tipo_proceso', $caso->tipo_proceso)
            ->where(function ($query) use ($caso) {
                $query->where('cooperativa_id', $caso->cooperativa_id)
                      ->orWhereNull('cooperativa_id');
            })
            ->get();

        if ($requisitos->isEmpty()) {
            return;
        }

        foreach ($requisitos as $requisito) {
            $documentoExiste = $caso->documentos()->where('tipo_documento', $requisito->tipo_documento_requerido)->exists();
            
            ValidacionLegal::create([
                'caso_id' => $caso->id,
                'requisito_id' => $requisito->id, 
                'tipo' => 'documento_requerido',
                'estado' => $documentoExiste ? 'cumple' : 'incumple',
                'observacion' => "Verificación automática para: '{$requisito->tipo_documento_requerido}'.",
                'nivel_riesgo' => $requisito->nivel_riesgo ?? 'medio',
            ]);
        }
    }
}
