<?php

namespace App\Filament\Inventario\Resources\CajaChicaResource\Pages;

use App\Models\Pago;
use Filament\Actions;
use App\Models\CajaChica;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Inventario\Resources\CajaChicaResource;

class CreateCajaChica extends CreateRecord
{
    protected static string $resource = CajaChicaResource::class;
}
