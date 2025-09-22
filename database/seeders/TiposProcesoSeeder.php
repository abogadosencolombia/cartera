<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoProceso;

class TiposProcesoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            'EJECUTIVO',
            'CIVIL',
            'LABORAL',
            'FAMILIA',
            'PENAL',
            'ADMINISTRATIVO',
            'CONSTITUCIONAL',
            'INSOLVENCIA',
            'SUCESIÓN',
            'COMERCIAL',
            'OTRO',
        ];

        foreach ($tipos as $nombre) {
            TipoProceso::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
