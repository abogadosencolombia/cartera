<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoProceso extends Model
{
    use HasFactory;

    protected $table = 'tipos_proceso';
    protected $fillable = ['nombre','descripcion'];

    public function subtipos()
    {
        return $this->hasMany(SubtipoProceso::class, 'tipo_proceso_id');
    }
}
