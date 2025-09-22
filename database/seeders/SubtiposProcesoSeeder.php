<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoProceso;
use App\Models\SubtipoProceso;

class SubtiposProcesoSeeder extends Seeder
{
    public function run(): void
    {
        // Asegurar tipos base presentes
        $tipoId = fn(string $n) => TipoProceso::firstOrCreate(['nombre' => $n])->id;

        $map = [
            'EJECUTIVO' => ['HIPOTECARIO','PRENDARIO','SINGULAR','PAGO DIRECTO','GARANTIA REAL','MUEBLE'],
            'INSOLVENCIA' => ['INSOLVENCIA'],
            'LABORAL' => ['LABORAL'],
            'CIVIL' => ['DIVISORIO','CURADURIA','MIXTO'],
            'SUCESIÓN' => ['SUCESIÓN'],
            // Puedes añadir más mapeos aquí cuando lo requieras
        ];

        foreach ($map as $tipoNombre => $subtipos) {
            $tId = $tipoId($tipoNombre);
            foreach ($subtipos as $nombre) {
                SubtipoProceso::firstOrCreate(
                    ['tipo_proceso_id' => $tId, 'nombre' => $nombre],
                    []
                );
            }
        }
    }
}
