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
        /* Schema::create('traslado_detalles', function (Blueprint $table) {
            $table->id();
            $table->integer('cantidad_enviada');
            $table->integer('cantidad_recibida')->nullable();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('traslado_id');
            $table->timestamps();
            $table->foreign('producto_id')->references('id')->on('productos');
            $table->foreign('traslado_id')->references('id')->on('traslados');
        }); */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traslado_detalles');
    }
};
