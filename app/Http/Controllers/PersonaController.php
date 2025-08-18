<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdatePersonaRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PersonaController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', Persona::class);

        // ===== CAMBIO IMPORTANTE =====
        // En lugar de Persona::all(), seleccionamos solo las columnas que necesitamos.
        // Esto es más eficiente y nos aseguramos de traer los datos de contacto.
        $personas = Persona::select(
            'id', 
            'nombre_completo', 
            'tipo_documento', 
            'numero_documento',
            'celular_1', // <-- Dato necesario para WhatsApp
            'correo_1'   // <-- Dato necesario para Email
        )->get();

        return Inertia::render('Personas/Index', [
            'personas' => $personas,
            'can' => [
                'delete_personas' => Auth::user()->can('delete', Persona::class)
            ]
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Persona::class);
        return Inertia::render('Personas/Create');
    }

    public function store(StorePersonaRequest $request): RedirectResponse
    {
        $this->authorize('create', Persona::class);
        Persona::create($request->validated());
        return to_route('personas.index')->with('success', '¡Persona registrada exitosamente!');
    }

    public function show(Persona $persona)
    {
        $this->authorize('view', $persona);
        return redirect()->route('personas.edit', $persona);
    }

    public function edit(Persona $persona): Response
    {
        $this->authorize('update', $persona);
        return Inertia::render('Personas/Edit', [
            'persona' => $persona
        ]);
    }

    public function update(UpdatePersonaRequest $request, Persona $persona): RedirectResponse
    {
        $this->authorize('update', $persona);
        $persona->update($request->validated());
        return to_route('personas.index')->with('success', '¡Datos de persona actualizados!');
    }



    public function destroy(Persona $persona): RedirectResponse
    {
        $this->authorize('delete', $persona);
        $persona->delete();
        return to_route('personas.index')->with('success', '¡Persona eliminada del directorio!');
    }
}
