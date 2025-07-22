<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí es donde registramos las rutas API para nuestra aplicación.
| Laravel automáticamente les añade el prefijo "/api/" a todas estas rutas.
|
*/

// Esta ruta simula el endpoint de Supersolidaria para validar una cooperativa por su NIT.
// La URL final será: /api/simulador/supersolidaria/validar/{nit}
Route::get('/simulador/supersolidaria/validar/{nit}', function ($nit) {
    
    // Creamos una pequeña base de datos falsa de cooperativas para nuestras pruebas.
    $cooperativasSimuladas = [
        '900123456-7' => ['nombre' => 'Cooperativa El Futuro', 'estado' => 'Activa', 'fecha_registro' => '2010-05-20'],
        '800987654-3' => ['nombre' => 'CoopProgreso', 'estado' => 'En Liquidación', 'fecha_registro' => '2005-11-15'],
        '123456789-0' => ['nombre' => 'Cooperativa Fantasma', 'estado' => 'Cancelada', 'fecha_registro' => '2001-01-10'],
    ];

    // Verificamos si el NIT que nos pasaron existe en nuestra lista.
    if (array_key_exists($nit, $cooperativasSimuladas)) {
        // Si existe, devolvemos los datos de esa cooperativa en formato JSON.
        return response()->json($cooperativasSimuladas[$nit]);
    } else {
        // Si no existe, devolvemos un error 404 (No Encontrado) con un mensaje JSON.
        return response()->json(['error' => 'Cooperativa no encontrada en los registros de Supersolidaria.'], 404);
    }
});
