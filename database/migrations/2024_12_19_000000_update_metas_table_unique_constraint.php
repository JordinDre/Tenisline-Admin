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
        Schema::table('metas', function (Blueprint $table) {
            // Eliminar la restricción única anterior
            $table->dropUnique(['user_id', 'mes', 'anio']);

            // Hacer user_id nullable
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Agregar la nueva restricción única solo por bodega, mes y año
            $table->unique(['bodega_id', 'mes', 'anio'], 'metas_bodega_mes_anio_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metas', function (Blueprint $table) {
            // Eliminar la nueva restricción única
            $table->dropUnique('metas_bodega_mes_anio_unique');

            // Hacer user_id no nullable
            $table->unsignedBigInteger('user_id')->nullable(false)->change();

            // Restaurar la restricción única anterior
            $table->unique(['user_id', 'mes', 'anio']);
        });
    }
};
