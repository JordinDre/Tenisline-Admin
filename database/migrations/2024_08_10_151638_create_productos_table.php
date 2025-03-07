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
            $table->string('codigo', 100)->nullable();
            $table->string('descripcion', 250);
            $table->string('slug')->unique()->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->decimal('precio_compra', 10, 2);
            $table->decimal('envio', 10, 2)->nullable()->default(0);
            $table->decimal('envase', 10, 2)->nullable()->default(0);
            $table->decimal('precio_costo', 10, 2);
            $table->integer('uni_empaque')->nullable()->default(1);
            $table->text('detalle')->nullable();
            $table->json('imagenes')->nullable();
            $table->json('videos')->nullable();
            $table->json('documentos')->nullable();
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->unsignedBigInteger('marca_id')->nullable();
            $table->unsignedBigInteger('presentacion_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('proveedor_id')->references('id')->on('users');
            $table->foreign('marca_id')->references('id')->on('marcas');
            $table->foreign('presentacion_id')->references('id')->on('presentacions');
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
