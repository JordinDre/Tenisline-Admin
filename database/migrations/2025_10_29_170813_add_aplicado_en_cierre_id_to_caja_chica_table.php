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
        Schema::table('caja_chicas', function (Blueprint $table) {
            $table->foreignId('aplicado_en_cierre_id')
              ->nullable()
              ->after('aplicado')
              ->constrained('cierres')
              ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caja_chicas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aplicado_en_cierre_id');
        });
    }
};
