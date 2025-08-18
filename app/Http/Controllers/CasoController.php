<?php

namespace App\Http\Controllers;

use App\Models\Caso;
use App\Models\Cooperativa;
use App\Models\Persona;
use App\Models\User;
use App\Models\PlantillaDocumento;
use App\Http\Requests\StoreCasoRequest;
use App\Http\Requests\UpdateCasoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Services\IntegrationService;
use App\Services\ValidacionLegalService;
use App\Models\Juzgado;

class CasoController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', Caso::class);
        $user = Auth::user();
        $query = Caso::with(['cooperativa', 'deudor', 'user']);

        if ($user->tipo_usuario === 'admin') {
            // No additional filters
        } elseif (in_array($user->tipo_usuario, ['gestor', 'abogado'])) {
            $cooperativaIds = $user->cooperativas->pluck('id');
            $query->whereIn('cooperativa_id', $cooperativaIds);
        } elseif ($user->tipo_usuario === 'cli') {
            $query->where(function($q) use ($user) {
                $q->where('deudor_id', $user->persona_id)
                  ->orWhere('codeudor1_id', $user->persona_id)
                  ->orWhere('codeudor2_id', $user->persona_id);
            });
        }

        return Inertia::render('Casos/Index', [
            'casos' => $query->latest()->get(),
            'can' => ['delete_cases' => $user->can('delete', Caso::class)]
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Caso::class);
        $user = Auth::user();

        $cooperativas = ($user->tipo_usuario === 'admin')
            ? Cooperativa::select('id', 'nombre')->get()
            : $user->cooperativas()->select('id', 'nombre')->get();
        
        $abogadosYGestores = User::whereIn('tipo_usuario', ['abogado', 'gestor'])->select('id', 'name')->get();
        $personas = Persona::select('id', 'nombre_completo', 'numero_documento')->get();

        return Inertia::render('Casos/Create', [
            'cooperativas' => $cooperativas,
            'abogadosYGestores' => $abogadosYGestores,
            'personas' => $personas,
            'subtipos_proceso' => ['CURADURIA', 'DIVISORIO', 'GARANTIA REAL', 'HIPOTECARIO', 'INSOLVENCIA', 'LABORAL', 'MIXTO', 'MUEBLE', 'PAGO DIRECTO', 'PRENDARIO', 'SINGULAR', 'SUCESIÓN'],
            'etapas_procesales' => ['Avalúo y Remate', 'Notificación', 'Contestación Demanda', 'Audiencia Inicial', 'Audiencia de Instrucción y Juzgamiento', 'Sentencia', 'Apelación', 'Ejecución de Sentencia', 'Liquidación', 'Terminación'],
        ]);
    }
        
    public function store(StoreCasoRequest $request): RedirectResponse
    {
        $this->authorize('create', Caso::class);
        $caso = Caso::create($request->validated());
        
        $caso->bitacoras()->create([
            'user_id' => auth()->id(),
            'accion' => $request->clonado_de_id ? 'Clonación de Caso' : 'Creación del Caso',
            'comentario' => $request->clonado_de_id 
                ? 'El caso fue creado como un clon del caso #' . $request->clonado_de_id
                : 'El caso ha sido registrado en el sistema.'
        ]);

        return to_route('casos.show', $caso->id)->with('success', '¡Caso registrado exitosamente!');
    }

    public function show(Caso $caso): Response
    {
        $this->authorize('view', $caso);

        // ===== ¡AQUÍ ESTÁ LA CORRECCIÓN! =====
        // Añadimos 'codeudor1' y 'codeudor2' a la lista de datos a cargar.
        $caso->load([
            'deudor', 
            'codeudor1', // <-- LÍNEA AÑADIDA
            'codeudor2', // <-- LÍNEA AÑADIDA
            'cooperativa', 
            'user', 
            'documentos', 
            'bitacoras.user', 
            'documentosGenerados.usuario',
            'pagos.usuario',
            'validacionesLegales.requisito',
            'juzgado'
        ]);
        
        $caso->setRelation('auditoria', $caso->bitacoras);

        $plantillasDisponibles = PlantillaDocumento::where('activa', true)
            ->where(function ($query) use ($caso) {
                $query->where('cooperativa_id', $caso->cooperativa_id)->orWhereNull('cooperativa_id');
            })->where(function ($query) use ($caso) {
                $query->whereNull('aplica_a')->orWhereJsonContains('aplica_a', $caso->tipo_proceso);
            })->orderBy('nombre')->get(['id', 'nombre', 'version']);

        return Inertia::render('Casos/Show', [
            'caso' => $caso,
            'plantillas' => $plantillasDisponibles,
            'can' => [
                'update' => Auth::user()->can('update', $caso),
                'delete' => Auth::user()->can('delete', $caso),
            ]
        ]);
    }

    public function edit(Caso $caso): Response
    {
        $this->authorize('update', $caso);
        $user = Auth::user();

        $caso->load('juzgado');

        $cooperativas = ($user->tipo_usuario === 'admin') ? Cooperativa::select('id', 'nombre')->get() : $user->cooperativas()->select('id', 'nombre')->get();
        $abogadosYGestores = User::whereIn('tipo_usuario', ['abogado', 'gestor'])->select('id', 'name')->get();
        $personas = Persona::select('id', 'nombre_completo', 'numero_documento')->get();

        return Inertia::render('Casos/Edit', [
            'caso' => $caso,
            'cooperativas' => $cooperativas,
            'abogadosYGestores' => $abogadosYGestores,
            'personas' => $personas,
            'subtipos_proceso' => ['CURADURIA', 'DIVISORIO', 'GARANTIA REAL', 'HIPOTECARIO', 'INSOLVENCIA', 'LABORAL', 'MIXTO', 'MUEBLE', 'PAGO DIRECTO', 'PRENDARIO', 'SINGULAR', 'SUCESIÓN'],
            'etapas_procesales' => ['Avalúo y Remate', 'Notificación', 'Contestación Demanda', 'Audiencia Inicial', 'Audiencia de Instrucción y Juzgamiento', 'Sentencia', 'Apelación', 'Ejecución de Sentencia', 'Liquidación', 'Terminación'],
        ]);
    }

    public function update(UpdateCasoRequest $request, Caso $caso): RedirectResponse
    {
        $this->authorize('update', $caso);
        $caso->update($request->validated());

        $caso->bitacoras()->create([
            'user_id' => auth()->id(), 
            'accion' => 'Actualización de Caso', 
            'comentario' => 'Se actualizaron los datos principales del caso.'
        ]);

        return to_route('casos.show', $caso->id)->with('success', '¡Caso actualizado exitosamente!');
    }

    public function destroy(Caso $caso): RedirectResponse
    {
        $this->authorize('delete', $caso);
        $caso->delete();
        return to_route('casos.index')->with('success', '¡Caso eliminado exitosamente!');
    }

    public function clonar(Caso $caso): Response
    {
        $this->authorize('create', Caso::class);
        $this->authorize('view', $caso);

        $caso->load('juzgado');

        return Inertia::render('Casos/Create', [
            'casoAClonar' => $caso,
            'cooperativas' => Cooperativa::all(['id', 'nombre']),
            'abogadosYGestores' => User::whereIn('tipo_usuario', ['abogado', 'gestor'])->select('id', 'name')->get(),
            'personas' => Persona::select('id', 'nombre_completo', 'numero_documento')->get(),
            'subtipos_proceso' => ['CURADURIA', 'DIVISORIO', 'GARANTIA REAL', 'HIPOTECARIO', 'INSOLVENCIA', 'LABORAL', 'MIXTO', 'MUEBLE', 'PAGO DIRECTO', 'PRENDARIO', 'SINGULAR', 'SUCESIÓN'],
            'etapas_procesales' => ['Avalúo y Remate', 'Notificación', 'Contestación Demanda', 'Audiencia Inicial', 'Audiencia de Instrucción y Juzgamiento', 'Sentencia', 'Apelación', 'Ejecución de Sentencia', 'Liquidación', 'Terminación'],
        ]);
    }
}
