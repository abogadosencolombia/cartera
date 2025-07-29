<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ===== ¡ASEGÚRATE DE QUE ESTA LÍNEA EXISTA! =====
// Necesitamos decirle a este archivo que vamos a usar el modelo User.
use App\Models\User;

class PagoCaso extends Model
{
    use HasFactory;

    protected $table = 'pagos_caso';

    protected $fillable = [
        'caso_id',
        'monto_pagado',
        'fecha_pago',
        'motivo_pago',
        'user_id', // <-- Es buena práctica añadir el user_id aquí también
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

    // =======================================================
    // ===== ¡AQUÍ ESTÁ EL MÉTODO QUE SOLUCIONA EL ERROR! =====
    // =======================================================
    /**
     * Obtiene el usuario que registró el pago.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}