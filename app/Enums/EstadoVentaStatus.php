<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoVentaStatus: string implements HasColor, HasLabel
{
    case Creada = 'creada';
    case Liquidada = 'liquidada';
    case ParcialmenteDevuelta = 'parcialmente_devuelta';
    case Devuelta = 'devuelta';
    case Anulada = 'anulada';
    case Enviado = 'enviado';
    case ValidacionPago = 'validacion_pago';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Creada => 'Creada',
            self::Liquidada => 'Liquidada',
            self::ParcialmenteDevuelta => 'Parcialmente Devuelta',
            self::Anulada => 'Anulada',
            self::Devuelta => 'Devuelta',
            self::Enviado => 'Enviado',
            self::ValidacionPago => 'Validacion Pago',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Creada => 'info',
            self::ValidacionPago => 'purple',
            self::Liquidada => 'success',
            self::ParcialmenteDevuelta => 'teal',
            self::Devuelta => 'orange',
            self::Anulada => 'danger',
            self::Enviado => 'zinc',
        };
    }
}
