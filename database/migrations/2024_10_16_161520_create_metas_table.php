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
        Schema::create('metas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('bodega_id')->nullable();
            $table->integer('mes');
            $table->year('anio');
            $table->decimal('meta', 10, 2);
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('bodega_id')->references('id')->on('bodegas');
            $table->timestamps();
            $table->unique(['user_id', 'mes', 'anio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metas');
    }
};
