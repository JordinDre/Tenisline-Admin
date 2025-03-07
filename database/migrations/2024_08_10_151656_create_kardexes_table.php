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
        Schema::create('kardexes', function (Blueprint $table) {
            $table->id();
            $table->integer('existencia_inicial');
            $table->integer('cantidad');
            $table->integer('existencia_final');
            $table->morphs('kardexable');
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->unsignedBigInteger('bodega_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('evento', ['entrada', 'salida']);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->foreign('bodega_id')->references('id')->on('bodegas');
            $table->foreign('producto_id')->references('id')->on('productos');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardexes');
    }
};
