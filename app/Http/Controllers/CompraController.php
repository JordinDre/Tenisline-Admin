<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Inventario;
use App\Models\Kardex;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

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
                $compra->estado = 'confirmada';
                $compra->fecha_confirmada = now();
                $compra->confirmo_id = auth()->user()->id;
                $compra->save();
                OrdenController::actualizarBackorder();
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
