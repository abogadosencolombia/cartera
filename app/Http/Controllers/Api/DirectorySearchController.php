<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Persona;
use Illuminate\Support\Facades\DB;

class DirectorySearchController extends Controller
{
    /**
     * Busca en TODOS los usuarios activos.
     * Se elimina el filtro restrictivo de 'abogado' o 'gestor'
     * para asegurar que siempre se puedan encontrar y seleccionar a los responsables,
     * sin importar su rol.
     */
    public function usuariosAbogadosYGestores(Request $request)
    {
        $q = trim($request->get('q', ''));
        $limit = (int) $request->get('limit', 15);

        $users = User::query()
            // ->whereIn('tipo_usuario', ['abogado', 'gestor']) // <-- SE ELIMINA ESTA LÍNEA RESTRICTIVA
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email']);

        return $users->map(fn($user) => [
            'id'    => $user->id,
            'label' => "{$user->name} ({$user->email})",
        ]);
    }

    /**
     * Personas (demandante / demandado)
     */
    public function personas(Request $request)
    {
        $q = trim($request->get('q', ''));
        $limit = (int) $request->get('limit', 15);

        $items = Persona::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nombre_completo', 'like', "%{$q}%")
                      ->orWhere('numero_documento', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre_completo')
            ->limit($limit)
            ->get(['id','nombre_completo','numero_documento','ciudad']);

        return $items->map(function ($persona) {
            $doc = $persona->numero_documento ?: 's/doc';
            $ciudad = $persona->ciudad ?: 's/ciudad';
            return [
                'id'    => $persona->id,
                'label' => "{$persona->nombre_completo} ({$doc} • {$ciudad})",
            ];
        });
    }

    /**
     * Tipos de proceso
     */
    public function tiposProceso(Request $request)
    {
        $q = trim($request->get('q', ''));
        $limit = (int) $request->get('limit', 20);

        $items = DB::table('tipos_proceso')
            ->when($q !== '', fn($query) => $query->where('nombre', 'like', "%{$q}%"))
            ->orderBy('nombre')
            ->limit($limit)
            ->get(['id','nombre']);

        return $items->map(fn($tipo) => [
            'id'    => $tipo->id,
            'label' => $tipo->nombre,
        ]);
    }
}

