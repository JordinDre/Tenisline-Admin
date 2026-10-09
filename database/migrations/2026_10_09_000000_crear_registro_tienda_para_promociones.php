<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// El recurso "Promociones web" solo permite editar: se asegura que exista el registro único de la tienda.
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('tiendas')->whereNull('deleted_at')->exists()) {
            DB::table('tiendas')->insert(['contenido' => '[]', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        //
    }
};
