<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nota: Para modificar columnas, necesitas tener instalado el paquete 'doctrine/dbal'.
        // Ejecuta: composer require doctrine/dbal
        Schema::table('contratos', function (Blueprint $table) {
            
            // 1. Permitir que el monto total sea nulo
            $table->decimal('monto_total', 14, 2)->default(null)->nullable()->change();

            // 2. Cambiar la columna modalidad para incluir los nuevos tipos
            $table->enum('modalidad', ['CUOTAS', 'PAGO_UNICO', 'LITIS', 'CUOTA_MIXTA'])
                  ->default('CUOTAS')
                  ->change();

            // 3. Añadir nuevas columnas para Litis y Cuota Mixta
            $table->decimal('porcentaje_litis', 5, 2)->nullable()->after('anticipo');
            $table->decimal('monto_base_litis', 14, 2)->nullable()->after('porcentaje_litis');
        });
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            // Revertir los cambios en el orden inverso
            $table->dropColumn(['porcentaje_litis', 'monto_base_litis']);

            $table->string('modalidad')->default('CUOTAS')->change();
            
            $table->decimal('monto_total', 14, 2)->default(0)->nullable(false)->change();
        });
    }
};