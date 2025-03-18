<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoPago extends Model
{
    use HasFactory;

    protected $guarded = [];

    public const CLIENTE_PAGOS = [
        'PAGO CONTRA ENTREGA',
        'PRONTO PAGO',
        'CRÉDITO',
        'AUTORIZACION',
    ];

    public const CLIENTE_PAGOS_SIN_CREDITO = [
        'PAGO CONTRA ENTREGA',
        'PRONTO PAGO',
    ];

    public const CLIENTE_PAGOS_ARRAY = [
        2 => 'CRÉDITO',
        3 => 'PAGO CONTRA ENTREGA',
        4 => 'PRONTO PAGO',
        12 => 'AUTORIZACION',
    ];

    public const FORMAS_PAGO_VENTA = [
        'CONTADO',
        'TARJETA',
        'LINK DE PAGO',
    ];

    public const FORMAS_PAGO = [
        'DEPÓSITO',
        'TRANSFERENCIA',
        'CHEQUE',
        'TARJETA',
        'NOTA DE CRÉDITO',
    ];

    public const FORMAS_PAGO_ARRAY = [
        5 => 'DEPÓSITO',
        9 => 'TRANSFERENCIA',
        6 => 'CHEQUE',
        7 => 'TARJETA',
        11 => 'NOTA DE CRÉDITO',
    ];

    public const COMPRAS_PAGOS = [
        'CONTADO',
        'CRÉDITO',
    ];
}
