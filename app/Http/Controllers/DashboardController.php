<?php

namespace App\Http\Controllers;

use App\Models\Caso;
use App\Models\Cooperativa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Muestra el dashboard principal con estadísticas relevantes para el usuario.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $stats = [];

        if ($user->tipo_usuario === 'admin') {
            // Estadísticas para el Administrador (visión global)
            $stats = [
                'totalCooperativas' => Cooperativa::count(),
                'totalUsuarios' => User::count(),
                'totalCasos' => Caso::count(),
                'casosPorEstado' => Caso::query()
                    ->select('estado_proceso', DB::raw('count(*) as total'))
                    ->groupBy('estado_proceso')
                    ->get(),
            ];
        } elseif (in_array($user->tipo_usuario, ['gestor', 'abogado'])) {
            // Estadísticas para Gestor/Abogado (visión de sus cooperativas)
            $cooperativaIds = $user->cooperativas->pluck('id');
            $stats = [
                'totalCooperativas' => $cooperativaIds->count(),
                'totalCasos' => Caso::whereIn('cooperativa_id', $cooperativaIds)->count(),
                'casosPorEstado' => Caso::whereIn('cooperativa_id', $cooperativaIds)
                    ->select('estado_proceso', DB::raw('count(*) as total'))
                    ->groupBy('estado_proceso')
                    ->get(),
            ];
        } elseif ($user->tipo_usuario === 'cli' && $user->persona_id) {
            // Estadísticas para el Cliente (visión de sus casos)
            $query = Caso::query()
                ->where('deudor_id', $user->persona_id)
                ->orWhere('codeudor1_id', $user->persona_id)
                ->orWhere('codeudor2_id', $user->persona_id);
            
            $stats = [
                'totalCasos' => $query->count(),
                'montoTotal' => $query->sum('monto_total'),
                'casosPorEstado' => (clone $query)
                    ->select('estado_proceso', DB::raw('count(*) as total'))
                    ->groupBy('estado_proceso')
                    ->get(),
            ];
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats
        ]);
    }
}
