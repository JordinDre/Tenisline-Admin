<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Compra;
use App\Models\Kardex;
use App\Models\Producto;
use App\Models\Inventario;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class CompraController extends Controller
{
    public static function sumarInventario(Compra $compra, string $descripcion)
    {
        $compra->detalles->each(function ($detalle) use ($compra, $descripcion) {
            $inventario = Inventario::firstOrCreate(
                [
                    'producto_id' => $detalle->producto_id,
                    'bodega_id' => $compra->bodega_id,
                ],
                [
                    'existencia' => 0,
                ]
            );
            $existenciaInicial = $inventario->existencia;
            $inventario->existencia += $detalle->cantidad;
            $inventario->save();
            Kardex::registrar(
                $detalle->producto_id,
                $compra->bodega_id,
                $detalle->cantidad,
                $existenciaInicial,
                $inventario->existencia,
                'entrada',
                $compra,
                $descripcion
            );
        });
    }

    public static function confirmar(Compra $compra)
    {
        try {
            if (round($compra->total, 0) != round($compra->subtotal, 0)) {
                $compra->estado = 'creada';
                $compra->save();
                throw new Exception('El total de la compra no coincide con la suma de los detalles');
            }
            DB::transaction(function () use ($compra) {
                self::sumarInventario($compra, 'Compra confirmada');
                
                foreach ($compra->detalles as $detalles) {
                    $productoId = $detalles->producto_id ?? null;
                    $producto = Producto::find($productoId);
                    $producto->precio_costo = $detalles->precio;
                    $producto->precio_venta = $detalles->precio_venta;
                    $producto->save();
                }
                $compra->estado = 'confirmada';
                $compra->fecha_confirmada = now();
                $compra->confirmo_id = auth()->user()->id;
                $compra->save();
                /* OrdenController::actualizarBackorder(); */
                activity()->performedOn($compra)->causedBy(auth()->user())->withProperties($compra)->event('confirmación')->log('Compra confirmada');
            });
            Notification::make()
                ->color('success')
                ->title('Compra confirmada correctamente')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al confirmar la Compra')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function completar(Compra $compra)
    {
        try {
            if (round($compra->total, 0) != round($compra->subtotal, 0)) {
                $compra->estado = 'creada';
                $compra->save();
                throw new Exception('El total de la compra no coincide con la suma de los detalles');
            }
            DB::transaction(function () use ($compra) {
                $compra->estado = 'completada';
                $compra->fecha_completada = now();
                $compra->save();
                activity()->performedOn($compra)->causedBy(auth()->user())->withProperties($compra)->event('confirmación')->log('Compra completada');
            });
            Notification::make()
                ->color('success')
                ->title('Compra completada correctamente')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al completar la Compra')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function anular(Compra $compra)
    {
        try {
            DB::transaction(function () use ($compra) {
                $compra->estado = 'anulada';
                $compra->fecha_anulada = now();
                $compra->anulo_id = auth()->user()->id;
                $compra->save();
                activity()->performedOn($compra)->causedBy(auth()->user())->withProperties($compra)->event('anulación')->log('Compra anulada');
            });
            Notification::make()
                ->color('success')
                ->title('Compra anulada correctamente')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al anular la Compra')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
