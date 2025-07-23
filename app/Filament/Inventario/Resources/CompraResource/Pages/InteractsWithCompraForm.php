<?php

namespace App\Filament\Inventario\Resources\CompraResource\Pages;

use App\Models\Producto;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;

trait InteractsWithCompraForm
{
    public array $detalles = [];

    /**
     * Se llama desde la acción del formulario para agregar un producto al array.
     */
    public function agregarDetalle(array $data, ?Set $set = null): void
    {
        $producto = Producto::find($data['producto_id']);
        
        if (!$producto) return;
        
        // Prevenir duplicados
        $existe = collect($this->detalles)->contains('producto_id', $producto->id);

        
        if ($existe) {
            // Opcional: Enviar una notificación al usuario
            return;
        }
        
        $this->detalles[] = [
            'producto_id' => $producto->id, 
            'descripcion' => $producto->descripcion,
            'cantidad' => $data['cantidad'],
            'precio' => $data['precio'],
            'precio_venta' => $data['precio_venta'],
        ];

        $this->updateTotals($set);
    }

    /**
     * Se llama desde el botón "Eliminar" en la tabla (vía wire:click).
     */
    public function eliminarDetalle(int $index): void
    {
        if (isset($this->detalles[$index])) {
            unset($this->detalles[$index]);
            $this->detalles = array_values($this->detalles);
            $this->updateTotals();

        }
    }

    /**
     * Calcula los totales y actualiza los campos del formulario.
     */
    public function updateTotals(?Set $set = null): void
    {
        $subtotal = collect($this->detalles)->sum(function ($detalle) {
            return (float)($detalle['cantidad'] ?? 0) * (float)($detalle['precio'] ?? 0);
        });


        $this->subtotal = round($subtotal, 2);
        $this->total = round($subtotal, 2);

        if ($set) {
            $set('subtotal', $this->subtotal);
            $set('total', $this->total);
        }
    }
}
