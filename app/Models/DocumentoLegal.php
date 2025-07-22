<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute; // 1. Importamos Attribute
use Carbon\Carbon; // 2. Importamos Carbon para manejar fechas

class DocumentoLegal extends Model
{
    use HasFactory;

    protected $table = 'documentos_legales';

    protected $fillable = [
        'cooperativa_id',
        'tipo_documento',
        'archivo',
        'fecha_expedicion',
        'fecha_vencimiento'
    ];

    protected $casts = [
        'fecha_expedicion' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    /**
     * ¡NUEVO! Añadimos el atributo 'status' a la data que se envía.
     */
    protected $appends = ['status'];

    /**
     * ¡LA MAGIA!
     * Este es un "Accessor". Define un atributo virtual 'status'.
     * Laravel lo llamará automáticamente cada vez que pidamos el status de un documento.
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function () {
                $fechaVencimiento = $this->fecha_vencimiento;

                // Si no tiene fecha de vencimiento, siempre está vigente.
                if (is_null($fechaVencimiento)) {
                    return 'vigente';
                }

                // Si la fecha de vencimiento ya pasó, está vencido.
                if (Carbon::now()->gt($fechaVencimiento)) {
                    return 'vencido';
                }

                // Si vence en los próximos 30 días, está por vencer.
                if (Carbon::now()->diffInDays($fechaVencimiento) <= 30) {
                    return 'por_vencer';
                }

                // En cualquier otro caso, está vigente.
                return 'vigente';
            }
        );
    }


    public function cooperativa(): BelongsTo
    {
        return $this->belongsTo(Cooperativa::class);
    }
}
