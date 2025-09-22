<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\AuditoriaEvento;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Caso extends Model
{
    use HasFactory;

    protected $fillable = [
        'cooperativa_id', 'user_id', 'deudor_id', 'codeudor1_id', 'codeudor2_id',
        'referencia_credito', 'tipo_proceso', 'estado_proceso', 'tipo_garantia_asociada',
        'fecha_apertura', 'fecha_vencimiento', 'monto_total', 'tasa_interes_corriente',
        'tasa_moratoria', 'origen_documental', 'bloqueado', 'motivo_bloqueo',
        'etapa_actual', 'medio_contacto', 'ultima_actividad', 'notas_legales',
        'clonado_de_id',
        // --- CAMPOS NUEVOS AÑADIDOS ---
        'subtipo_proceso',
        'etapa_procesal',
        'juzgado_id', 'especialidad_id',
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_vencimiento' => 'date',
        'monto_total' => 'decimal:2',
        'tasa_interes_corriente' => 'decimal:2',
        'tasa_moratoria' => 'decimal:2',
        'bloqueado' => 'boolean',
        'ultima_actividad' => 'datetime',
    ];

    protected $appends = ['semaforo', 'dias_en_mora'];

    protected function diasEnMora(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->fecha_vencimiento) {
                    return 0;
                }
                $vencimiento = Carbon::parse($this->fecha_vencimiento)->startOfDay();
                $hoy = Carbon::today();

                if ($hoy->gt($vencimiento)) {
                    return $hoy->diffInDays($vencimiento);
                }
                
                return 0;
            }
        );
    }

    protected function semaforo(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->estado_proceso === 'cerrado') {
                    return (object) ['text' => 'Cerrado', 'color' => 'gray'];
                }

                if ($this->dias_en_mora > 0) {
                    return (object) ['text' => 'En Mora', 'color' => 'red'];
                }
                
                if ($this->fecha_vencimiento) {
                    $vencimiento = Carbon::parse($this->fecha_vencimiento)->startOfDay();
                    $hoy = Carbon::today();
                    $limitePorVencer = (clone $hoy)->addDays(30);

                    if ($vencimiento->isBetween($hoy, $limitePorVencer)) {
                        return (object) ['text' => 'Por Vencer', 'color' => 'yellow'];
                    }
                }

                switch ($this->estado_proceso) {
                    case 'prejurídico': return (object) ['text' => 'Prejurídico', 'color' => 'blue'];
                    case 'demandado': return (object) ['text' => 'Demandado', 'color' => 'yellow'];
                    case 'en ejecución': return (object) ['text' => 'En Ejecución', 'color' => 'orange'];
                    case 'sentencia': return (object) ['text' => 'Sentencia', 'color' => 'green'];
                    default: return (object) ['text' => 'Activo', 'color' => 'green'];
                }
            }
        );
    }

    // --- RELACIONES ---
    public function cooperativa(): BelongsTo { return $this->belongsTo(Cooperativa::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function deudor(): BelongsTo { return $this->belongsTo(Persona::class, 'deudor_id'); }
    public function codeudor1(): BelongsTo { return $this->belongsTo(Persona::class, 'codeudor1_id');}
    public function codeudor2(): BelongsTo { return $this->belongsTo(Persona::class, 'codeudor2_id');}
    public function documentos(): HasMany { return $this->hasMany(DocumentoCaso::class); }
    public function bitacoras(): HasMany { return $this->hasMany(BitacoraCaso::class)->orderBy('created_at', 'desc'); }
    public function pagos(): HasMany { return $this->hasMany(PagoCaso::class); }
    public function alertas(): HasMany { return $this->hasMany(AlertaCaso::class); }
    public function historialMora(): HasMany { return $this->hasMany(HistorialMora::class); }
    public function documentosGenerados(): HasMany { return $this->hasMany(DocumentoGenerado::class)->orderBy('created_at', 'desc'); }
    public function notificaciones(): HasMany { return $this->hasMany(NotificacionCaso::class); }
    public function validacionesLegales(): HasMany { return $this->hasMany(ValidacionLegal::class); }
    public function configuracionLegal(): HasOne { return $this->hasOne(ConfiguracionLegal::class); }

    public function auditoria(): MorphMany
    {
        return $this->morphMany(AuditoriaEvento::class, 'auditable')->latest();
    }

    /**
     * Relación con el juzgado.
     * Siguiendo la convención de Laravel, la nombramos 'juzgado' para que coincida con 'juzgado_id'.
     */
    public function juzgado(): BelongsTo 
    { 
        return $this->belongsTo(Juzgado::class, 'juzgado_id'); 
    }

    public function especialidad()
    {
        return $this->belongsTo(\App\Models\EspecialidadJuridica::class, 'especialidad_id');
    }


}
