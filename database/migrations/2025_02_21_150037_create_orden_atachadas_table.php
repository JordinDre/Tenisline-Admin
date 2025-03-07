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
        Schema::create('orden_atachadas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orden_madre_id');
            $table->foreign('orden_madre_id')->references('id')->on('ordens');
            $table->unsignedBigInteger('orden_hija_id');
            $table->foreign('orden_hija_id')->references('id')->on('ordens');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_atachadas');
    }
};
