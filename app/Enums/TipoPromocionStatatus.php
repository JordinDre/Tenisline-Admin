<?php

namespace App\Enums;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoPromocionStatatus: string implements HasColor, HasLabel
{   
    case Mix = 'mix';
    case Bonificacion = 'bonificacion';
    case Descuento = 'descuento';
    case Combo = 'combo';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Mix => 'Mix',
            self::Bonificacion => 'Bonificación',
            self::Descuento => 'Descuento',
            self::Combo => 'Combo',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Mix => 'success',
            self::Bonificacion => 'danger',
            self::Descuento => 'secondary',
            self::Combo => 'primary',
        };
    }
    
}
