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
        /* Schema::create('promocion_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promocion_id');
            $table->foreign('promocion_id')->references('id')->on('promocions');
            $table->unsignedBigInteger('producto_id');
            $table->foreign('producto_id')->references('id')->on('productos'); */
        // En el caso de promociones MIX o COMBO se puede definir un rol:
        // 'principal' para el producto base o disparador
        // 'adicional' para los que se agregan a la promoción
        /*    $table->enum('tipo', ['principal', 'adicional'])->nullable();
           $table->timestamps();
        }); */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocion_detalles');
    }
};
