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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->string('descripcion', 250);
            $table->string('slug')->unique()->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->decimal('precio_venta', 10, 2);
            $table->decimal('precio_vendedores', 10, 2)->nullable()->default(0);
            $table->decimal('precio_mayorista', 10, 2)->nullable()->default(0);
            $table->decimal('precio_compra', 10, 2)->nullable()->default(0);
            $table->decimal('envio', 10, 2)->nullable()->default(0);
            $table->decimal('precio_costo', 10, 2)->nullable()->default(0);
            $table->string('modelo', 250)->nullable();
            $table->string('talla', 250)->nullable();
            $table->string('color')->nullable();
            $table->enum('genero', ['Hombre', 'Mujer', 'Niños', 'Niñas', 'Bebés', 'Unisex']);
            $table->json('imagenes')->nullable();
            $table->json('videos')->nullable();
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->unsignedBigInteger('marca_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('proveedor_id')->references('id')->on('users');
            $table->foreign('marca_id')->references('id')->on('marcas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
