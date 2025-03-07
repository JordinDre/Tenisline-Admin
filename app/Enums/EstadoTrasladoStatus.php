<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoTrasladoStatus: string implements HasColor, HasLabel
{
    case Creado = 'creado';
    case Preparado = 'preparado';
    case EnTransito = 'en tránsito';
    case Recibido = 'recibido';
    case Confirmado = 'confirmado';
    case Anulado = 'anulado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Creado => 'Creado',
            self::Preparado => 'Preparado',
            self::EnTransito => 'En Tránsito',
            self::Recibido => 'Recibido',
            self::Confirmado => 'Confirmado',
            self::Anulado => 'Anulado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Creado => 'info',
            self::Preparado => 'warning',
            self::EnTransito => 'pink',
            self::Recibido => 'purple',
            self::Confirmado => 'success',
            self::Anulado => 'danger',
        };
    }
}
