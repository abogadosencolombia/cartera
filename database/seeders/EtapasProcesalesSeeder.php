<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EtapaProcesal;

class EtapasProcesalesSeeder extends Seeder
{
    public function run(): void
    {
        $etapas = [
            'Avalúo y Remate',
            'Notificación',
            'Contestación Demanda',
            'Audiencia Inicial',
            'Audiencia de Instrucción y Juzgamiento',
            'Sentencia',
            'Apelación',
            'Ejecución de Sentencia',
            'Liquidación',
            'Terminación',
        ];

        $orden = 1;
        foreach ($etapas as $nombre) {
            EtapaProcesal::firstOrCreate(
                ['nombre' => $nombre],
                ['orden'  => $orden++]
            );
        }
    }
}
