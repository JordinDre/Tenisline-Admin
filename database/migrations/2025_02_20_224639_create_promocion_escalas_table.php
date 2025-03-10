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
       /*  Schema::create('promocion_escalas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promocion_id');
            $table->unsignedBigInteger('escala_id');
            $table->foreign('promocion_id')->references('id')->on('promocions');
            $table->foreign('escala_id')->references('id')->on('escalas');
            $table->timestamps();
        }); */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocion_escalas');
    }
};
