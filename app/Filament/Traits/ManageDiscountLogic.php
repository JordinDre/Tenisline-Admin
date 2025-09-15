<?php

namespace App\Filament\Traits;

use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;

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

        $totalGeneral = collect($detalles)->sum(function ($item) {
            $precioItem = $item['precio'] ?? 0;
            $cantidadItem = $item['cantidad'] ?? 0;

            return round($precioItem * $cantidadItem, 2);
        });

        $set('../../subtotal', $totalGeneral);
        $set('../../total', $totalGeneral);
    }

    protected function updateRootTotals(Get $get, Set $set): void
    {
        $detalles = $get('detalles') ?? [];

        $totalGeneral = collect($detalles)->sum(function ($item) {
            $precioItem = $item['precio'] ?? 0;
            $cantidadItem = $item['cantidad'] ?? 0;

            return round($precioItem * $cantidadItem, 2);
        });

        $set('subtotal', $totalGeneral);
        $set('total', $totalGeneral);
    }

    protected function restoreOriginalPrice(Get $get, Set $set): void
    {
        $precioOriginal = $get('precio_original') ?? 0;
        $set('precio', $precioOriginal);
        $set('subtotal', round($precioOriginal * ($get('cantidad') ?? 1), 2));
    }

    protected function getAvailableDiscountSlots(array $detalles): int
    {
        $totalProductos = collect($detalles)->sum('cantidad');

        return floor($totalProductos / 2);
    }

    protected function countActiveIndividualDiscounts(array $detalles): int
    {
        return collect($detalles)
            ->filter(fn ($d) => ($d['oferta'] ?? false) || ($d['oferta_20'] ?? false))
            ->count();
    }

    protected function handleItemDiscountToggle(bool $state, Get $get, Set $set, string $toggleName, callable $priceCalculationFn): void
    {
        if (! $state) {
            $this->restoreOriginalPrice($get, $set);
            $this->updateOrderTotals($get, $set);

            return;
        }

        $detalles = $this->getDetallesArray($get);
        $totalProductos = collect($detalles)->sum('cantidad');

      /*   if ($totalProductos === 1) {
            if ($toggleName === 'oferta') {
                Notification::make()
                    ->title('Descuento no aplicable')
                    ->body('El descuento "oferta" requiere al menos 2 productos.')
                    ->danger()
                    ->send();
                $set($toggleName, false);

                return;
            }
            $precioOriginal = $get('precio_original') ?? 0;
            $precioFinal = $priceCalculationFn($precioOriginal);

            $set('precio', $precioFinal);
            $set('subtotal', round($precioFinal * ($get('cantidad') ?? 1), 2));

            $this->updateOrderTotals($get, $set);

            return;
        } */

        $activeIndividualDiscounts = $this->countActiveIndividualDiscounts($detalles);

        $totalActiveDiscounts = $activeIndividualDiscounts;
        $availableSlots = $this->getAvailableDiscountSlots($detalles);

        /* if ($totalActiveDiscounts > $availableSlots) {
            Notification::make()
                ->title('Límite de promociones alcanzado')
                ->body('Ya has aplicado el máximo de descuentos permitidos en esta venta. Para aplicar uno nuevo, desactiva uno existente o agrega más productos.')
                ->danger()
                ->send();
            $set($toggleName, false);

            return;
        } */

        $precioOriginal = $get('precio_original') ?? 0;
        $precioFinal = $priceCalculationFn($precioOriginal);

        $set('precio', $precioFinal);
        $set('subtotal', round($precioFinal * ($get('cantidad') ?? 1), 2));

        $this->updateOrderTotals($get, $set);
    }

    protected function hasActiveConflict(Get $get, string $currentToggleName): bool
    {
        return false;
    }

    protected function sendConflictNotification(): void
    {
        Notification::make()
            ->title('Conflicto de descuentos')
            ->body('Solo se puede aplicar un tipo de descuento a la vez en la venta.')
            ->danger()
            ->send();
    }
}
