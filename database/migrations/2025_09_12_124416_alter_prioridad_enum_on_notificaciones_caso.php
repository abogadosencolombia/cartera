<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Para cambiar el tipo/enum necesitas doctrine/dbal:
        // composer require doctrine/dbal
        Schema::table('notificaciones_caso', function (Blueprint $t) {
            $t->enum('prioridad', ['baja','media','alta'])->default('media')->change();
        });
    }

    public function down(): void
    {
        Schema::table('notificaciones_caso', function (Blueprint $t) {
            // vuelve a string si lo prefieres
            $t->string('prioridad')->default('media')->change();
        });
    }
};
