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
        /*  Schema::create('promocions', function (Blueprint $table) {
             $table->id();
             $table->string('nombre');
             $table->text('descripcion')->nullable();
             $table->enum('tipo', ['mix', 'bonificacion', 'descuento', 'combo']);
             $table->integer('cantidad')->nullable();
             $table->integer('bonificacion')->nullable();
             $table->integer('cantidad_requerida')->nullable();
             $table->decimal('descuento', 10, 2)->nullable();
             $table->unsignedBigInteger('presentacion_id')->nullable();
             $table->foreign('presentacion_id')->references('id')->on('presentacions');
             $table->unsignedBigInteger('marca_id')->nullable();
             $table->foreign('marca_id')->references('id')->on('marcas');
             $table->unsignedBigInteger('escala_id')->nullable();
             $table->foreign('escala_id')->references('id')->on('escalas');
             $table->boolean('estado')->default(true);
             $table->timestamps();
         }); */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocions');
    }
};
