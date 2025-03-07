<?php

namespace App\Filament\Inventario\Resources\ProductoResource\Pages;

use App\Filament\Inventario\Resources\ProductoResource;
use App\Models\Marca;
use App\Models\Presentacion;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateProducto extends CreateRecord
{
    protected static string $resource = ProductoResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        $slug = Str::slug(
            ($record->id ?? '').'-'.
                ($record->codigo ?? '').'-'.
                ($record->descripcion ?? '').'-'.
                (Marca::find($record->marca_id)->marca ?? '').'-'.
                (Presentacion::find($record->presentacion_id)->presentacion ?? '')
        );
        $record->update(['slug' => $slug]);
    }
}
