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
        Schema::table('cierres', function (Blueprint $table) {
            $table->boolean('liquidado_completo')->default(false)->after('cierre');
            $table->timestamp('fecha_liquidado_completo')->nullable()->after('liquidado_completo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cierres', function (Blueprint $table) {
            $table->dropColumn(['liquidado_completo', 'fecha_liquidado_completo']);
        });
    }
};
