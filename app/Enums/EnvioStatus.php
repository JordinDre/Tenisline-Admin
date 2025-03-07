<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EnvioStatus: string implements HasColor, HasLabel
{
    case Guatex = 'guatex';
    case Propio = 'propio';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Guatex => 'Guatex',
            self::Propio => 'Propio',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Guatex => 'indigo',
            self::Propio => 'warning',
        };
    }
}
