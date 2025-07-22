<?php

namespace App\Http\Controllers;

use App\Models\Cooperativa;
use App\Models\DocumentoLegal;
use App\Http\Requests\StoreDocumentoLegalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // <-- 1. Importamos Storage

class DocumentoLegalController extends Controller
{
    public function store(StoreDocumentoLegalRequest $request, Cooperativa $cooperativa): RedirectResponse
    {
        $path = $request->file('archivo')->store('documentos_legales', 'public');

        $cooperativa->documentos()->create([
            'tipo_documento' => $request->tipo_documento,
            'fecha_expedicion' => $request->fecha_expedicion,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'archivo' => $path,
        ]);

        return to_route('cooperativas.show', $cooperativa)
            ->with('success', '¡Documento subido exitosamente!');
    }

    /**
     * ¡NUEVO! Remove the specified resource from storage.
     */
    public function destroy(DocumentoLegal $documento): RedirectResponse
    {
        // 2. Nos aseguramos de que el archivo exista y lo borramos del disco.
        Storage::disk('public')->delete($documento->archivo);

        // 3. Eliminamos el registro de la base de datos.
        $documento->delete();

        // 4. Redirigimos de vuelta a la ficha con un mensaje de éxito.
        return to_route('cooperativas.show', $documento->cooperativa_id)
            ->with('success', '¡Documento eliminado exitosamente!');
    }
}
