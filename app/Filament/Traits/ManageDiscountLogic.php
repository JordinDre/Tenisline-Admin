<?php

namespace App\Filament\Traits;

use Filament\Forms\Get;
use Filament\Forms\Set;

trait ManageDiscountLogic
{
    protected function getDetallesArray(Get $get): array
    {
        $detalles = $get('detalles') ?? [];

        if (empty($detalles)) {
            $detalles = $get('../../detalles') ?? [];
        }

        return $detalles;
    }

    protected function updateOrderTotals(Get $get, Set $set): void
    {
        $detalles = $this->getDetallesArray($get);
        $currentUuid = $get('uuid');

        if ($currentUuid) {
            $currentPrecio = (float) ($get('precio') ?? 0);
            $currentCantidad = (float) ($get('cantidad') ?? 0);
            $currentProductoId = $get('producto_id');

            $found = false;
            foreach ($detalles as &$item) {
                if (($item['uuid'] ?? null) === $currentUuid) {
                    $item['precio'] = $currentPrecio;
                    $item['cantidad'] = $currentCantidad;
                    if ($currentProductoId) {
                        $item['producto_id'] = $currentProductoId;
                    }
                    $found = true;
                    break;
                }
            }
            unset($item);

            if (!$found && $currentProductoId) {
                $detalles[] = [
                    'uuid' => $currentUuid,
                    'producto_id' => $currentProductoId,
                    'precio' => $currentPrecio,
                    'cantidad' => $currentCantidad,
                ];
            }
        }

        $totalGeneral = collect($detalles)->sum(function ($item) {
            $precioItem = $item['precio'] ?? 0;
            $cantidadItem = $item['cantidad'] ?? 0;

            return round((float) $precioItem * (float) $cantidadItem, 2);
        });

        $set('../../subtotal', round($totalGeneral, 2));
        $set('../../total', round($totalGeneral, 2));
    }

    protected function updateRootTotals(Get $get, Set $set): void
    {
        $detalles = $get('detalles') ?? [];

        $totalGeneral = collect($detalles)->sum(function ($item) {
            $precioItem = $item['precio'] ?? 0;
            $cantidadItem = $item['cantidad'] ?? 0;

            return round((float) $precioItem * (float) $cantidadItem, 2);
        });

        $set('subtotal', $totalGeneral);
        $set('total', $totalGeneral);
    }

}
