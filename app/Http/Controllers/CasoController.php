<?php

namespace App\Http\Controllers;

// Tus controladores y modelos existentes
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

class CasoController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', Caso::class);
        $user = Auth::user();
        $query = Caso::with(['cooperativa', 'deudor', 'user']);

        if ($user->tipo_usuario === 'admin') {
            // Sin filtro
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
            'casos' => $query->get(),
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
        ]);
    }
    
    /**
     * Almacena un nuevo caso, maneja la clonación de validaciones y dispara la validación automática.
     */
    public function store(StoreCasoRequest $request, IntegrationService $integrationService, ValidacionLegalService $validacionService): RedirectResponse
    {
        $this->authorize('create', Caso::class);
        $caso = Caso::create($request->validated());

        // ===== LÓGICA DE VALIDACIONES MEJORADA =====
        if ($request->filled('clonado_de_id')) {
            // Si es un clon, copiamos las validaciones del original.
            $casoOriginal = Caso::with('validacionesLegales.requisito')->find($request->clonado_de_id);
            if ($casoOriginal && $casoOriginal->validacionesLegales->isNotEmpty()) {
                foreach ($casoOriginal->validacionesLegales as $validacionOriginal) {
                    $caso->validacionesLegales()->create([
                        'requisito_id' => $validacionOriginal->requisito_id,
                        'estado' => $validacionOriginal->estado,
                        'nivel_riesgo' => $validacionOriginal->requisito->nivel_riesgo ?? 'medio',
                        'observacion' => 'Validación clonada desde el caso #' . $casoOriginal->id,
                        'accion_correctiva' => $validacionOriginal->accion_correctiva,
                    ]);
                }
            }
        } else {
            // Si es un caso nuevo, generamos las validaciones desde cero.
            $validacionService->generarValidacionesParaCaso($caso);
        }
        // ==========================================================

        $bitacora = $caso->bitacoras()->create([
            'user_id' => auth()->id(),
            'accion' => $request->clonado_de_id ? 'Clonación de Caso' : 'Creación del Caso',
            'comentario' => $request->clonado_de_id 
                ? 'El caso fue creado como un clon del caso #' . $request->clonado_de_id
                : 'El caso ha sido registrado en el sistema.'
        ]);
        \App\Events\EventoAuditoriaSospechoso::dispatch($bitacora, auth()->user());

        $cooperativa = Cooperativa::find($request->cooperativa_id);
        $validationMessage = '';
        if ($cooperativa && $cooperativa->nit) {
            $urlDelSimulador = url('/api/simulador/supersolidaria/validar/' . $cooperativa->nit);
            $respuesta = $integrationService->ejecutar('Supersolidaria (Simulador)', 'get', $urlDelSimulador);
            if (isset($respuesta['error']) && $respuesta['error']) {
                $validationMessage = 'ADVERTENCIA: No se pudo validar la cooperativa en Supersolidaria. Código de error: ' . ($respuesta['status_code'] ?? 'Desconocido');
            } else {
                $estado = $respuesta['estado'] ?? 'Desconocido';
                $validationMessage = "VALIDACIÓN EXITOSA: El estado de la cooperativa en Supersolidaria es: '{$estado}'.";
            }
        } else {
            $validationMessage = 'ADVERTENCIA: La cooperativa no tiene un NIT registrado para poder ser validada.';
        }

        return to_route('casos.show', $caso->id)->with([
            'success' => '¡Caso registrado exitosamente!',
            'validation_info' => $validationMessage,
        ]);
    }

    /**
     * Muestra el detalle de un caso, cargando todas las relaciones necesarias.
     */
    public function show(Caso $caso): Response
    {
        $this->authorize('view', $caso);

        // Cargamos todas las relaciones que tus componentes necesitan de forma eficiente
        $caso->load([
            'deudor', 'cooperativa', 'user', 
            'documentos', 
            'bitacoras.user', // Para la Bitácora de Actividad y el Rastro de Auditoría
            'documentosGenerados.usuario',
            'pagos', 
            'validacionesLegales.requisito', // Para el Cumplimiento Legal
        ]);
        
        // Hacemos que la relación 'auditoria' use los datos de 'bitacoras' para consistencia
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
        $cooperativas = ($user->tipo_usuario === 'admin') ? Cooperativa::select('id', 'nombre')->get() : $user->cooperativas()->select('id', 'nombre')->get();
        $abogadosYGestores = User::whereIn('tipo_usuario', ['abogado', 'gestor'])->select('id', 'name')->get();
        $personas = Persona::select('id', 'nombre_completo', 'numero_documento')->get();
        return Inertia::render('Casos/Edit', [
            'caso' => $caso,
            'cooperativas' => $cooperativas,
            'abogadosYGestores' => $abogadosYGestores,
            'personas' => $personas,
        ]);
    }
    
    public function update(UpdateCasoRequest $request, Caso $caso, ValidacionLegalService $validacionService): RedirectResponse
    {
        $this->authorize('update', $caso);
        $caso->update($request->validated());
        $validacionService->generarValidacionesParaCaso($caso);
        $caso->load('validacionesLegales');
        if ($request->input('estado_proceso') === 'demandado' && $caso->estado_proceso !== 'demandado') {
            $fallasGravesOMedias = $caso->validacionesLegales()->where('estado', 'incumple')->whereIn('nivel_riesgo', ['alto', 'medio'])->exists();
            if ($fallasGravesOMedias) {
                return back()->with('error', 'Acción Bloqueada: No se puede radicar el caso porque existen fallas de cumplimiento de riesgo ALTO o MEDIO. Por favor, corríjalas primero.');
            }
        }
        $caso->bitacoras()->create(['user_id' => auth()->id(), 'accion' => 'Actualización de Caso', 'comentario' => 'Se actualizaron los datos principales del caso.']);
        return to_route('casos.show', $caso->id)->with('success', '¡Caso actualizado y re-validado exitosamente!');
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
        return Inertia::render('Casos/Create', [
            'casoAClonar' => $caso,
            'cooperativas' => Cooperativa::all(['id', 'nombre']),
            'abogadosYGestores' => User::whereIn('tipo_usuario', ['abogado', 'gestor'])->select('id', 'name')->get(),
            'personas' => Persona::select('id', 'nombre_completo', 'numero_documento')->get(),
        ]);
    }
}
