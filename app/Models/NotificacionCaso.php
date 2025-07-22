<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionCaso extends Model
{
    use HasFactory;

    protected $table = 'notificaciones_caso';

    protected $fillable = [
        'caso_id',
        'user_id',
        'tipo',
        'mensaje',
        'prioridad',
        'leido',
        'fecha_envio',
        'programado_para',
    ];

    protected $casts = [
        'leido' => 'boolean',
        'fecha_envio' => 'datetime',
        'programado_para' => 'datetime',
    ];

    /**
     * Una notificación pertenece a un Caso.
     */
    public function caso(): BelongsTo
    {
        return $this->belongsTo(Caso::class);
    }

    /**
     * Una notificación está dirigida a un Usuario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
