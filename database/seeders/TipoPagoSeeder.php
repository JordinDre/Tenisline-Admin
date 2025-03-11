<?php

namespace Database\Seeders;

use App\Models\TipoPago;
use Illuminate\Database\Seeder;

class TipoPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipoPagos = [
            ['tipo_pago' => 'DEPÓSITO'],
            ['tipo_pago' => 'TARJETA'],
            ['tipo_pago' => 'TRANSFERENCIA'],
            ['tipo_pago' => 'EFECTIVO'],
        ];

        foreach ($tipoPagos as $tipoPago) {
            TipoPago::create($tipoPago);
        }
    }
}
