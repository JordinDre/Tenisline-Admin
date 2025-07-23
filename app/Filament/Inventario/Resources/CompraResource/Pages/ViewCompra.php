<?php

namespace App\Filament\Inventario\Resources\CompraResource\Pages;

use App\Filament\Inventario\Resources\CompraResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCompra extends ViewRecord
{
    protected static string $resource = CompraResource::class;

    use InteractsWithCompraForm;

    public float $subtotal = 0;
    public float $total = 0;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public array $detalles = [];

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
}
