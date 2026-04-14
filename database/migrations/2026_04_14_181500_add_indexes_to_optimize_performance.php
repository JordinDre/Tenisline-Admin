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
        Schema::table('productos', function (Blueprint $table) {
            $table->index('marchamo');
            $table->index('color');
            $table->index('talla');
            $table->index('genero');
            $table->index(['marca_id', 'deleted_at']); // Common filter
            $table->index(['proveedor_id', 'deleted_at']);
        });

        Schema::table('ordens', function (Blueprint $table) {
            $table->index('estado');
            $table->index('tipo_pago_id');
            $table->index('asesor_id');
            $table->index('bodega_id');
            $table->index('created_at');
        });

        Schema::table('inventarios', function (Blueprint $table) {
            $table->index(['producto_id', 'bodega_id', 'existencia']); // Optimized for presence check
            $table->index('existencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['marchamo']);
            $table->dropIndex(['color']);
            $table->dropIndex(['talla']);
            $table->dropIndex(['genero']);
            $table->dropIndex(['marca_id', 'deleted_at']);
            $table->dropIndex(['proveedor_id', 'deleted_at']);
        });

        Schema::table('ordens', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropIndex(['tipo_pago_id']);
            $table->dropIndex(['asesor_id']);
            $table->dropIndex(['bodega_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('inventarios', function (Blueprint $table) {
            $table->dropIndex(['producto_id', 'bodega_id', 'existencia']);
            $table->dropIndex(['existencia']);
        });
    }
};
