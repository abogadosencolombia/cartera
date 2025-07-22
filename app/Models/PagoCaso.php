<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoCaso extends Model
{
    use HasFactory;

    protected $table = 'pagos_caso';

    protected $fillable = [
        'caso_id',
        'monto_pagado',
        'fecha_pago',
        'motivo_pago',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto_pagado' => 'decimal:2',
    ];

    /**
     * Un pago pertenece a un único Caso.
     */
    public function caso(): BelongsTo
    {
        return $this->belongsTo(Caso::class);
    }
}
