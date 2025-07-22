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
        return Inertia::render('Personas/Index', [
            'personas' => Persona::all(),
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
        // La vista de detalle para una persona puede no ser necesaria,
        // pero la dejamos preparada por si acaso.
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
        
        // Alerta: Eliminar una persona puede dejar casos sin deudor/codeudor.
        // La base de datos lo manejará (poniendo a null), pero es una acción delicada.
        $persona->delete();
        
        return to_route('personas.index')->with('success', '¡Persona eliminada del directorio!');
    }
}
