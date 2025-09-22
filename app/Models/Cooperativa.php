<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cooperativa extends Model
{
    use HasFactory;

    // --- SINCRONIZACIÓN FINAL ---
    // La lista ahora coincide 100% con las columnas de su base de datos.
    protected $fillable = [
        'nombre',
        'NIT', // Corregido a mayúsculas para coincidir con la base de datos
        'tipo_vigilancia',
        'fecha_constitucion',
        'numero_matricula_mercantil',
        'tipo_persona',
        'representante_legal_nombre',
        'representante_legal_cedula',
        'contacto_nombre',
        'contacto_telefono',
        'contacto_correo',
        'correo_notificaciones_judiciales',
        'usa_libranza',
        'requiere_carta_instrucciones',
        'tipo_garantia_frecuente',
        'tasa_maxima_moratoria',
        'ciudad_principal_operacion',
        'estado_activo',
    ];

    protected $casts = [
        'configuraciones_adicionales' => 'array',
        'usa_libranza' => 'boolean',
        'requiere_carta_instrucciones' => 'boolean',
        'estado_activo' => 'boolean',
    ];

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoLegal::class);
    }

    public function getConfiguracion($clave, $default = null)
    {
        return $this->configuraciones_adicionales[$clave] ?? $default;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'cooperativa_user');
    }

    // --- AGREGA ESTA FUNCIÓN COMPLETA ---
    /**
     * Obtiene la configuración legal asociada a la cooperativa.
     */
    public function configuracionLegal(): HasOne
    {
        return $this->hasOne(ConfiguracionLegal::class);
    }
}
