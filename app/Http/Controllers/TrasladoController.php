<?php

namespace App\Http\Controllers;

use App\Filament\Inventario\Resources\TrasladoResource;
use App\Models\Bodega;
use App\Models\Inventario;
use App\Models\Kardex;
use App\Models\Producto;
use App\Models\Traslado;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class TrasladoController extends Controller
{
    public static function manejarInventario(
        Traslado $traslado,
        string $descripcion,
        string $tipoOperacion,
        ?int $bodegaId = null
    ) {
        $traslado->detalles()->each(function ($detalle) use ($traslado, $descripcion, $tipoOperacion, $bodegaId) {
            $bodegaId = $bodegaId ?? ($tipoOperacion === 'entrada' ? $traslado->entrada_id : $traslado->salida_id);
            $inventario = Inventario::firstOrCreate(
                ['producto_id' => $detalle->producto_id, 'bodega_id' => $bodegaId],
                ['existencia' => 0]
            );

            $existenciaInicial = $inventario->existencia;

            if ($tipoOperacion === 'entrada') {
                $cantidad = $detalle->cantidad_recibida ?? $detalle->cantidad_enviada;
                $inventario->existencia += $cantidad;
            } elseif ($tipoOperacion === 'salida') {
                $cantidad = $detalle->cantidad_enviada ?? $detalle->cantidad_recibida;
                if ($inventario->existencia < $cantidad) {
                    $producto = Producto::withTrashed()->find($detalle->producto_id);
                    throw new Exception("La cantidad solicitada para el producto {$producto->id} - {$producto->codigo} - {$producto->descripcion} - {$producto->marca->marca} - {$producto->presentacion->presentacion} supera la existencia disponible.");
                }
                $inventario->existencia -= $cantidad;
            }

            $inventario->save();

            // Registrar Kardex
            Kardex::registrar(
                $detalle->producto_id,
                $bodegaId,
                $cantidad,
                $existenciaInicial,
                $inventario->existencia,
                $tipoOperacion,
                $traslado,
                $descripcion
            );
        });
    }

    public static function preparar(Traslado $traslado)
    {
        try {
            if ($traslado->detalles->isEmpty()) {
                throw new Exception('No se ha agregado ningún producto al traslado.');
            }

            DB::transaction(function () use ($traslado) {
                self::manejarInventario($traslado, 'Traslado preparado', 'salida');
                self::manejarInventario($traslado, 'Traslado confirmado', 'entrada', Bodega::TRASLADO);
                $traslado->estado = 'preparado';
                $traslado->fecha_preparado = now();
                $traslado->save();
            });

            activity()->performedOn($traslado)->causedBy(auth()->user())
                ->withProperties($traslado)
                ->event('envío')->log('Traslado preparado');

            Notification::make()
                ->color('success')
                ->title('Traslado preparado correctamente')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al preparar el Traslado')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function confirmar(Traslado $traslado)
    {
        try {
            // Verificar que todas las cantidades enviadas y recibidas sean iguales
            foreach ($traslado->detalles as $detalle) {
                if ($detalle->cantidad_enviada != $detalle->cantidad_recibida) {
                    throw new Exception("El producto {$detalle->producto->codigo} tiene una diferencia en la cantidad enviada ({$detalle->cantidad_enviada}) y la cantidad recibida ({$detalle->cantidad_recibida}). No se puede confirmar el traslado.");
                }
            }

            DB::transaction(function () use ($traslado) {
                self::manejarInventario($traslado, 'Traslado confirmado', 'entrada', $traslado->entrada_id);
                self::manejarInventario($traslado, 'Traslado confirmado', 'salida', Bodega::TRASLADO);
                $traslado->estado = 'confirmado';
                $traslado->fecha_confirmado = now();
                $traslado->receptor_id = auth()->user()->id;
                $traslado->save();
                OrdenController::actualizarBackorder();
            });

            activity()->performedOn($traslado)->causedBy(auth()->user())
                ->withProperties($traslado)
                ->event('confirmación')->log('Traslado confirmado');

            Notification::make()
                ->title('Traslado confirmado correctamente')
                ->success()
                ->color('success')
                ->send();

            return redirect(TrasladoResource::getUrl());
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al confirmar el Traslado')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function anular(Traslado $traslado)
    {
        try {
            DB::transaction(function () use ($traslado) {
                // Revertir los productos de la bodega destino a la bodega origen
                self::manejarInventario($traslado, 'Traslado anulado', 'salida', Bodega::TRASLADO);
                self::manejarInventario($traslado, 'Traslado anulado', 'entrada', $traslado->salida_id);

                $traslado->estado = 'anulado';
                $traslado->fecha_anulado = now(); // Fecha de anulación
                $traslado->anulo_id = auth()->user()->id;
                $traslado->save();
            });

            activity()->performedOn($traslado)->causedBy(auth()->user())
                ->withProperties($traslado)
                ->event('anulación')->log('Traslado anulado');

            Notification::make()
                ->title('Traslado anulado correctamente')
                ->success()
                ->color('success')
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al anular el Traslado')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function entregar(Traslado $traslado)
    {
        try {
            DB::transaction(function () use ($traslado) {
                $traslado->estado = 'recibido';
                $traslado->fecha_recibido = now();
                $traslado->save();
            });
            activity()->performedOn($traslado)->causedBy(auth()->user())->withProperties($traslado)->event('recepción')->log('Traslado entregado');
            Notification::make()
                ->color('success')
                ->title('Traslado entregado correctamente')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al entregar el Traslado')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function recolectar(Traslado $traslado)
    {
        try {
            if ($traslado->detalles->isEmpty()) {
                throw new Exception('No se ha agregado ningún producto al traslado.');
            }

            DB::transaction(function () use ($traslado) {
                $traslado->estado = 'en tránsito';
                $traslado->fecha_salida = now();
                $traslado->piloto_id = auth()->user()->id;
                $traslado->save();
            });

            activity()->performedOn($traslado)->causedBy(auth()->user())
                ->withProperties($traslado)
                ->event('envío')->log('Traslado recolectado');

            Notification::make()
                ->title('Traslado recolectado correctamente')
                ->success()
                ->color('success')
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al recolectar el Traslado')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
