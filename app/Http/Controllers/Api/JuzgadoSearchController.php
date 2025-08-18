<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Juzgado;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JuzgadoSearchController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $searchTerm = $request->query('q', '');

        // Solo buscar si el usuario ha escrito al menos 3 caracteres
        if (strlen($searchTerm) < 3) {
            return response()->json([]);
        }

        $juzgados = Juzgado::where('nombre', 'LIKE', '%' . $searchTerm . '%')
            ->limit(50) // Limitar los resultados a 50 para no sobrecargar
            ->get(['id', 'nombre']);

        return response()->json($juzgados);
    }
}
