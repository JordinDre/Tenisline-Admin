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
        Schema::create('traslados', function (Blueprint $table) {
            $table->id();
            $table->enum('estado', ['creado', 'preparado', 'en tránsito', 'recibido', 'confirmado', 'anulado'])->default('creado');
            $table->unsignedBigInteger('salida_id');
            $table->unsignedBigInteger('entrada_id');
            $table->unsignedBigInteger('emisor_id');
            $table->unsignedBigInteger('receptor_id')->nullable();
            $table->unsignedBigInteger('piloto_id')->nullable();
            $table->unsignedBigInteger('anulo_id')->nullable();
            $table->text('observaciones')->nullable();
            $table->dateTime('fecha_preparado')->nullable();
            $table->dateTime('fecha_salida')->nullable();
            $table->dateTime('fecha_recibido')->nullable();
            $table->dateTime('fecha_confirmado')->nullable();
            $table->dateTime('fecha_anulado')->nullable();
            $table->timestamps();

            $table->foreign('salida_id')->references('id')->on('bodegas');
            $table->foreign('entrada_id')->references('id')->on('bodegas');
            $table->foreign('emisor_id')->references('id')->on('users');
            $table->foreign('receptor_id')->references('id')->on('users');
            $table->foreign('piloto_id')->references('id')->on('users');
            $table->foreign('anulo_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traslados');
    }
};
