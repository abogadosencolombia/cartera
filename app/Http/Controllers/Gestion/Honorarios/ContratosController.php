<?php

namespace App\Http\Controllers\Gestion\Honorarios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;

class ContratosController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->input('q',''));
        $estado = trim((string)$request->input('estado',''));
        $stats = ['activeValue'=>0,'activeCount'=>0,'closedCount'=>0];

        $contratosQuery = DB::table('contratos')
            ->where('contratos.estado', '!=', 'REESTRUCTURADO')
            ->join('personas', 'contratos.cliente_id', '=', 'personas.id')
            ->select(
                'contratos.*',
                'personas.nombre_completo as persona_nombre',
                DB::raw('(SELECT SUM(valor) FROM contrato_pagos WHERE contrato_pagos.contrato_id = contratos.id) as total_pagado')
            );

        if ($q !== '') {
            $contratosQuery->where(function ($w) use ($q) {
                $w->where('contratos.id','like',"%{$q}%")
                ->orWhere('personas.nombre_completo', 'like', "%{$q}%");
            });
        }
        if ($estado !== '' && Schema::hasColumn('contratos','estado')) {
            $contratosQuery->where('contratos.estado',$estado);
        }
        
        $contratos = $contratosQuery->orderByDesc('contratos.created_at')->paginate(10);

        try {
            if (Schema::hasTable('contratos')) {
                $active = DB::table('contratos')->whereIn('estado',['ACTIVO', 'PAGOS_PENDIENTES', 'EN_MORA']);
                $stats['activeCount']   = (clone $active)->count();
                $stats['activeValue']   = Schema::hasColumn('contratos','monto_total') ? (clone $active)->sum('monto_total') : 0;
                $stats['closedCount']   = Schema::hasColumn('contratos','estado') ? DB::table('contratos')->where('estado','CERRADO')->count() : 0;
            }
        } catch (\Throwable $e) {}

        return Inertia::render('Gestion/Honorarios/Contratos/Index', [
            'contratos' => $contratos,
            'filters'   => ['q'=>$q,'estado'=>$estado],
            'stats'     => $stats,
        ]);
    }

    public function create(Request $request)
    {
        $plantilla = null;
        if ($request->has('from')) {
            $contratoOrigen = DB::table('contratos')->find($request->input('from'));
            if ($contratoOrigen) {
                $plantilla = $contratoOrigen;
            }
        }

        $personas = [];
        $modalidades = ['CUOTAS','PAGO_UNICO','LITIS','CUOTA_MIXTA'];

        try {
            if (Schema::hasTable('personas')) {
                $personas = DB::table('personas')
                                ->select('id','nombre_completo as nombre')
                                ->orderBy('nombre_completo')
                                ->limit(500)
                                ->get();
            }
        } catch (\Throwable $e) {}

        return Inertia::render('Gestion/Honorarios/Contratos/Create', [
            'clientes'    => $personas,
            'modalidades' => $modalidades,
            'plantilla'   => $plantilla,
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'cliente_id' => ['required'],
            'modalidad'  => ['required', 'in:CUOTAS,PAGO_UNICO,LITIS,CUOTA_MIXTA'],
            'inicio'     => ['required', 'date'],
            'anticipo'   => ['nullable', 'numeric', 'min:0'],
            'nota'       => ['nullable', 'string'],
            'contrato_origen_id' => ['nullable', 'integer', 'exists:contratos,id'],
        ];

        $modalidad = $request->input('modalidad');

        if ($modalidad === 'CUOTAS' || $modalidad === 'PAGO_UNICO' || $modalidad === 'CUOTA_MIXTA') {
            $rules['monto_total'] = ['required', 'numeric', 'min:0'];
            $rules['cuotas']      = ['required', 'integer', 'min:1', 'max:120'];
        }

        if ($modalidad === 'LITIS' || $modalidad === 'CUOTA_MIXTA') {
            $rules['porcentaje_litis'] = ['required', 'numeric', 'min:0', 'max:100'];
        }

        $v = Validator::make($request->all(), $rules, [], ['cliente_id' => 'cliente']);
        $v->validate();

        $cliente_id         = $request->input('cliente_id');
        $monto_total        = round((float)$request->input('monto_total', 0), 2);
        $cuotas             = (int)$request->input('cuotas', 1);
        $anticipo           = round((float)($request->input('anticipo') ?? 0), 2);
        $nota               = (string)($request->input('nota') ?? '');
        $inicio             = (string)$request->input('inicio');
        $porcentaje_litis   = $request->input('porcentaje_litis');
        $contrato_origen_id = $request->input('contrato_origen_id');

        if ($anticipo > $monto_total && in_array($modalidad, ['CUOTAS', 'PAGO_UNICO', 'CUOTA_MIXTA'])) {
            return back()->withErrors(['anticipo' => 'El anticipo no puede superar el monto total.'])->withInput();
        }

        $idContrato = null;

        DB::transaction(function () use (&$idContrato, $cliente_id, $monto_total, $cuotas, $modalidad, $inicio, $anticipo, $nota, $porcentaje_litis, $contrato_origen_id) {
            
            if ($contrato_origen_id) {
                DB::table('contratos')->where('id', $contrato_origen_id)->update([
                    'estado' => 'REESTRUCTURADO',
                    'updated_at' => now(),
                ]);
            }

            $idContrato = DB::table('contratos')->insertGetId([
                'cliente_id'          => $cliente_id,
                'monto_total'         => $monto_total,
                'anticipo'            => $anticipo,
                'porcentaje_litis'    => $porcentaje_litis,
                'monto_base_litis'    => null,
                'modalidad'           => $modalidad,
                'estado'              => 'ACTIVO',
                'inicio'              => $inicio,
                'nota'                => $nota,
                'contrato_origen_id'  => $contrato_origen_id,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            if ($modalidad === 'LITIS') {
                return;
            }

            $neto = max(0, $monto_total - $anticipo);
            
            if ($modalidad === 'PAGO_UNICO') {
                DB::table('contrato_cuotas')->insert([
                    'contrato_id'       => $idContrato,
                    'numero'            => 1,
                    'fecha_vencimiento' => $inicio,
                    'valor'             => $neto,
                    'estado'            => $neto > 0 ? 'PENDIENTE' : 'PAGADA',
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            } elseif ($modalidad === 'CUOTAS' || $modalidad === 'CUOTA_MIXTA') {
                if ($cuotas < 1) $cuotas = 1;
                $netoCents  = (int) round($neto * 100);
                $baseCents  = intdiv($netoCents, $cuotas);
                $restoCents = $netoCents - ($baseCents * $cuotas);

                for ($i = 1; $i <= $cuotas; $i++) {
                    $valorCents = $baseCents + ($i <= $restoCents ? 1 : 0);
                    $valor      = $valorCents / 100.0;
                    $fecha      = Carbon::parse($inicio)->addMonthsNoOverflow($i - 1)->toDateString();

                    DB::table('contrato_cuotas')->insert([
                        'contrato_id'       => $idContrato,
                        'numero'            => $i,
                        'fecha_vencimiento' => $fecha,
                        'valor'             => $valor,
                        'estado'            => $valor > 0 ? 'PENDIENTE' : 'PAGADA',
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }
            }
        });

        return redirect()->route('honorarios.contratos.show', $idContrato)->with('success', 'Contrato creado.');
    }

    public function show($id)
    {
        if (!Schema::hasTable('contratos')) abort(404);
        
        $contrato = DB::table('contratos')->find($id);
        if (!$contrato) abort(404);

        $contratoOrigen = null;
        if ($contrato->contrato_origen_id) {
            $contratoOrigen = DB::table('contratos')->find($contrato->contrato_origen_id);
        }
        
        $cliente = null;
        if (Schema::hasTable('personas')) {
            $cliente = DB::table('personas')->select('id','nombre_completo as nombre')->where('id',$contrato->cliente_id)->first();
        }

        // ====================================================================================
        // INICIO DE LA MODIFICACIÓN: Cargar clientes y modalidades para el modal
        // ====================================================================================
        $clientes = [];
        $modalidades = ['CUOTAS','PAGO_UNICO','LITIS','CUOTA_MIXTA'];

        try {
            if (Schema::hasTable('personas')) {
                $clientes = DB::table('personas')
                                ->select('id','nombre_completo as nombre')
                                ->orderBy('nombre_completo')
                                ->limit(500)
                                ->get();
            }
        } catch (\Throwable $e) {}
        // ====================================================================================
        // FIN DE LA MODIFICACIÓN
        // ====================================================================================

        $total_cargos_valor = DB::table('contrato_cargos')->where('contrato_id', $id)->sum('monto');
        $total_pagos_valor  = DB::table('contrato_pagos')->where('contrato_id', $id)->sum('valor');

        $cuotas = DB::table('contrato_cuotas')
            ->where('contrato_id', $id)
            ->orderBy('numero')
            ->paginate(15, ['*'], 'cuotasPage');

        $cargos = DB::table('contrato_cargos as c')
            ->leftJoin('contrato_pagos as p', 'c.pago_id', '=', 'p.id')
            ->where('c.contrato_id', $id)
            ->select('c.*', 'p.fecha as fecha_pago_cargo', 'p.metodo as metodo_pago_cargo', 'p.nota as nota_pago_cargo', 'p.comprobante as comprobante_pago_cargo')
            ->orderByDesc('c.fecha_aplicado')
            ->paginate(15, ['*'], 'cargosPage');

        $pagos = DB::table('contrato_pagos')
            ->where('contrato_id', $id)
            ->orderByDesc('fecha')->orderByDesc('id')
            ->paginate(15, ['*'], 'pagosPage');
        
        return Inertia::render('Gestion/Honorarios/Contratos/Show', compact(
            'contrato', 'contratoOrigen', 'cliente', 'cuotas', 'pagos', 'cargos',
            'total_cargos_valor', 'total_pagos_valor', 'clientes', 'modalidades' // <-- Añadir 'clientes' y 'modalidades'
        ));
    }

    public function reestructurar($id)
    {
        $contrato = DB::table('contratos')->find($id);
        if (!$contrato) {
            return redirect()->route('honorarios.contratos.index')->with('error', 'Contrato no encontrado.');
        }

        return redirect()->route('honorarios.contratos.create', ['from' => $id]);
    }

    public function pagar($id, Request $request)
    {
        $v = Validator::make($request->all(), [
            'cuota_id'    => ['required','integer'],
            'valor'       => ['required','numeric','min:0.01'],
            'fecha'       => ['required','date'],
            'metodo'      => ['required','in:EFECTIVO,TRANSFERENCIA,TARJETA,OTRO'],
            'nota'        => ['nullable','string','max:1000'],
            'comprobante' => ['nullable','file','mimes:pdf,jpg,jpeg,png,webp','max:5120'],
        ]);
        $v->validate();

        $contrato = DB::table('contratos')->where('id',$id)->first();
        if (!$contrato) abort(404);

        $cuota = DB::table('contrato_cuotas')->where('id',$request->input('cuota_id'))->where('contrato_id',$id)->first();
        if (!$cuota) return back()->withErrors(['cuota_id'=>'Cuota no encontrada.']);

        $valor = round((float)$request->input('valor'), 2);
        if ($valor + 0.01 < (float)$cuota->valor) {
            return back()->withErrors(['valor'=>'Solo se admiten pagos iguales o superiores al valor de la cuota.']);
        }

        $path = null;
        if ($request->hasFile('comprobante')) {
            $path = $request->file('comprobante')->store("comprobantes/pagos/{$id}", 'public');
        }

        DB::transaction(function () use ($id, $cuota, $request, $valor, $path) {
            DB::table('contrato_pagos')->insert([
                'contrato_id' => $id,
                'cuota_id'    => $cuota->id,
                'cargo_id'    => null,
                'valor'       => $valor,
                'fecha'       => $request->input('fecha'),
                'metodo'      => $request->input('metodo'),
                'nota'        => (string)$request->input('nota',''),
                'comprobante' => $path,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::table('contrato_cuotas')->where('id',$cuota->id)->update([
                'estado'     => 'PAGADA',
                'fecha_pago' => $request->input('fecha'),
                'updated_at' => now(),
            ]);

            $this->checkAndCloseContract($id);
        });

        return back()->with('success','Pago registrado.');
    }

    public function resolverLitis($id, Request $request)
    {
        $v = Validator::make($request->all(), [
            'monto_base_litis' => ['required', 'numeric', 'min:0'],
        ]);
        $v->validate();

        $contrato = DB::table('contratos')->where('id', $id)->first();
        if (!$contrato) {
            return back()->with('error', 'El contrato no existe.');
        }

        if ($contrato->modalidad !== 'LITIS' && $contrato->modalidad !== 'CUOTA_MIXTA') {
            return back()->with('error', 'Esta acción solo es válida para contratos de tipo Litis o Cuota Mixta.');
        }

        $monto_base = round((float)$request->input('monto_base_litis'), 2);
        $porcentaje = (float)$contrato->porcentaje_litis;
        $honorarios = round(($monto_base * $porcentaje) / 100, 2);

        DB::transaction(function () use ($id, $contrato, $monto_base, $honorarios) {
            DB::table('contratos')->where('id', $id)->update([
                'monto_base_litis' => $monto_base,
                'updated_at'       => now(),
            ]);

            DB::table('contrato_cargos')->insert([
                'contrato_id'    => $id,
                'tipo'           => 'HONORARIO_LITIS',
                'monto'          => $honorarios,
                'estado'         => 'PENDIENTE',
                'descripcion'    => "Honorarios del {$contrato->porcentaje_litis}% sobre un monto base de $$monto_base.",
                'fecha_aplicado' => now()->toDateString(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });

        return back()->with('success', 'Resultado del caso registrado. Se ha generado el cargo por honorarios.');
    }

    public function pagarCargo($id, Request $request)
    {
        $v = Validator::make($request->all(), [
            'cargo_id'    => ['required','integer'],
            'valor'       => ['required','numeric','min:0.01'],
            'fecha'       => ['required','date'],
            'metodo'      => ['required','in:EFECTIVO,TRANSFERENCIA,TARJETA,OTRO'],
            'nota'        => ['nullable','string','max:1000'],
            'comprobante' => ['required','file','mimes:pdf,jpg,jpeg,png,webp','max:5120'],
        ]);
        $v->validate();

        $contrato = DB::table('contratos')->where('id',$id)->first();
        if (!$contrato) abort(404);

        $cargo = DB::table('contrato_cargos')->where('id',$request->input('cargo_id'))->where('contrato_id',$id)->first();
        if (!$cargo) return back()->withErrors(['cargo_id'=>'Cargo no encontrado.']);

        $valor = round((float)$request->input('valor'), 2);
        if ($valor + 0.01 < (float)$cargo->monto) {
            return back()->withErrors(['valor'=>'El pago debe ser igual o superior al valor del cargo.']);
        }

        $path = $request->file('comprobante')->store("comprobantes/cargos_pagos/{$id}", 'public');

        DB::transaction(function () use ($id, $cargo, $request, $valor, $path) {
            $pagoId = DB::table('contrato_pagos')->insertGetId([
                'contrato_id' => $id,
                'cuota_id'    => null,
                'cargo_id'    => $cargo->id,
                'valor'       => $valor,
                'fecha'       => $request->input('fecha'),
                'metodo'      => $request->input('metodo'),
                'nota'        => (string)$request->input('nota',''),
                'comprobante' => $path,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::table('contrato_cargos')->where('id',$cargo->id)->update([
                'estado'     => 'PAGADO',
                'fecha_pago' => $request->input('fecha'),
                'pago_id'    => $pagoId,
                'updated_at' => now(),
            ]);

            $this->checkAndCloseContract($id);
        });

        return back()->with('success','Pago de cargo registrado.');
    }

    public function agregarCargo($id, Request $request)
    {
        $v = Validator::make($request->all(), [
            'monto'                  => ['required','numeric','min:0.01'],
            'descripcion'            => ['required','string','max:255'],
            'fecha'                  => ['required','date'],
            'comprobante'            => ['nullable','file','mimes:pdf,jpg,jpeg,png,webp','max:5120'],
            'fecha_inicio_intereses' => ['nullable', 'date', 'after_or_equal:fecha'],
        ]);
        $v->validate();

        $contrato = DB::table('contratos')->where('id',$id)->first();
        if (!$contrato) return back()->with('error','El contrato no existe.');

        $path = null;
        if ($request->hasFile('comprobante')) {
            $path = $request->file('comprobante')->store("comprobantes/cargos/{$id}", 'public');
        }

        DB::table('contrato_cargos')->insert([
            'contrato_id'            => $id,
            'tipo'                   => 'GASTO_REEMBOLSABLE',
            'monto'                  => $request->input('monto'),
            'estado'                 => 'PENDIENTE',
            'descripcion'            => $request->input('descripcion'),
            'comprobante'            => $path,
            'fecha_aplicado'         => $request->input('fecha'),
            'fecha_inicio_intereses' => $request->input('fecha_inicio_intereses'),
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        return back()->with('success','Gasto reembolsable añadido.');
    }

    public function activar($id)
    {
        DB::table('contratos')->where('id',$id)->update(['estado'=>'ACTIVO','updated_at'=>now()]);
        return back()->with('success','Contrato activado.');
    }

    public function cerrar($id, Request $request)
    {
        $v = Validator::make($request->all(), [
            'monto'                  => ['nullable','numeric','min:0'],
            'descripcion'            => ['nullable','string','max:255'],
            'fecha_inicio_intereses' => ['nullable', 'date'],
        ]);
        $v->validate();

        DB::transaction(function () use ($id, $request) {
            $monto = $request->input('monto');
            $descripcion = $request->input('descripcion');

            if (!empty($monto) && $monto > 0) {
                DB::table('contrato_cargos')->insert([
                    'contrato_id'            => $id,
                    'tipo'                   => 'CIERRE_ATIPICO',
                    'monto'                  => $monto,
                    'estado'                 => 'PENDIENTE',
                    'descripcion'            => $descripcion ?: 'Cargo por cierre manual.',
                    'fecha_aplicado'         => now()->toDateString(),
                    'fecha_inicio_intereses' => $request->input('fecha_inicio_intereses'),
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);
            }

            $this->checkAndCloseContract($id, true);
        });

        return back()->with('success','Contrato actualizado.');
    }

    public function saldarContrato($id)
    {
        DB::table('contratos')->where('id',$id)->update(['estado'=>'CERRADO','updated_at'=>now()]);
        return back()->with('success','Contrato saldado y cerrado.');
    }

    public function reabrir($id)
    {
        DB::table('contratos')->where('id',$id)->update(['estado'=>'ACTIVO','updated_at'=>now()]);
        return back()->with('success','Contrato reabierto y activado.');
    }

    public function verComprobante($pago_id)
    {
        $pago = DB::table('contrato_pagos')->find($pago_id);
        if (!$pago || !$pago->comprobante) abort(404,'Comprobante no encontrado.');

        if (!Storage::disk('public')->exists($pago->comprobante)) abort(404,'Archivo no encontrado.');

        return Storage::disk('public')->response($pago->comprobante);
    }

    public function verCargoComprobante($cargo_id)
    {
        $cargo = DB::table('contrato_cargos')->find($cargo_id);
        if (!$cargo || !$cargo->comprobante) abort(404,'Comprobante no encontrado.');

        if (!Storage::disk('public')->exists($cargo->comprobante)) abort(404,'Archivo no encontrado.');

        return Storage::disk('public')->response($cargo->comprobante);
    }

    public function pdfContrato($id)
    {
        $contrato = DB::table('contratos')->where('id', $id)->first();
        if (!$contrato) abort(404);

        $cliente = DB::table('personas')
                        ->select('nombre_completo as nombre')
                        ->where('id', $contrato->cliente_id)
                        ->first();

        $cuotas = DB::table('contrato_cuotas')->where('contrato_id', $id)->orderBy('numero')->get();

        $data = compact('contrato','cliente','cuotas');

        if (class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('honorarios.contrato', $data);
            return $pdf->stream("contrato_{$id}.pdf");
        }
        return response()->view('honorarios.contrato', $data);
    }

    public function pdfLiquidacion($id)
    {
        $contrato = DB::table('contratos')->where('id', $id)->first();
        if (!$contrato) abort(404);

        $cliente = DB::table('personas')
                        ->select('nombre_completo as nombre')
                        ->where('id', $contrato->cliente_id)
                        ->first();

        $pagos  = DB::table('contrato_pagos')->where('contrato_id', $id)->orderByDesc('fecha')->get();
        $cuotas = DB::table('contrato_cuotas')->where('contrato_id', $id)->orderBy('numero')->get();

        $data = compact('contrato','cliente','cuotas','pagos');

        if (class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('honorarios.liquidacion', $data);
            return $pdf->stream("liquidacion_{$id}.pdf");
        }
        return response()->view('honorarios.liquidacion', $data);
    }

    private function checkAndCloseContract($contrato_id, $isManualClosure = false)
    {
        $cuotasPendientes = DB::table('contrato_cuotas')->where('contrato_id',$contrato_id)->where('estado','!=','PAGADA')->count();
        $cargosPendientes = DB::table('contrato_cargos')->where('contrato_id',$contrato_id)->where('estado','!=','PAGADO')->count();

        if ($cuotasPendientes === 0 && $cargosPendientes === 0) {
            DB::table('contratos')->where('id',$contrato_id)->update(['estado'=>'CERRADO','updated_at'=>now()]);
        } elseif ($isManualClosure) {
            DB::table('contratos')->where('id',$contrato_id)->update(['estado'=>'PAGOS_PENDIENTES','updated_at'=>now()]);
        }
    }
}
