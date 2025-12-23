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
        Schema::table('pagos', function (Blueprint $table) {
            $table->integer('ult_dgt')->after('tipo_pago_id')->nullable();
            $table->string('nombre_tarjeta')->after('tipo_pago_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('nombre_tarjeta');
            $table->dropColumn('ult_dgt');
        });
    }
};
