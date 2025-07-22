<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Para modificar una columna ENUM, usamos un comando SQL directo.
        // Esto añade 'documento_requerido' a la lista de opciones sin perder datos.
        DB::statement("ALTER TABLE validaciones_legales MODIFY COLUMN tipo ENUM('poder_vencido', 'tasa_usura', 'sin_pagare', 'sin_carta_instrucciones', 'sin_certificacion_saldo', 'tipo_proceso_vs_garantia', 'plazo_excedido_sin_demanda', 'documento_faltante_para_radicar', 'documento_requerido') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Este comando revierte los cambios, eliminando 'documento_requerido' de la lista.
        DB::statement("ALTER TABLE validaciones_legales MODIFY COLUMN tipo ENUM('poder_vencido', 'tasa_usura', 'sin_pagare', 'sin_carta_instrucciones', 'sin_certificacion_saldo', 'tipo_proceso_vs_garantia', 'plazo_excedido_sin_demanda', 'documento_faltante_para_radicar') NOT NULL");
    }
};
