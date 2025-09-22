<?php

namespace App\Http\Controllers;

use App\Models\RequisitoDocumento;
use App\Models\Cooperativa;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class RequisitoDocumentoController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     * Muestra todas las reglas de requisitos definidas.
     */
    public function index(): Response
    {
        // Por ahora, asumimos que solo los admins gestionan esto.
        // Podríamos crear una política si fuera necesario.
        $this->authorize('isAdmin'); 

        return Inertia::render('Requisitos/Index', [
            'requisitos' => RequisitoDocumento::with('cooperativa')->get(),
            'cooperativas' => Cooperativa::all(['id', 'nombre']),
            // Listas predefinidas para los formularios
            'tipos_proceso' => ['ejecutivo singular', 'hipotecario', 'prendario', 'libranza'],
            'tipos_documento' => ['pagaré', 'carta instrucciones', 'certificación saldo', 'libranza', 'cédula deudor', 'cédula codeudor', 'otros'],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Guarda una nueva regla en la base de datos.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('isAdmin');

        $request->validate([
            'cooperativa_id' => 'nullable|exists:cooperativas,id',
            'tipo_proceso' => 'required|string',
            'tipo_documento_requerido' => 'required|string',
        ]);

        RequisitoDocumento::create($request->all());

        return to_route('requisitos.index')->with('success', '¡Requisito creado exitosamente!');
    }

    /**
     * Remove the specified resource from storage.
     * Elimina una regla existente.
     */
    public function destroy(RequisitoDocumento $requisito): RedirectResponse
    {
        $this->authorize('isAdmin');
        
        $requisito->delete();

        return to_route('requisitos.index')->with('success', '¡Requisito eliminado exitosamente!');
    }
}
