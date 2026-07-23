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
        Schema::create('vale_regalos', function (Blueprint $table) {
            $table->id();
            $table->string('correlativo')->unique();
            $table->string('de');
            $table->string('para');
            $table->decimal('monto', 12, 2);
            $table->string('estado')->default('disponible');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('venta_id')->nullable();
            $table->unsignedBigInteger('pago_id')->nullable();
            $table->timestamp('fecha_canje')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('venta_id')->references('id')->on('ventas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vale_regalos');
    }
};
