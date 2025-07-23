<?php

namespace App\Filament\Inventario\Resources\CompraResource\Pages;

use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ContentTabPosition;
use App\Filament\Inventario\Resources\CompraResource;
use Kenepa\ResourceLock\Resources\Pages\Concerns\UsesResourceLock;
use App\Filament\Inventario\Resources\CompraResource\Pages\InteractsWithCompraForm;

class EditCompra extends EditRecord
{
    use InteractsWithCompraForm; 

    protected static string $resource = CompraResource::class;

    protected array $facturaDataToSave = [];


    public function mount($record): void
    {
        parent::mount($record);

        $this->record->load(['detalles.producto', 'proveedor', 'bodega', 'tipoPago', 'factura']);

        $this->detalles = $this->record->detalles->map(fn ($detalle) => [
            'producto_id' => $detalle->producto_id,
            'descripcion' => $detalle->producto->descripcion ?? 'Producto eliminado',
            'cantidad' => $detalle->cantidad,
            'precio' => $detalle->precio,
            'precio_venta' => $detalle->precio_venta,
        ])->toArray();

        $this->form->fill([
            'fel_uuid' => $this->record->factura->fel_uuid ?? null,
            'fel_numero' => $this->record->factura->fel_numero ?? null,
            'fel_serie' => $this->record->factura->fel_serie ?? null,

            'bodega_id' => $this->record->bodega_id,
            'proveedor_id' => $this->record->proveedor_id,
            'tipo_pago_id' => $this->record->tipo_pago_id,

            'subtotal' => $this->record->subtotal,
            'total' => $this->record->total,
        ]);
    }

    protected function afterSave(): void
    {
        // Guardar detalles
        $this->record->detalles()->delete();

        $detallesParaGuardar = array_map(function ($detalle) {
            unset($detalle['descripcion']);
            return $detalle;
        }, $this->detalles);

        $this->record->detalles()->createMany($detallesParaGuardar);

        // Actualizar factura
        if (!empty($this->facturaDataToSave)) {
            if ($this->record->factura) {
                $this->record->factura()->update($this->facturaDataToSave);
            } else {
                $this->record->factura()->create(array_merge(
                    $this->facturaDataToSave,
                    ['user_id' => auth()->id()]
                ));
            }
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

            unset($data['fel_uuid'], $data['fel_numero'], $data['fel_serie']);
        }

        return $data;
    }

    public function isEdit(): bool
    {
        return true;
    }
}
