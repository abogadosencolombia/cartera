<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitoDocumento extends Model
{
    use HasFactory;

    // Le decimos a Laravel que el nombre de nuestra tabla es 'requisitos_documento'
    protected $table = 'requisitos_documento';

    /**
     * Los atributos que se pueden asignar de forma masiva.
     */
    protected $fillable = [
        'cooperativa_id',
        'tipo_proceso',
        'tipo_documento_requerido',
    ];

    /**
     * Un requisito puede pertenecer a una Cooperativa.
     */
    public function cooperativa(): BelongsTo
    {
        return $this->belongsTo(Cooperativa::class);
    }
}
