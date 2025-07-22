<?php

namespace App\Http\Controllers;

use App\Models\Caso;
use App\Models\PagoCaso;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class PagoCasoController extends Controller
{
    /**
     * Almacena un nuevo pago para un caso específico.
     */
    public function store(Request $request, Caso $caso): RedirectResponse
    {
        // Aquí podríamos añadir una política de seguridad para autorizar la acción
        // $this->authorize('registrarPago', $caso);

        $validated = $request->validate([
            'monto_pagado' => ['required', 'numeric', 'min:0'],
            'fecha_pago' => ['required', 'date'],
            'motivo_pago' => ['required', 'in:total,parcial,acuerdo,sentencia'],
        ]);

        $caso->pagos()->create($validated);
        
        // Opcional: Registrar en la bitácora del caso
        $caso->bitacoras()->create([
            'user_id' => auth()->id(),
            'accion' => 'Registro de Pago',
            'comentario' => 'Se registró un pago de ' . number_format($validated['monto_pagado'], 2) . ' con fecha ' . $validated['fecha_pago']
        ]);

        return back()->with('success', '¡Pago registrado exitosamente!');
    }
}
