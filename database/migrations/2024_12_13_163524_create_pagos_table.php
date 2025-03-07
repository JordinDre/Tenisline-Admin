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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->morphs('pagable');
            $table->decimal('monto', 10, 2)->nullable();
            $table->decimal('cod', 10, 2)->nullable()->default(0);
            $table->decimal('total', 10, 2)->nullable();
            $table->string('no_documento')->nullable();
            $table->date('fecha_transaccion')->nullable();
            $table->unsignedBigInteger('tipo_pago_id')->nullable();
            $table->string('nombre_cuenta')->nullable();
            $table->unsignedBigInteger('banco_id')->nullable();
            $table->string('no_autorizacion')->nullable();
            $table->string('no_auditoria')->nullable();
            $table->string('afiliacion')->nullable();
            $table->string('cuotas')->nullable();
            $table->string('imagen')->nullable();
            $table->timestamps();

            $table->foreign('tipo_pago_id')->references('id')->on('tipo_pagos');
            $table->foreign('banco_id')->references('id')->on('bancos');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
