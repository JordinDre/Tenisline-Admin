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
        /* Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 25)->unique();
            $table->string('color', 25)->nullable();
            $table->string('tipo_placa', 25)->unique();
            $table->string('marca', 25);
            $table->string('linea', 25);
            $table->year('año');
            $table->string('ejes', 25);
            $table->string('toneladas', 25);
            $table->string('tanque', 25);
            $table->string('combustible', 25);
            $table->string('volumen_carga', 25);
            $table->string('motor', 25);
            $table->string('modelo', 25);
            $table->json('imagenes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        }); */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
