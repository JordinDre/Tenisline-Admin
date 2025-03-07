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
        Schema::create('guias', function (Blueprint $table) {
            $table->id();
            $table->morphs('guiable');
            $table->integer('costo')->nullable();
            $table->decimal('cobrar')->nullable()->default(0);
            $table->integer('cantidad')->nullable()->default(0);
            $table->string('tracking')->nullable();
            $table->json('hijas')->nullable();
            $table->enum('tipo', ['paquetes', 'cc'])->default('paquetes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guias');
    }
};
