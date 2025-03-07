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
        Schema::create('bodegas', function (Blueprint $table) {
            $table->id();
            $table->string('bodega', 50)->unique();
            $table->string('direccion', 100);
            $table->unsignedBigInteger('municipio_id')->nullable();
            $table->unsignedBigInteger('departamento_id')->nullable();
            $table->unsignedBigInteger('pais_id')->default(1);

            $table->foreign('municipio_id')->references('id')->on('municipios');
            $table->foreign('departamento_id')->references('id')->on('departamentos');
            $table->foreign('pais_id')->references('id')->on('pais');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bodegas');
    }
};
