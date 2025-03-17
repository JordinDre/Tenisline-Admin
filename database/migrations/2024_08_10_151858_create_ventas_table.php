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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bodega_id');
            $table->unsignedBigInteger('asesor_id');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('anulo_id')->nullable();
            $table->unsignedBigInteger('devolvio_id')->nullable();
            $table->unsignedBigInteger('liquido_id')->nullable();
            $table->enum('estado', ['creada', 'liquidada', 'anulada'])
                ->default('creada');
            /* $table->unsignedBigInteger('tipo_pago_id'); */
            $table->enum('tipo_envio', ['guatex', 'propio']);
            $table->decimal('envio', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->boolean('facturar_cf')->default(0);
            $table->boolean('comp')->default(0);
            $table->text('observaciones')->nullable();
            $table->text('motivo')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->dateTime('fecha_liquidada')->nullable();
            $table->dateTime('fecha_anulada')->nullable();
            $table->dateTime('fecha_devuelta')->nullable();
            $table->dateTime('fecha_parcialmente_devuelta')->nullable();
            $table->timestamps();
            /* $table->foreign('tipo_pago_id')->references('id')->on('tipo_pagos'); */
            $table->foreign('bodega_id')->references('id')->on('bodegas');
            $table->foreign('asesor_id')->references('id')->on('users');
            $table->foreign('cliente_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
