<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoOrdenStatus: string implements HasColor, HasLabel
{
    case Creada = 'creada';
    case BackOrder = 'backorder';
    case Completada = 'completada';
    case Confirmada = 'confirmada';
    case Recolectada = 'recolectada';
    case Preparada = 'preparada';
    case Enviada = 'enviada';
    case Finalizada = 'finalizada';
    case Liquidada = 'liquidada';
    case ParcialmenteDevuelta = 'parcialmente devuelta';
    case Devuelta = 'devuelta';
    case Anulada = 'anulada';
    case Cotizacion = 'cotizacion';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Cotizacion => 'Cotización',
            self::BackOrder => 'BackOrder',
            self::Creada => 'Creada',
            self::Completada => 'Completada',
            self::Confirmada => 'Confirmada',
            self::Recolectada => 'Recolectada',
            self::Preparada => 'Preparada',
            self::Enviada => 'Enviada',
            self::Finalizada => 'Finalizada',
            self::Liquidada => 'Liquidada',
            self::ParcialmenteDevuelta => 'Parcialmente Devuelta',
            self::Devuelta => 'Devuelta',
            self::Anulada => 'Anulada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Cotizacion => 'zinc',
            self::BackOrder => 'warning',
            self::Creada => 'primary',
            self::Completada => 'violet',
            self::Confirmada => 'success',
            self::Recolectada => 'indigo',
            self::Preparada => 'rose',
            self::Enviada => 'pink',
            self::Finalizada => 'purple',
            self::Liquidada => 'lime',
            self::ParcialmenteDevuelta => 'teal',
            self::Devuelta => 'orange',
            self::Anulada => 'danger',
        };
    }
}
