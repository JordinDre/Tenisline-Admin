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
            $table->string('codigo_destino_guatex', 150)->nullable();
            $table->string('municipio_destino_guatex', 150)->nullable();
            $table->string('punto_destino_guatex', 150)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('codigo_destino_guatex');
            $table->dropColumn('municipio_destino_guatex');
            $table->dropColumn('punto_destino_guatex');
        });
    }
};
