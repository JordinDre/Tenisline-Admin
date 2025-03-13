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
            ['tipo_pago' => 'CONTADO'],
            ['tipo_pago' => 'CRÉDITO'],
            ['tipo_pago' => 'PAGO CONTRA ENTREGA'],
            ['tipo_pago' => 'PRONTO PAGO'],
            ['tipo_pago' => 'DEPÓSITO'],
            ['tipo_pago' => 'CHEQUE'],
            ['tipo_pago' => 'TARJETA'],
            ['tipo_pago' => 'CUOTAS'],
            ['tipo_pago' => 'TRANSFERENCIA'],
            ['tipo_pago' => 'EFECTIVO'],
            ['tipo_pago' => 'NOTA DE CRÉDITO'],
            ['tipo_pago' => 'AUTORIZACIÓN'],
        ];

        foreach ($tipoPagos as $tipoPago) {
            TipoPago::create($tipoPago);
        }
    }
}
