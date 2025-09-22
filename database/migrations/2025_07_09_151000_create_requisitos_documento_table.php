<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('requisitos_documento', function (Blueprint $table) {
            $table->id();

            // La regla puede ser para una cooperativa específica o para todas (si es null).
            $table->foreignId('cooperativa_id')->nullable()->constrained()->onDelete('cascade');

            // El tipo de proceso al que aplica la regla (ej: 'hipotecario').
            $table->string('tipo_proceso');

            // El nombre del tipo de documento que es requerido.
            $table->string('tipo_documento_requerido');
            
            $table->timestamps();

            // Creamos un índice único para evitar reglas duplicadas.
            $table->unique(['cooperativa_id', 'tipo_proceso', 'tipo_documento_requerido'], 'regla_unica_requisito');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisitos_documento');
    }
};
