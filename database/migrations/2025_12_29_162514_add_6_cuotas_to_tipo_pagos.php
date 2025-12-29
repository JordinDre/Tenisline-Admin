<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si el registro ya existe
        $exists = DB::table('tipo_pagos')->where('tipo_pago', '6 CUOTAS')->exists();

        if (! $exists) {
            // Verificar si el ID 21 está disponible, si no, usar el siguiente disponible
            $idToUse = 21;
            $idExists = DB::table('tipo_pagos')->where('id', $idToUse)->exists();

            if ($idExists) {
                $idToUse = DB::table('tipo_pagos')->max('id') + 1;
            }

            // Insertar el nuevo tipo de pago
            DB::table('tipo_pagos')->insert([
                'id' => $idToUse,
                'tipo_pago' => '6 CUOTAS',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tipo_pagos')->where('tipo_pago', '6 CUOTAS')->delete();
    }
};
