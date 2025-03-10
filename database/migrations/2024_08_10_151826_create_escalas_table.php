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
        Schema::create('escalas', function (Blueprint $table) {
            $table->id();
            $table->enum('dia', ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado']);
            $table->decimal('porcentaje', 10, 2);
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('productos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escalas');
    }
};
