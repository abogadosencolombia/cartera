<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persona extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_completo',
        'tipo_documento',
        'numero_documento',
        'telefono_fijo',
        'celular_1',
        'celular_2',
        'correo_1',
        'correo_2',
        'direccion',
        'ciudad',
        'empresa',
        'cargo',
        'observaciones',
        'social_links',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    public function casosComoDeudor(): HasMany
    {
        return $this->hasMany(Caso::class, 'deudor_id');
    }
}
