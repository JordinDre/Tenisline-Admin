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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->morphs('facturable');
            $table->unsignedBigInteger('user_id');
            $table->text('motivo')->nullable();
            $table->enum('fel_tipo', ['FACT', 'FCAM', 'NCRE'])
                ->default('FACT');
            $table->string('fel_uuid')->nullable();
            $table->string('fel_serie')->nullable();
            $table->string('fel_numero')->nullable();
            $table->string('fel_fecha')->nullable();
            $table->enum('tipo', ['factura', 'anulacion', 'devolucion'])
                ->default('factura');
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
