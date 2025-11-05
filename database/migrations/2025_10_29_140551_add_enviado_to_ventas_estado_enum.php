<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ventas_estado_enum', function (Blueprint $table) {
            DB::statement("ALTER TABLE ventas MODIFY COLUMN estado ENUM('creada', 'liquidada', 'anulada', 'devuelta', 'parcialmente_devuelta', 'enviado') DEFAULT 'creada'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas_estado_enum', function (Blueprint $table) {
           DB::statement("ALTER TABLE ventas MODIFY COLUMN estado ENUM('creada', 'liquidada', 'anulada', 'devuelta', 'parcialmente_devuelta') DEFAULT 'creada'");
        });
    }
};
