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
            $table->boolean('requiere_codigo_confirmacion')->default(false)->after('requiere_evidencia_oferta20');
            $table->string('codigo_confirmacion', 6)->nullable()->after('requiere_codigo_confirmacion');
            $table->timestamp('codigo_generado_en')->nullable()->after('codigo_confirmacion');
            $table->timestamp('codigo_confirmado_en')->nullable()->after('codigo_generado_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn([
                'requiere_codigo_confirmacion',
                'codigo_confirmacion',
                'codigo_generado_en',
                'codigo_confirmado_en',
            ]);
        });
    }
};
