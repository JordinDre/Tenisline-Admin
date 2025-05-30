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
        Schema::create('bodega_user', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('bodega_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('bodega_id')->references('id')->on('bodegas');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bodega_user');
    }
};
