<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CajaChicaStatus: string implements HasColor, HasLabel
{
    case Pendiente = 'creada';
    case Confirmada = 'confirmada';
    case Anulada = 'anulada';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pendiente => 'Creada',
            self::Confirmada => 'Confirmada',
            self::Anulada => 'Anulada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Confirmada => 'success',
            self::Anulada => 'danger',
        };
    }
}
