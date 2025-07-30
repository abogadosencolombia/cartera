<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// --- CONTROLADORES PRINCIPALES ---
use App\Http\Controllers\AnaliticaController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CasoController;
use App\Http\Controllers\CooperativaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoCasoController;
use App\Http\Controllers\DocumentoGeneradoController;
use App\Http\Controllers\GeneradorDocumentoController;
use App\Http\Controllers\IntegracionController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PagoCasoController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\PlantillaDocumentoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReporteCumplimientoController;
use App\Http\Controllers\RequisitoDocumentoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ValidacionLegalController;
use App\Http\Controllers\Admin\ReglaAlertaController;

// --- CONTROLADORES DE MÓDULOS ---
use App\Http\Controllers\Juridico\ArchivoIncidenteController;
use App\Http\Controllers\Juridico\DecisionComiteEticaController;
use App\Http\Controllers\Juridico\IncidenteJuridicoController;
use App\Http\Controllers\Juridico\IndicadoresController;
use App\Http\Controllers\Juridico\TicketDisciplinarioController;
use App\Services\IntegrationService;
use App\Http\Controllers\DocumentoLegalController;
use App\Http\Controllers\Admin\IntegracionTokenController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');


// =================================================================
// ===== INICIO DE RUTAS DE PRUEBA Y SIMULACIÓN (MÓDULO 11) =====
// =================================================================

// RUTA DEL SIMULADOR: Esta ruta finge ser la API de Supersolidaria.
Route::get('/api/simulador/supersolidaria/validar/{nit}', function ($nit) {
    $cooperativasSimuladas = [
        '900123456-7' => ['nombre' => 'Cooperativa El Futuro', 'estado' => 'Activa', 'fecha_registro' => '2010-05-20'],
        '800987654-3' => ['nombre' => 'CoopProgreso', 'estado' => 'En Liquidación', 'fecha_registro' => '2005-11-15'],
        '123456789-0' => ['nombre' => 'Cooperativa Fantasma', 'estado' => 'Cancelada', 'fecha_registro' => '2001-01-10'],
    ];
    if (array_key_exists($nit, $cooperativasSimuladas)) {
        return response()->json($cooperativasSimuladas[$nit]);
    } else {
        return response()->json(['error' => 'Cooperativa no encontrada en los registros de Supersolidaria.'], 404);
    }
});

// RUTA DE PRUEBA: Para ejecutar el ciclo completo de integración.
Route::get('/probar-integracion-supersolidaria', function (IntegrationService $integrationService) {
    $nitDePrueba = '800987654-3';
    $urlDelSimulador = url('/api/simulador/supersolidaria/validar/' . $nitDePrueba);
    
    echo "<h1>PASO 1: Preparando la llamada...</h1>";
    echo "<b>Servicio a llamar:</b> Supersolidaria (Simulador)<br>";
    echo "<b>URL a la que apuntamos:</b> " . $urlDelSimulador;
    echo "<hr>";
    
    $respuesta = $integrationService->ejecutar('Supersolidaria (Simulador)', 'get', $urlDelSimulador);
    
    echo "<h1>PASO 2: Respuesta recibida...</h1>";
    echo "<pre>";
    print_r($respuesta);
    echo "</pre>";
    echo "<hr>";
    
    echo "<h1>PASO 3: ¡Revisa la base de datos!</h1>";
    echo "<h2>Abre tu gestor de base de datos y mira la tabla 'integracion_externa_logs'. ¡Deberías ver un nuevo registro!</h2>";
});

// =================================================================
// ===== FIN DE RUTAS DE PRUEBA Y SIMULACIÓN =======================
// =================================================================

Route::middleware(['auth', 'verified'])->group(function () {

    // --- RUTA DEL DASHBOARD ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- RUTAS GENERALES DEL USUARIO (PERFIL Y NOTIFICACIONES) ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // 👇 ESTA ES LA RUTA PARA LAS PREFERENCIAS. ESTÁ CORRECTA. 👇
    Route::patch('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences.update');
    
    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::patch('/notificaciones/{notificacion}/leer', [NotificacionController::class, 'marcarComoLeida'])->name('notificaciones.leer');
    Route::patch('/notificaciones/{notificacion}/atender', [NotificacionController::class, 'marcarComoAtendida'])->name('notificaciones.atender');
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar');
    Route::get('/reportes/cumplimiento', ReporteCumplimientoController::class)->name('reportes.cumplimiento');
    Route::get('/reportes/cumplimiento/exportar', [ReporteController::class, 'exportarCumplimiento'])->name('reportes.cumplimiento.exportar');
    Route::put('/validaciones-legales/{validacion}/corregir', ValidacionLegalController::class)->name('validaciones.corregir');

    // --- RUTAS PARA GESTIÓN DE DOCUMENTOS LEGALES DE COOPERATIVAS ---
    Route::post('cooperativas/{cooperativa}/documentos', [DocumentoLegalController::class, 'store'])->name('cooperativas.documentos.store');
    Route::get('documentos-legales/{documento}', [DocumentoLegalController::class, 'show'])->name('documentos-legales.show');
    // ===== ¡AQUÍ ESTÁ LA CORRECCIÓN! =====
    // Cambiamos el nombre para que coincida con lo que el frontend busca.
    Route::delete('documentos-legales/{documento}', [DocumentoLegalController::class, 'destroy'])->name('documentos.destroy');


    // --- RUTAS PARA GESTORES Y ABOGADOS (Y ADMINS) ---
    Route::middleware('role:admin,gestor,abogado')->group(function() {
        Route::resource('casos', CasoController::class);
        Route::resource('personas', PersonaController::class);
        Route::resource('cooperativas', CooperativaController::class);
        Route::resource('plantillas', PlantillaDocumentoController::class)->except(['show', 'edit', 'update']);
        Route::post('/plantillas/{plantilla}/clonar', [PlantillaDocumentoController::class, 'clonar'])->name('plantillas.clonar');
        Route::get('casos/{caso}/clonar', [CasoController::class, 'clonar'])->name('casos.clonar');
        Route::post('casos/{caso}/documentos', [DocumentoCasoController::class, 'store'])->name('casos.documentos.store');
        Route::get('documentos-caso/{documento}/view', [DocumentoCasoController::class, 'view'])->name('documentos-caso.view');
        Route::delete('documentos-caso/{documento}', [DocumentoCasoController::class, 'destroy'])->name('documentos-caso.destroy');
        Route::post('/casos/{caso}/pagos', [PagoCasoController::class, 'store'])->name('casos.pagos.store');
        Route::post('/casos/{caso}/notificaciones', [NotificacionController::class, 'storeManual'])->name('casos.notificaciones.store');
        Route::get('/documentos-generados', [DocumentoGeneradoController::class, 'index'])->name('documentos-generados.index');
        Route::post('/documentos/generar', [GeneradorDocumentoController::class, 'generar'])->name('documentos.generar');
        Route::get('/documentos/{documento}/descargar-docx', [GeneradorDocumentoController::class, 'descargarDocx'])->name('documentos.descargar.docx');
        Route::get('/documentos/{documento}/descargar-pdf', [GeneradorDocumentoController::class, 'descargarPdf'])->name('documentos.descargar.pdf');
    });

    // --- ESTRUCTURA DE RUTAS DE ADMINISTRACIÓN (CON PREFIJO /admin) ---
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('tokens', IntegracionTokenController::class);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::resource('reglas-alerta', ReglaAlertaController::class)->only(['index', 'store', 'destroy']);
        Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
        Route::get('/analitica', AnaliticaController::class)->name('analitica.index');
        Route::get('juridico/indicadores', IndicadoresController::class)->name('juridico.indicadores');
        Route::get('incidentes-juridicos/exportar', [IncidenteJuridicoController::class, 'export'])->name('incidentes-juridicos.exportar');
        Route::resource('incidentes-juridicos', IncidenteJuridicoController::class)->parameters(['incidentes-juridicos' => 'incidente']);
        Route::post('incidentes-juridicos/{incidente}/tickets', [TicketDisciplinarioController::class, 'store'])->name('incidentes-juridicos.tickets.store');
        Route::post('incidentes-juridicos/{incidente}/archivos', [ArchivoIncidenteController::class, 'store'])->name('incidentes-juridicos.archivos.store');
        Route::get('archivos-incidente/{archivo}/descargar', [ArchivoIncidenteController::class, 'descargar'])->name('archivos-incidente.descargar');
        Route::post('tickets-disciplinarios/{ticket}/decision', [DecisionComiteEticaController::class, 'store'])->name('tickets-disciplinarios.decision.store');
    });

    // --- RUTAS DE ADMIN SIN PREFIJO ---
    Route::middleware(['role:admin'])->group(function() {
        Route::get('requisitos', [RequisitoDocumentoController::class, 'index'])->name('requisitos.index');
        Route::post('requisitos', [RequisitoDocumentoController::class, 'store'])->name('requisitos.store');
        Route::delete('requisitos/{requisito}', [RequisitoDocumentoController::class, 'destroy'])->name('requisitos.destroy');
        
        // Rutas del Módulo 11
        Route::get('integraciones', [IntegracionController::class, 'index'])->name('integraciones.index');
        Route::get('integraciones/exportar', [IntegracionController::class, 'export'])->name('integraciones.exportar');
    });

});

require __DIR__.'/auth.php';
