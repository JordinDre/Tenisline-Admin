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
        Schema::create('cierres', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bodega_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->dateTime('apertura')->nullable();
            $table->dateTime('cierre')->nullable();
            $table->foreign('bodega_id')->references('id')->on('bodegas');
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cierres');
    }
};
