<?php

namespace App\Filament\Inventario\Resources\TrasladoResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Inventario\Resources\TrasladoResource;
use Kenepa\ResourceLock\Resources\Pages\Concerns\UsesResourceLock;
use App\Filament\Inventario\Resources\TrasladoResource\Pages\FormTraslado;

class EditTraslado extends EditRecord
{
    public array $detalles = [];

    protected static string $resource = TrasladoResource::class;

    public function mount($record): void
    {
        parent::mount($record); 

        $this->detalles = $this->record->detalles->map(function ($detalle) {
            return [
                'producto_id' => $detalle->producto_id,
                'descripcion' => $detalle->producto->descripcion ?? 'Producto eliminado',
                'cantidad_enviada' => $detalle->cantidad_enviada,
            ];
        })->toArray();
    }

    public function eliminarDetalle($index)
    {
        unset($this->detalles[$index]);
        $this->detalles = array_values($this->detalles);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    public function isEdit(): bool
    {
        return true;
    }

    protected function afterSave(): void
    {
        $this->record->detalles()->delete();

        foreach ($this->detalles as $detalle) {
            $this->record->detalles()->create([
                'producto_id' => $detalle['producto_id'],
                'cantidad_enviada' => $detalle['cantidad_enviada'],
            ]);
        }
    }
}
