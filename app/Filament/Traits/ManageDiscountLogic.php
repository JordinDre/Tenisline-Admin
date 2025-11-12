<?php

namespace App\Filament\Traits;

use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Producto;
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
            ->filter(fn ($d) => ($d['oferta'] ?? false))
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

    protected function calcularPrecioSegundoPar(\App\Models\Producto $p): float
    {
        $porcentaje = (float) ($p->precio_segundo_par ?? 0); // ahora significa % de descuento
        $costo      = (float) ($p->precio_costo ?? 0);

        // Debe ser 0 < % < 100 y costo > 0
        if ($porcentaje <= 0.0 || $porcentaje >= 100.0 || $costo <= 0.0) {
            return (float) $p->precio_venta;
        }

        $den = 1.0 - ($porcentaje / 100.0);   // p.ej. 20% => 0.80
        if ($den <= 0.0) {
            return (float) $p->precio_venta;
        }

        // Redondeo a 2 decimales para que coincida con lo que esperas en UI/DB
        $precio = $costo / $den;

        // IMPORTANTE: forzar 2 decimales consistentes con number_format
        return round($precio + 1e-9, 2);
    }

    protected function mapProductosDesdeDetalles(array $detalles): array
    {
        $ids = collect($detalles)
            ->pluck('producto_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        return Producto::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id')
            ->all();
    }

    protected function hayAlgunoConPorcentajeSegundoPar(array $detalles): bool
    {
        $productos = $this->mapProductosDesdeDetalles($detalles);

        foreach ($productos as $p) {
            if ((float) ($p->precio_segundo_par ?? 0) > 0) {
                return true;
            }
        }
        return false;
    }

    protected function idMenorCostoElegible(array $detalles): ?int
    {
        $productos = $this->mapProductosDesdeDetalles($detalles);

        $minId = null;
        $minCosto = null;

        foreach ($productos as $p) {
            $porcentaje = (float) ($p->precio_segundo_par ?? 0);
            $costo = (float) ($p->precio_costo ?? 0);

            if ($porcentaje > 0 && $costo > 0) {
                if ($minCosto === null || $costo < $minCosto) {
                    $minCosto = $costo;
                    $minId = (int) $p->id;
                }
            }
        }

        return $minId;
    }

    protected function esProductoMenorCostoElegible(int $productoId, array $detalles): bool
    {
        $minId = $this->idMenorCostoElegible($detalles);
        return $minId !== null && $minId === $productoId;
    }

    protected function totalPares(array $detalles): int
    {
        return (int) collect($detalles)->sum(fn ($i) => (int) ($i['cantidad'] ?? 0));
    }

    protected function paresPermitidos(int $totalPares): int
    {
        return intdiv($totalPares, 2);
    }

    protected function paresConSegundoParExcluyendo(array $detalles, ?string $currentUuid): int
    {
        return (int) collect($detalles)
            ->filter(fn ($i) => ($i['uuid'] ?? null) !== $currentUuid && ($i['tipo_precio'] ?? null) === 'segundo_par')
            ->sum(fn ($i) => (int) ($i['cantidad'] ?? 0));
    }
}
