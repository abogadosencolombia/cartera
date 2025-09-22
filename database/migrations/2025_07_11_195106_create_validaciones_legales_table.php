<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validaciones_legales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caso_id')->constrained('casos')->onDelete('cascade');
            $table->enum('tipo', [
                'poder_vencido', 'tasa_usura', 'sin_pagare',
                'sin_carta_instrucciones', 'sin_certificacion_saldo',
                'tipo_proceso_vs_garantia', 'plazo_excedido_sin_demanda',
                'documento_faltante_para_radicar'
            ]);
            $table->enum('estado', ['cumple', 'incumple', 'no_aplica'])->default('cumple');
            
            // --- NUEVOS CAMPOS ---
            $table->enum('nivel_riesgo', ['bajo', 'medio', 'alto'])->default('medio')->comment('Clasificación del impacto de la falla.');
            $table->text('accion_correctiva')->nullable()->comment('Descripción de cómo se solucionó la falla.');
            $table->timestamp('fecha_cumplimiento')->nullable()->comment('Fecha en que se solucionó la falla.');
            // --- FIN NUEVOS CAMPOS ---

            $table->text('observacion')->nullable();
            $table->timestamp('ultima_revision')->nullable();
            $table->timestamps();

            $table->unique(['caso_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validaciones_legales');
    }
};