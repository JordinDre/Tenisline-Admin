<?php

namespace App\Filament\Inventario\Resources\CompraResource\Pages;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Inventario\Resources\CompraResource;
use App\Filament\Inventario\Resources\CompraResource\Pages\InteractsWithCompraForm;

class CreateCompra extends CreateRecord
{
    use InteractsWithCompraForm; 

    protected static string $resource = CompraResource::class;

    protected array $facturaDataToSave = [];

    public float $subtotal = 0;
    public float $total = 0;

    protected function handleRecordCreation(array $data): Model
    {
        if (isset($data['fel_uuid'], $data['fel_numero'], $data['fel_serie'])) {
            $this->facturaDataToSave = [
                'fel_uuid' => $data['fel_uuid'],
                'fel_numero' => $data['fel_numero'],
                'fel_serie' => $data['fel_serie'],
            ];
            unset($data['fel_uuid'], $data['fel_numero'], $data['fel_serie']);
        }

        return parent::handleRecordCreation($data);
    }

    protected function afterCreate(): void
    {
        if (!empty($this->detalles)) {
            $detallesParaGuardar = array_map(function ($detalle) {
                unset($detalle['descripcion']); 
                return $detalle;
            }, $this->detalles);
    
            $this->record->detalles()->createMany($detallesParaGuardar);
        }
    
        if (!empty($this->facturaDataToSave)) {
            $this->record->factura()->create(array_merge(
                $this->facturaDataToSave,
                ['user_id' => auth()->id()]
            ));
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {

        $this->facturaDataToSave = [];

        if (isset($data['fel_uuid'], $data['fel_numero'], $data['fel_serie'])) {
            $this->facturaDataToSave = [
                'fel_uuid' => $data['fel_uuid'],
                'fel_numero' => $data['fel_numero'],
                'fel_serie' => $data['fel_serie'],
            ];
            // Eliminamos para que no se guarden en compras
            unset($data['fel_uuid'], $data['fel_numero'], $data['fel_serie']);
        }

        return $data;
    }
}
