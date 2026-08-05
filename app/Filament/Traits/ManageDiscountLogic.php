<?php

namespace App\Filament\Traits;

use App\Models\Producto;
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

       /*  $this->aplicarDescuentoEfectivoGlobal($get, $set); */
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

       /*  $this->aplicarDescuentoEfectivoGlobal($get, $set); */
    }

    protected function restoreOriginalPrice(Get $get, Set $set): void
    {
        $precioOriginal = $get('precio_original') ?? 0;
        $set('precio', $precioOriginal);
        $set('subtotal', round((float) $precioOriginal * (float) ($get('cantidad') ?? 1), 2));
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
        $set('subtotal', round((float) $precioFinal * (float) ($get('cantidad') ?? 1), 2));

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
        $porcentaje = (float) ($p->precio_segundo_par ?? 0); 
        $precioVenta = (float) ($p->precio_venta ?? 0);

        if ($porcentaje <= 0.0 || $porcentaje >= 100.0 || $precioVenta <= 0.0) {
            return $precioVenta;
        }

        $descuento = $precioVenta * ($porcentaje / 100.0);
        $precio = $precioVenta - $descuento;

        return round($precio, 2);
    }

    protected const MARCHAMO_GRUPO_A = ['naranja', 'verde'];
    protected const MARCHAMO_GRUPO_B = ['rojo', 'celeste', 'amarillo'];

    protected function grupoMarchamo(?string $marchamo): ?string
    {
        $valor = strtolower(trim($marchamo ?? ''));

        if (in_array($valor, self::MARCHAMO_GRUPO_A, true)) {
            return 'A';
        }

        if (in_array($valor, self::MARCHAMO_GRUPO_B, true)) {
            return 'B';
        }

        return null;
    }

    protected function calcularPrecioSegundoParMarchamo(\App\Models\Producto $p): float
    {
        $precioVenta = (float) ($p->precio_venta ?? 0);

        if ($precioVenta <= 0.0) {
            return $precioVenta;
        }

        return round($precioVenta * 0.5, 2);
    }

    /**
     * Evalúa si el descuento "Segundo Par 50% (Marchamo)" aplica al producto dado, dentro del
     * contexto de una venta con exactamente 2 pares:
     * - Validación 1: 1 par Grupo A (naranja/verde) + 1 par Grupo B (rojo/celeste/amarillo)
     *   → 50% al par del Grupo B, sin importar el precio.
     * - Validación 2: 2 pares del Grupo B → 50% al de menor precio de los dos.
     *
     * @param  callable(array, mixed): bool  $esActual  Predicado que identifica, dentro de $detalles, cuál es la fila actual.
     * @return array{ok: bool, motivo: ?string}
     */
    protected function evaluarSegundoParMarchamo(\App\Models\Producto $producto, array $detalles, callable $esActual): array
    {
        $grupoActual = $this->grupoMarchamo($producto->marchamo ?? null);

        if ($grupoActual === null) {
            return ['ok' => false, 'motivo' => 'Este producto no tiene un marchamo válido (rojo, celeste, amarillo, naranja o verde) para esta promoción.'];
        }

        $totalPares = $this->totalPares($detalles);
        if ($totalPares !== 2) {
            return ['ok' => false, 'motivo' => 'Esta promoción solo aplica cuando la venta tiene exactamente 2 pares en total.'];
        }

        $otrosDetalles = collect($detalles)->reject($esActual)->values();

        if ($otrosDetalles->count() !== 1 || (int) ($otrosDetalles->first()['cantidad'] ?? 0) !== 1) {
            return ['ok' => false, 'motivo' => 'Esta promoción requiere exactamente dos productos (un par cada uno) en la venta.'];
        }

        $otro = $otrosDetalles->first();

        if (! in_array($otro['tipo_precio'] ?? 'normal', [null, 'normal'], true)) {
            return ['ok' => false, 'motivo' => 'El otro par debe estar a precio normal para poder aplicar esta promoción.'];
        }

        $otroProducto = Producto::find($otro['producto_id'] ?? null);
        if (! $otroProducto) {
            return ['ok' => false, 'motivo' => 'No se pudo determinar el segundo producto de la venta.'];
        }

        $grupoOtro = $this->grupoMarchamo($otroProducto->marchamo ?? null);

        if ($grupoActual === 'B' && $grupoOtro === 'A') {
            return ['ok' => true, 'motivo' => null];
        }

        if ($grupoActual === 'B' && $grupoOtro === 'B') {
            $precioActual = (float) ($producto->precio_venta ?? 0);
            $precioOtro = (float) ($otroProducto->precio_venta ?? 0);

            if ($precioActual > $precioOtro) {
                return ['ok' => false, 'motivo' => 'El 50% de descuento solo puede aplicarse al par de menor precio entre los dos productos de marchamo rojo, celeste o amarillo.'];
            }

            return ['ok' => true, 'motivo' => null];
        }

        if ($grupoActual === 'A' && $grupoOtro === 'B') {
            return ['ok' => false, 'motivo' => 'Esta promoción aplica el 50% al par de marchamo rojo, celeste o amarillo, no al de naranja/verde. Selecciónala en el otro producto.'];
        }

        return ['ok' => false, 'motivo' => 'Esta combinación de marchamos no aplica para la promoción "Segundo Par 50% (Marchamo)".'];
    }

    protected function calcularPrecioLiquidacion(\App\Models\Producto $p): float
    {
        $porcentaje = (float) ($p->precio_liquidacion ?? 0); 
        $precioVenta = (float) ($p->precio_venta ?? 0);

        if ($porcentaje <= 0.0 || $porcentaje >= 100.0 || $precioVenta <= 0.0) {
            return $precioVenta;
        }

        $descuento = $precioVenta * ($porcentaje / 100.0);
        $precio = $precioVenta - $descuento;

        return round($precio, 2);
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

    protected function idsMenorCostoElegibles(array $detalles): array
    {
        $productos = $this->mapProductosDesdeDetalles($detalles);

        $productosValidos = collect($productos)->filter(function ($p) {
            return ($p->precio_segundo_par ?? 0) > 0 && ($p->precio_venta ?? 0) > 0;
        });

        if ($productosValidos->isEmpty()) {
            return [];
        }

        $precios = $productosValidos->pluck('precio_venta')->unique();

        if ($precios->count() === 1) {
            return [];
        }

        $totalPares = $this->totalPares($detalles);
        $permitidos = $this->paresPermitidos($totalPares);

        if ($permitidos <= 0) {
            return [];
        }

        $cantidadPorProducto = collect($detalles)
            ->groupBy('producto_id')
            ->map(fn ($items) => collect($items)->sum(fn ($i) => (int) ($i['cantidad'] ?? 0)));

        $acumulado = 0;
        $ids = [];

        foreach ($productosValidos->sortBy('precio_venta') as $producto) {
            if ($acumulado >= $permitidos) {
                break;
            }

            $ids[] = $producto->id;
            $acumulado += (int) ($cantidadPorProducto[$producto->id] ?? 1);
        }

        return $ids;
    }

    protected function esProductoMenorCostoElegible(int $productoId, array $detalles): bool
    {
        $ids = $this->idsMenorCostoElegibles($detalles);

        if (empty($ids)) {
            return true;
        }

        return in_array($productoId, $ids, true);
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

    /* protected function aplicarDescuentoEfectivoGlobal(Get $get, Set $set): void
    {
        $subtotal = (float) ($get('subtotal') ?? 0);

        if ($get('descuento_efectivo_5')) {
            $total = round($subtotal * 0.95, 2);
        } else {
            $total = $subtotal;
        }

        $set('total', $total);
    } */
}
