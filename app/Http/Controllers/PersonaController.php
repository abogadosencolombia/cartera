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

        $personas = Persona::select(
            'id',
            'nombre_completo',
            'tipo_documento',
            'numero_documento',
            'celular_1',
            'correo_1',
            'social_links'
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
        $data = $request->validated();

        if (isset($data['social_links'])) {
            $data['social_links'] = array_values(array_filter($data['social_links'], fn ($r) =>
                isset($r['url']) && trim((string)$r['url']) !== ''
            ));
        }

        Persona::create($data);
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
        return Inertia::render('Personas/Edit', ['persona' => $persona]);
    }

    public function update(UpdatePersonaRequest $request, Persona $persona): RedirectResponse
    {
        $this->authorize('update', $persona);
        $data = $request->validated();

        if (isset($data['social_links'])) {
            $data['social_links'] = array_values(array_filter($data['social_links'], fn ($r) =>
                isset($r['url']) && trim((string)$r['url']) !== ''
            ));
        }

        $persona->update($data);
        return to_route('personas.index')->with('success', '¡Datos de persona actualizados!');
    }

    public function destroy(Persona $persona): RedirectResponse
    {
        $this->authorize('delete', $persona);
        $persona->delete();
        return to_route('personas.index')->with('success', '¡Persona eliminada del directorio!');
    }
}
