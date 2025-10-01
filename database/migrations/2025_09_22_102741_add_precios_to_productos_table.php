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
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio_liquidacion', 10, 2)->nullable()->default(0);
            $table->decimal('precio_segundo_par', 10, 2)->nullable()->default(0);
            $table->decimal('precio_descuento', 10, 2)->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('precio_liquidacion');
            $table->dropColumn('precio_segundo_par');
            $table->dropColumn('precio_descuento');
        });
    }
};
