<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\IntegracionToken;

class IntegracionTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creamos un registro de prueba para nuestro simulador.
        IntegracionToken::create([
            'proveedor' => 'Supersolidaria (Simulador)',
            'api_key' => 'ESTA_ES_UNA_API_KEY_DE_PRUEBA_12345', // Inventamos una API Key
            'activo' => true,
        ]);

        // Aquí podrías añadir en el futuro las credenciales para CIFIN, WhatsApp, etc.
        /*
        IntegracionToken::create([
            'proveedor' => 'CIFIN',
            'client_id' => '...',
            'client_secret' => '...',
            'activo' => false, // Lo dejamos inactivo hasta que esté listo
        ]);
        */
    }
}