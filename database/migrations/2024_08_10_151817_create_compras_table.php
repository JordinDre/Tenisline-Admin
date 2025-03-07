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
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('confirmo_id')->nullable();
            $table->unsignedBigInteger('anulo_id')->nullable();
            $table->decimal('total', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->integer('dias_credito')->nullable();
            $table->unsignedBigInteger('tipo_pago_id');
            $table->enum('estado', ['creada', 'completada', 'confirmada', 'anulada'])->default('creada');
            $table->unsignedBigInteger('bodega_id');
            $table->unsignedBigInteger('proveedor_id');
            $table->text('observaciones')->nullable();
            $table->dateTime('fecha_completada')->nullable();
            $table->dateTime('fecha_confirmada')->nullable();
            $table->dateTime('fecha_anulada')->nullable();
            $table->timestamps();
            $table->foreign('tipo_pago_id')->references('id')->on('tipo_pagos');
            $table->foreign('bodega_id')->references('id')->on('bodegas');
            $table->foreign('proveedor_id')->references('id')->on('users');
            $table->foreign('confirmo_id')->references('id')->on('users');
            $table->foreign('anulo_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
