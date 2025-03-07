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
        Schema::create('ordens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asesor_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('direccion_id');
            $table->unsignedBigInteger('confirmo_id')->nullable();
            $table->unsignedBigInteger('empaquetador_id')->nullable();
            $table->unsignedBigInteger('recolector_id')->nullable();
            $table->unsignedBigInteger('piloto_id')->nullable();
            $table->unsignedBigInteger('liquido_id')->nullable();
            $table->enum('estado', ['cotizacion', 'creada', 'backorder', 'completada', 'confirmada', 'recolectada', 'preparada', 'enviada', 'finalizada', 'liquidada', 'anulada', 'parcialmente devuelta', 'devuelta'])
                ->default('creada');
            $table->enum('tipo_envio', ['guatex', 'propio']);
            $table->unsignedBigInteger('tipo_pago_id');
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->decimal('envio', 10, 2)->default(0);
            $table->decimal('costo_envio', 10, 2)->default(0);
            $table->decimal('apoyo', 10, 2)->default(0)->nullable();
            $table->boolean('facturar_cf')->default(0);
            $table->boolean('comp')->default(0);
            $table->boolean('pago_validado')->default(0);
            $table->boolean('enlinea')->default(0);
            $table->boolean('prioridad')->default(0);
            $table->string('estado_envio')->nullable();
            $table->string('recibio')->nullable();
            $table->string('guatex_destino')->nullable();
            $table->unsignedBigInteger('bodega_id')->nullable();
            $table->date('prefechado')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->timestamps();
            $table->dateTime('fecha_completada')->nullable();
            $table->dateTime('fecha_confirmada')->nullable();
            $table->dateTime('fecha_inicio_recolectada')->nullable();
            $table->dateTime('fecha_fin_recolectada')->nullable();
            $table->dateTime('fecha_preparada')->nullable();
            $table->dateTime('fecha_enviada')->nullable();
            $table->dateTime('fecha_finalizada')->nullable();
            $table->dateTime('fecha_liquidada')->nullable();
            $table->text('observaciones')->nullable();
            $table->text('motivo')->nullable();
            $table->foreign('tipo_pago_id')->references('id')->on('tipo_pagos');
            $table->foreign('bodega_id')->references('id')->on('bodegas');
            $table->foreign('asesor_id')->references('id')->on('users');
            $table->foreign('direccion_id')->references('id')->on('direccions');
            $table->foreign('empaquetador_id')->references('id')->on('users');
            $table->foreign('recolector_id')->references('id')->on('users');
            $table->foreign('cliente_id')->references('id')->on('users');
            $table->foreign('confirmo_id')->references('id')->on('users');
            $table->foreign('piloto_id')->references('id')->on('users');
            $table->foreign('liquido_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordens');
    }
};
