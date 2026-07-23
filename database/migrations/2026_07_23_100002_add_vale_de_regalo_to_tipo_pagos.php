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
        $exists = DB::table('tipo_pagos')->where('tipo_pago', 'VALE DE REGALO')->exists();

        if (! $exists) {
            $maxId = DB::table('tipo_pagos')->max('id');
            $idToUse = $maxId ? $maxId + 1 : 1;

            DB::table('tipo_pagos')->insert([
                'id' => $idToUse,
                'tipo_pago' => 'VALE DE REGALO',
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
        DB::table('tipo_pagos')->where('tipo_pago', 'VALE DE REGALO')->delete();
    }
};
