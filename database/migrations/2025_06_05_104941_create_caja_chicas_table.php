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
        Schema::create('caja_chicas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bodega_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->text('detalle_gasto')->nullable();
            $table->string('autoriza')->nullable();
            $table->enum('estado', ['creada', 'confirmada', 'anulada'])
                ->default('creada');
            $table->timestamps();

            $table->foreign('bodega_id')->references('id')->on('bodegas');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('proveedor_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_chica');
    }
};
