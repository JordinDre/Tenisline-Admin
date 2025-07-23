<?php

namespace App\Filament\Inventario\Resources\TrasladoResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Inventario\Resources\TrasladoResource;
use App\Filament\Inventario\Resources\TrasladoResource\Pages\FormTraslado;

class CreateTraslado extends CreateRecord
{
    use \App\Filament\Inventario\Resources\CompraResource\Pages\InteractsWithCompraForm;

    protected static string $resource = TrasladoResource::class;

    public function eliminarDetalle($index)
    {
        unset($this->detalles[$index]);
        $this->detalles = array_values($this->detalles); 
    }
}
