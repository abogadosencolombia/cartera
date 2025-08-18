<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use App\Http\Controllers\Controller;

class GestoresController extends Controller
{
    public function index(Request $request)
    {
        $cfg = config('cartera');

        // Tablas
        $T_USERS = $cfg['users_table'];
        $T_CASES = $cfg['cases_table'];
        $T_COOPS = $cfg['cooperativas_table'];
        $T_RECS  = $cfg['recoveries_table'];

        // PKs
        $PK_U = $cfg['user_pk'];
        $PK_C = $cfg['case_pk'];
        $PK_O = $cfg['cooperativa_pk'];

        // Detección de columnas
        $userNameCol = $this->pickColumn($T_USERS, $cfg['user_name_candidates'], 'name');
        $roleCol     = $cfg['role_column'] && Schema::hasColumn($T_USERS, $cfg['role_column'])
                        ? $cfg['role_column'] : null;

        $caseUserFk  = $this->pickColumn($T_CASES, $cfg['case_user_fk_candidates']);
        $caseNumCol  = $this->pickColumn($T_CASES, $cfg['case_number_candidates'], $cfg['case_pk']);
        $caseCoopFk  = $this->pickColumn($T_CASES, $cfg['case_coop_fk_candidates']);

        $coopNameCol = $this->pickColumn($T_COOPS, $cfg['cooperativa_name_candidates'], $cfg['cooperativa_pk']);

        $recCaseFk   = $this->pickColumn($T_RECS, $cfg['recovery_case_fk_candidates']);
        $recAmtCol   = $this->pickColumn($T_RECS, $cfg['recovery_amount_candidates']);

        // Filtros
        $q    = trim((string) $request->get('q', ''));
        $sort = in_array($request->get('sort'), ['total_recovered','name']) ? $request->get('sort') : 'total_recovered';
        $dir  = strtolower($request->get('dir')) === 'asc' ? 'asc' : 'desc';

        // Base de usuarios-gestores
        $users = DB::table($T_USERS)->select([$PK_U, $userNameCol.' as name']);

        if ($roleCol) {
            $users->whereIn($roleCol, $cfg['role_values_for_agents']);
        } else if (Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
            // Spatie Permission
            $users->join('model_has_roles as mhr', function($j) use ($T_USERS, $PK_U){
                $j->on('mhr.model_id', '=', $T_USERS.'.'.$PK_U)->where('mhr.model_type', '=', 'App\\Models\\User');
            })->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->whereIn('r.name', $cfg['role_values_for_agents'])
            ->addSelect('r.name as role');
        }

        if ($q !== '') {
            $users->where($userNameCol, 'like', "%".$q."%");
        }

        $usersList = $users->get();
        $userIds = $usersList->pluck($PK_U)->all();

        // Subconsulta: recuperado por caso
        $recoveredPerCase = DB::table($T_RECS)
            ->select([$recCaseFk.' as case_id', DB::raw("COALESCE(SUM($recAmtCol),0) as recovered")])
            ->groupBy($recCaseFk);

        // Agregado por usuario
        $totalsByUser = DB::table($T_CASES.' as c')
            ->leftJoinSub($recoveredPerCase, 'r', function($j) use ($PK_C){
                $j->on('r.case_id','=','c.'.$PK_C);
            })
            ->whereIn('c.'.$caseUserFk, $userIds)
            ->groupBy('c.'.$caseUserFk)
            ->select([
                'c.'.$caseUserFk.' as user_id',
                DB::raw('COALESCE(SUM(r.recovered),0) as total_recovered'),
                DB::raw('COUNT(*) as casos_count')
            ])->get()->keyBy('user_id');

        // Detalle de casos por usuario con cooperativa y recuperado por caso
        $casesByUser = DB::table($T_CASES.' as c')
            ->leftJoin($T_COOPS.' as o', 'o.'.$PK_O, '=', 'c.'.$caseCoopFk)
            ->leftJoinSub($recoveredPerCase, 'r', function($j) use ($PK_C){
                $j->on('r.case_id','=','c.'.$PK_C);
            })
            ->whereIn('c.'.$caseUserFk, $userIds)
            ->orderBy('c.'.$PK_C)
            ->select([
                'c.'.$caseUserFk.' as user_id',
                'c.'.$PK_C.' as case_id',
                'c.'.$caseNumCol.' as case_number',
                'o.'.$PK_O.' as coop_id',
                'o.'.$coopNameCol.' as coop_name',
                DB::raw('COALESCE(r.recovered,0) as recovered')
            ])->get()->groupBy('user_id');

        // Construcción final
        $rows = [];
        foreach ($usersList as $u) {
            $total = $totalsByUser[$u->$PK_U]->total_recovered ?? 0;
            $userCases = $casesByUser[$u->$PK_U] ?? collect();

            $coopsUnique = $userCases->pluck('coop_id','coop_id')->keys()->filter()->values();
            $coopsNamed  = $userCases->whereNotNull('coop_id')->map(function($x){
                return ['id'=>$x->coop_id, 'name'=>$x->coop_name];
            })->unique('id')->values()->all();

            $rows[] = [
                'id' => $u->$PK_U,
                'name' => $u->name,
                'total_recovered' => (float) $total,
                'casos_count' => (int) ($totalsByUser[$u->$PK_U]->casos_count ?? 0),
                'cooperativas_count' => count($coopsUnique),
                'cases' => $userCases->map(function($x){
                    return [
                        'id' => $x->case_id,
                        'number' => $x->case_number,
                        'recovered' => (float) $x->recovered,
                        'cooperativa' => $x->coop_id ? ['id'=>$x->coop_id,'name'=>$x->coop_name] : null,
                    ];
                })->values()->all(),
                'cooperativas' => $coopsNamed,
            ];
        }

        // Ordenación en servidor
        if ($sort === 'name') {
            usort($rows, fn($a,$b) => $dir==='asc' ? strcasecmp($a['name'],$b['name']) : strcasecmp($b['name'],$a['name']));
        } else {
            usort($rows, fn($a,$b) => $dir==='asc' ? $a['total_recovered']<=>$b['total_recovered'] : $b['total_recovered']<=>$a['total_recovered']);
        }

        return Inertia::render('Admin/Gestores/Index', [
            'filters' => [
                'q' => $q,
                'sort' => $sort,
                'dir' => $dir,
            ],
            'rows' => array_values($rows),
            'routesBase' => $cfg['frontend_paths'],
        ]);
    }

    private function pickColumn(string $table, array $candidates, ?string $fallback = null): string
    {
        foreach ($candidates as $col) {
            if (Schema::hasColumn($table, $col)) return $col;
        }
        if ($fallback) return $fallback;
        abort(500, "No se encontró columna adecuada en {$table}");
    }
}