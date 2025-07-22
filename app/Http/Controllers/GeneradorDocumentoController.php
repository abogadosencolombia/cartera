<?php

namespace App\Http\Controllers;

use App\Models\Caso;
use App\Models\DocumentoGenerado;
use App\Models\PlantillaDocumento;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Http\RedirectResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Events\DocumentoGeneradoDescargado;
use PhpOffice\PhpWord\IOFactory;

class GeneradorDocumentoController extends Controller
{
    public function generar(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'plantilla_id' => 'required|exists:plantillas_documento,id',
            'caso_id' => 'required|exists:casos,id',
            'es_confidencial' => 'required|boolean',
            'observaciones' => 'nullable|string|max:2000',
        ]);

        try {
            $plantilla = PlantillaDocumento::findOrFail($validatedData['plantilla_id']);
            $caso = Caso::with(['deudor', 'cooperativa'])->findOrFail($validatedData['caso_id']);
            $user = Auth::user();

            if (!$caso->cooperativa || !$caso->deudor) {
                throw new Exception("El caso #{$caso->id} no tiene una cooperativa o un deudor asociado.");
            }

            // --- SINCRONIZACIÓN FINAL ---
            $datosParaPlantilla = [
                'deudor_nombre_completo' => $caso->deudor->nombre_completo ?? 'N/A',
                'deudor_tipo_documento' => $caso->deudor->tipo_documento ?? 'N/A',
                'deudor_numero_documento' => $caso->deudor->numero_documento ?? 'N/A',
                'deudor_direccion' => $caso->deudor->direccion ?? 'N/A',
                'deudor_ciudad' => $caso->deudor->ciudad ?? 'N/A',
                'deudor_email' => $caso->deudor->correo_1 ?? 'N/A',
                'cooperativa_nombre' => $caso->cooperativa->nombre ?? 'N/A',
                'cooperativa_nit' => $caso->cooperativa->NIT ?? 'NIT no registrado', // Corregido a mayúsculas
                'caso_monto_total' => number_format($caso->monto_total, 2, ',', '.'),
                'caso_tipo_proceso' => $caso->tipo_proceso ?? 'N/A',
                'fecha_larga' => now()->isoFormat('LL'),
                'fecha_corta' => now()->format('d/m/Y'),
                'usuario_nombre' => $user->name,
                'observaciones_generacion' => $validatedData['observaciones'] ?? '',
            ];

            $nombreBase = "{$caso->id}_{$plantilla->id}_" . time();
            $rutaDirectorioDestino = "private/documentos_generados/caso_{$caso->id}";
            Storage::disk('local')->makeDirectory($rutaDirectorioDestino);

            $rutaDocx = $this->generarDocx($plantilla, $datosParaPlantilla, $rutaDirectorioDestino, $nombreBase);
            $rutaPdf = $this->generarPdf($datosParaPlantilla, $rutaDirectorioDestino, $nombreBase);

            DocumentoGenerado::create([
                'caso_id' => $caso->id,
                'plantilla_documento_id' => $plantilla->id,
                'user_id' => $user->id,
                'nombre_base' => $nombreBase,
                'ruta_archivo_docx' => $rutaDocx,
                'ruta_archivo_pdf' => $rutaPdf,
                'version_plantilla' => $plantilla->version,
                'observaciones' => $validatedData['observaciones'],
                'es_confidencial' => $validatedData['es_confidencial'],
                'metadatos' => ['ip_origen' => $request->ip()]
            ]);

            return back()->with('success', '¡Documentos DOCX y PDF generados con éxito!');

        } catch (Exception $e) {
            report($e);
            return back()->with('error', 'Error inesperado al generar el documento: ' . $e->getMessage());
        }
    }

    private function generarDocx($plantilla, $datos, $directorio, $nombreBase): string
    {
        if (!$plantilla->archivo || !Storage::disk('local')->exists($plantilla->archivo)) {
            throw new Exception("Archivo de plantilla DOCX no encontrado.");
        }
        $templateProcessor = new TemplateProcessor(Storage::disk('local')->path($plantilla->archivo));
        $templateProcessor->setValues($datos);
        $rutaCompleta = "{$directorio}/{$nombreBase}.docx";
        $templateProcessor->saveAs(Storage::disk('local')->path($rutaCompleta));
        return $rutaCompleta;
    }

    private function generarPdf($datos, $directorio, $nombreBase): string
    {
        $pdf = Pdf::loadView('pdf.documento_generado', ['datos' => $datos]);
        $rutaCompleta = "{$directorio}/{$nombreBase}.pdf";
        Storage::disk('local')->put($rutaCompleta, $pdf->output());
        return $rutaCompleta;
    }

    public function descargarDocx(Request $request, DocumentoGenerado $documento)
    {
        $this->verificarAcceso($documento);
        if (empty($documento->ruta_archivo_docx) || !Storage::disk('local')->exists($documento->ruta_archivo_docx)) {
            return back()->with('error', 'El archivo DOCX físico no se encuentra en el servidor.');
        }
        DocumentoGeneradoDescargado::dispatch(Auth::user(), $documento, $request);
        return Storage::disk('local')->download($documento->ruta_archivo_docx, $documento->nombre_base . '.docx');
    }

    public function descargarPdf(Request $request, DocumentoGenerado $documento)
    {
        $this->verificarAcceso($documento);
        if (empty($documento->ruta_archivo_pdf) || !Storage::disk('local')->exists($documento->ruta_archivo_pdf)) {
            return back()->with('error', 'El archivo PDF físico no se encuentra en el servidor.');
        }
        DocumentoGeneradoDescargado::dispatch(Auth::user(), $documento, $request);
        return Storage::disk('local')->download($documento->ruta_archivo_pdf, $documento->nombre_base . '.pdf');
    }

    private function verificarAcceso(DocumentoGenerado $documento)
    {
        if ($documento->es_confidencial && Auth::user()->tipo_usuario === 'cli') {
            abort(403, 'Acceso denegado a este documento.');
        }
    }
}
