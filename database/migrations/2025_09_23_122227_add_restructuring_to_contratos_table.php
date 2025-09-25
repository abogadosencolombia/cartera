<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Añade una nueva columna para registrar el origen de la reestructuración.
        // Esto crea un enlace entre el nuevo contrato y el antiguo.
        Schema::table('contratos', function (Blueprint $table) {
            $table->foreignId('contrato_origen_id')->nullable()->constrained('contratos')->nullOnDelete()->after('id');
        });

        // Modifica la columna 'estado' para incluir el nuevo estado 'REESTRUCTURADO'.
        // Es importante listar todos los estados existentes más el nuevo.
        DB::statement("ALTER TABLE contratos MODIFY COLUMN estado ENUM('ACTIVO', 'PAGOS_PENDIENTES', 'EN_MORA', 'REESTRUCTURADO', 'CERRADO') NOT NULL DEFAULT 'ACTIVO'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropForeign(['contrato_origen_id']);
            $table->dropColumn('contrato_origen_id');
        });

        // Revierte la columna 'estado' a su definición original sin 'REESTRUCTURADO'.
        DB::statement("ALTER TABLE contratos MODIFY COLUMN estado ENUM('ACTIVO', 'PAGOS_PENDIENTES', 'EN_MORA', 'CERRADO') NOT NULL DEFAULT 'ACTIVO'");
    }
};

