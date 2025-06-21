<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EventoKardexStatus: string implements HasColor, HasLabel
{
    case Entrada = 'entrada';
    case Salida = 'salida';
    case Neutro = 'neutro';
    case CAMBIO_CODIGO = 'cambio_codigo';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Salida => 'Salida',
            self::Neutro => 'Neutro',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Entrada => 'success',
            self::Salida => 'danger',
            self::Neutro => 'secondary',
        };
    }
}
