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
     * Agrupa, dentro de la venta, las unidades candidatas a la promoción "Segundo Par 50%
     * (Marchamo)": filas con marchamo Grupo A (naranja/verde) o Grupo B (rojo/celeste/amarillo)
     * cuyo tipo_precio actual es 'normal' o 'segundo_par_marchamo' (sin otra oferta activa).
     *
     * @return array{0: int, 1: int, 2: \Illuminate\Support\Collection} [countA, countB, filasB ordenadas por precio_venta asc]
     */
    protected function candidatosSegundoParMarchamo(array $detalles): array
    {
        $productos = $this->mapProductosDesdeDetalles($detalles);

        $cantidadPorProducto = collect($detalles)
            ->filter(fn ($d) => in_array($d['tipo_precio'] ?? 'normal', [null, 'normal', 'segundo_par_marchamo'], true))
            ->groupBy('producto_id')
            ->map(fn ($items) => collect($items)->sum(fn ($i) => (int) ($i['cantidad'] ?? 0)));

        $countA = 0;
        $countB = 0;
        $filasB = collect();

        foreach ($cantidadPorProducto as $productoId => $cantidad) {
            $p = $productos[$productoId] ?? null;
            if (! $p) {
                continue;
            }

            $grupo = $this->grupoMarchamo($p->marchamo ?? null);

            if ($grupo === 'A') {
                $countA += $cantidad;
            } elseif ($grupo === 'B') {
                $countB += $cantidad;
                $filasB->push([
                    'producto_id' => $productoId,
                    'cantidad' => $cantidad,
                    'precio_venta' => (float) ($p->precio_venta ?? 0),
                ]);
            }
        }

        return [$countA, $countB, $filasB->sortBy('precio_venta')->values()];
    }

    /**
     * IDs de producto (Grupo B) elegibles para el 50% de "Segundo Par (Marchamo)": siempre los
     * de menor precio, hasta el cupo disponible = floor((countA + countB) / 2), sin exceder countB.
     * Esto implementa ambas validaciones de forma unificada:
     * - Grupo A nunca recibe el 50% (solo sirve de "respaldo" para descontar un par del Grupo B).
     * - Entre varios pares del Grupo B, siempre se descuentan primero los más baratos.
     */
    protected function idsElegiblesSegundoParMarchamo(array $detalles): array
    {
        [$countA, $countB, $filasB] = $this->candidatosSegundoParMarchamo($detalles);

        $cupo = min(intdiv($countA + $countB, 2), $countB);

        if ($cupo <= 0) {
            return [];
        }

        $acumulado = 0;
        $ids = [];

        foreach ($filasB as $fila) {
            if ($acumulado >= $cupo) {
                break;
            }

            $ids[] = $fila['producto_id'];
            $acumulado += $fila['cantidad'];
        }

        return $ids;
    }

    protected function esProductoElegibleSegundoParMarchamo(int $productoId, array $detalles): bool
    {
        return in_array($productoId, $this->idsElegiblesSegundoParMarchamo($detalles), true);
    }

    /**
     * Evalúa si el descuento "Segundo Par 50% (Marchamo)" aplica al producto dado, considerando
     * TODOS los pares de marchamo Grupo A/Grupo B en la venta (no solo 2). El 50% siempre recae
     * en los pares del Grupo B (rojo/celeste/amarillo) de menor precio, hasta el cupo disponible:
     * - Validación 1: cada par del Grupo A (naranja/verde) habilita el cupo para descontar un par
     *   del Grupo B, sin importar cuál sea más caro.
     * - Validación 2: entre pares del propio Grupo B, se descuentan siempre los de menor precio.
     *
     * @return array{ok: bool, motivo: ?string}
     */
    protected function evaluarSegundoParMarchamo(\App\Models\Producto $producto, array $detalles): array
    {
        $grupo = $this->grupoMarchamo($producto->marchamo ?? null);

        if ($grupo === null) {
            return ['ok' => false, 'motivo' => 'Este producto no tiene un marchamo válido (rojo, celeste, amarillo, naranja o verde) para esta promoción.'];
        }

        if ($grupo === 'A') {
            return ['ok' => false, 'motivo' => 'Esta promoción aplica el 50% solo al par de marchamo rojo, celeste o amarillo, no al de naranja/verde.'];
        }

        if (! $this->esProductoElegibleSegundoParMarchamo($producto->id, $detalles)) {
            return ['ok' => false, 'motivo' => 'El 50% solo puede aplicarse a los pares de marchamo rojo/celeste/amarillo de menor precio dentro del cupo disponible (se necesita un par de naranja/verde o de rojo/celeste/amarillo a precio normal por cada par en promoción).'];
        }

        return ['ok' => true, 'motivo' => null];
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
