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
        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->integer('cantidad');
            $table->decimal('precio', 10, 2);
            $table->decimal('precio_comp', 10, 2)->nullable();
            /* $table->decimal('ganancia', 10, 2)->nullable(); */
            $table->decimal('subtotal', 10, 2)->nullable();
            /* $table->decimal('comision', 10, 2)->nullable();
            $table->integer('bonificacion')->nullable()->default(0); */
            /* $table->integer('devuelto')->nullable()->default(0);
            $table->integer('devuelto_mal')->nullable()->default(0); */
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('venta_id');
            $table->unsignedBigInteger('escala_id')->nullable();
            $table->timestamps();
            $table->foreign('producto_id')->references('id')->on('productos');
            $table->foreign('venta_id')->references('id')->on('ventas');
            $table->foreign('escala_id')->references('id')->on('escalas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_detalles');
    }
};
