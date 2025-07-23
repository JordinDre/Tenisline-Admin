<?php

namespace App\Filament\Inventario\Resources\TrasladoResource\Pages;

use Filament\Resources\Pages\Page;

abstract class FormTraslado extends Page
{
    public array $detalles = [];

    public function eliminarDetalle($index)
    {
        unset($this->detalles[$index]);
        $this->detalles = array_values($this->detalles);
    }

    public function agregarDetalle($productoId, $cantidad)
    {
        $producto = \App\Models\Producto::find($productoId);
        if (! $producto) return;

        $existe = collect($this->detalles)->contains(
            fn ($item) => $item['producto_id'] === $producto->id
        );

        if ($existe) return;

        $this->detalles[] = [
            'producto_id' => $producto->id,
            'descripcion' => $producto->descripcion,
            'cantidad_enviada' => $cantidad,
        ];
    }
}
