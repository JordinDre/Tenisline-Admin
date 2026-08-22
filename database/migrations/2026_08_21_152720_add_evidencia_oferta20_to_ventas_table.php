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
        Schema::table('ventas', function (Blueprint $table) {
            $table->boolean('requiere_validacion_pago')->default(false)->after('estado');
            $table->boolean('requiere_evidencia_oferta20')->default(false)->after('requiere_validacion_pago');
            $table->string('foto_evidencia_oferta20')->nullable()->after('requiere_evidencia_oferta20');
            $table->decimal('foto_evidencia_lat', 10, 7)->nullable()->after('foto_evidencia_oferta20');
            $table->decimal('foto_evidencia_lng', 10, 7)->nullable()->after('foto_evidencia_lat');
            $table->timestamp('foto_evidencia_capturada_en')->nullable()->after('foto_evidencia_lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn([
                'requiere_validacion_pago',
                'requiere_evidencia_oferta20',
                'foto_evidencia_oferta20',
                'foto_evidencia_lat',
                'foto_evidencia_lng',
                'foto_evidencia_capturada_en',
            ]);
        });
    }
};
