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
        /* Schema::create('direccions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('municipio_id')->nullable();
            $table->unsignedBigInteger('departamento_id')->nullable();
            $table->unsignedBigInteger('pais_id')->default(1);
            $table->string('direccion')->nullable();
            $table->string('referencia')->nullable();
            $table->integer('zona')->nullable();
            $table->string('encargado', 50)->nullable();
            $table->string('encargado_contacto', 25)->nullable();
            $table->json('imagenes')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('municipio_id')->references('id')->on('municipios');
            $table->foreign('departamento_id')->references('id')->on('departamentos');
            $table->foreign('pais_id')->references('id')->on('pais');
            $table->softDeletes();
            $table->timestamps();
        }); */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direccions');
    }
};
