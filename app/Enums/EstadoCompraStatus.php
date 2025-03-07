<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoCompraStatus: string implements HasColor, HasLabel
{
    case Pendiente = 'creada';
    case Completada = 'completada';
    case Confirmada = 'confirmada';
    case Anulada = 'anulada';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pendiente => 'Creada',
            self::Completada => 'Completada',
            self::Confirmada => 'Confirmada',
            self::Anulada => 'Anulada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Completada => 'purple',
            self::Confirmada => 'success',
            self::Anulada => 'danger',
        };
    }
}
