<?php

namespace App\Filament\Inventario\Resources\CompraResource\Pages;

use App\Filament\Inventario\Resources\CompraResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompra extends CreateRecord
{
    protected static string $resource = CompraResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $sum = $record->detalles->sum(fn ($detalle) => $detalle->cantidad * ($detalle->precio + $detalle->envio + $detalle->envase));
        $total = $record->total;
        if (round($sum, 2) == round($total, 2)) {
            $record->estado = 'completada';
        } else {
            $record->estado = 'creada';
        }

        $record->save();
    }
}
