<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Factura;
use App\Models\Inventario;
use App\Models\Kardex;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Venta;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public static function sumarInventario(Venta $venta, string $descripcion)
    {
        $venta->detalles->each(function ($detalle) use ($venta, $descripcion) {
            $inventario = Inventario::firstOrCreate(
                [
                    'producto_id' => $detalle->producto_id,
                    'bodega_id' => $venta->bodega_id,
                ],
                [
                    'existencia' => 0,
                ]
            );
            $existenciaInicial = $inventario ? $inventario->existencia : 0;
            $cantidadTotal = $detalle->cantidad;
            $inventario->existencia += $cantidadTotal;
            $inventario->save();
            Kardex::registrar(
                $detalle->producto_id,
                $venta->bodega_id,
                $cantidadTotal,
                $existenciaInicial,
                $inventario->existencia,
                'entrada',
                $venta,
                $descripcion
            );
        });
    }

    public static function restarInventario(Venta $venta, string $descripcion)
    {
        $venta->detalles->each(function ($detalle) use ($venta, $descripcion) {
            $inventario = Inventario::where('producto_id', $detalle->producto_id)
                ->where('bodega_id', $venta->bodega_id)
                ->first();
            $existenciaInicial = $inventario ? $inventario->existencia : 0;
            $cantidadTotal = $detalle->cantidad;
            if ($existenciaInicial < $cantidadTotal) {
                $producto = Producto::withTrashed()->find($detalle->producto_id);
                throw new Exception("No hay suficiente existencia para el producto {$producto->id} - {$producto->codigo} - {$producto->descripcion} - {$producto->marca->marca}- {$producto->modelo}- {$producto->talla}- {$producto->genero}");
            }
            $inventario->existencia -= $cantidadTotal;
            $inventario->save();
            Kardex::registrar(
                $detalle->producto_id,
                $venta->bodega_id,
                $cantidadTotal,
                $existenciaInicial,
                $inventario->existencia,
                'salida',
                $venta,
                $descripcion
            );
        });
    }

    /* este es el t11 el check en ventas para liquidar */

    public static function facturar(Venta $venta)
    {
        try {
            DB::transaction(function () use ($venta) {
                self::restarInventario($venta, 'Venta Confirmada');
                $venta->fecha_vencimiento = $venta->pagos->first()->tipo_pago_id == 2 ? now()->addDays($venta->cliente->credito_dias) : null;
                /* $res = FELController::facturaVenta($venta, $venta->bodega_id); */
                if (! $res['resultado']) {
                    throw new Exception($res['descripcion_errores'][0]['mensaje_error']);
                }
                $factura = new Factura;
                $factura->fel_tipo = $venta->tipo_pago_id == 2 ? 'FCAM' : 'FACT';
                $factura->fel_uuid = $res['uuid'];
                $factura->fel_serie = $res['serie'];
                $factura->fel_numero = $res['numero'];
                $factura->fel_fecha = $res['fecha'];
                $factura->user_id = auth()->user()->id;
                $factura->tipo = 'factura';
                $venta->factura()->save($factura);
                activity()->performedOn($venta)->causedBy(auth()->user())->withProperties($venta)->event('confirmacion')->log('Venta confirmada');
            });
            Notification::make()
                ->color('success')
                ->title('Se ha confirmado la Venta #'.$venta->id)
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al confirmar la Venta')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function anular($data, Venta $venta)
    {
        try {
            DB::transaction(function () use ($data, $venta) {
                self::sumarInventario($venta, 'Venta anulada');
                if ($venta->tipo_pago_id == 2) {
                    UserController::restarSaldo($venta->cliente_id, $venta->total);
                }
                if ($venta->factura()->exists()) {
                    $res = FELController::anularFacturaVenta($venta, $data['motivo']);
                    if (! $res['resultado']) {
                        throw new Exception($res['descripcion_errores'][0]['mensaje_error']);
                    }
                    $factura = new Factura;
                    $factura->fel_tipo = $venta->tipo_pago_id == 2 ? 'FCAM' : 'FACT';
                    $factura->fel_uuid = $res['uuid'];
                    $factura->fel_serie = $res['serie'];
                    $factura->fel_numero = $res['numero'];
                    $factura->fel_fecha = $res['fecha'];
                    $factura->user_id = auth()->user()->id;
                    $factura->tipo = 'anulacion';
                    $factura->motivo = $data['motivo'];
                    $venta->factura()->save($factura);
                    $venta->factura()->delete();
                }
                $venta->estado = 'anulada';
                $venta->motivo = $data['motivo'];
                $venta->fecha_anulada = now();
                $venta->anulo_id = auth()->user()->id;
                $pago = Pago::where('pagable_id', $venta->id);
                $pago->delete();
                $venta->save();
                activity()->performedOn($venta)->causedBy(auth()->user())->withProperties($venta)->event('anulación')->log('Venta anulada');
            });
            Notification::make()
                ->color('success')
                ->title('Venta anulada correctamente')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al anular la Venta')
                ->body($e->getMessage())
                ->danger()
                ->send();
            /* pruena de merge */
        }
    }

    public static function devolver($data, Venta $venta)
    {
        try {
            DB::transaction(function () use ($data, $venta) {
                $estado = 'devuelta';
                if ($venta->tipo_pago_id == 2) {
                    UserController::restarSaldo($venta->cliente_id, $venta->total);
                }

                foreach ($venta->detalles as $detalle) {
                    $totalDevuelto = $detalle->devuelto + $detalle->devuelto_mal;

                    if ($totalDevuelto > $detalle->cantidad) {
                        throw new \Exception("La cantidad devuelta del producto {$detalle->producto->descripcion} excede la cantidad vendida.");
                    }

                    if ($detalle->devuelto > 0) {
                        $inventario = Inventario::firstOrCreate(
                            [
                                'producto_id' => $detalle->producto_id,
                                'bodega_id' => $venta->bodega_id,
                            ],
                            ['existencia' => 0]
                        );
                        $existenciaInicial = $inventario ? $inventario->existencia : 0;
                        $cantidadTotal = $detalle->devuelto - $detalle->devuelto_mal;
                        $inventario->existencia += $cantidadTotal;
                        $inventario->save();
                        Kardex::registrar(
                            $detalle->producto_id,
                            $venta->bodega_id,
                            $cantidadTotal,
                            $existenciaInicial,
                            $inventario->existencia,
                            'entrada',
                            $venta,
                            'Devolución de venta'
                        );
                    }

                    if ($detalle->devuelto_mal > 0) {
                        $inventario = Inventario::firstOrCreate(
                            [
                                'producto_id' => $detalle->producto_id,
                                'bodega_id' => Bodega::MAL_ESTADO,
                            ],
                            ['existencia' => 0]
                        );
                        $existenciaInicial = $inventario ? $inventario->existencia : 0;
                        $inventario->existencia += $detalle->devuelto_mal;
                        $inventario->save();
                        Kardex::registrar(
                            $detalle->producto_id,
                            Bodega::MAL_ESTADO,
                            $detalle->devuelto_mal,
                            $existenciaInicial,
                            $inventario->existencia,
                            'entrada',
                            $venta,
                            'Devolución de venta'
                        );
                    }

                    if ($detalle->cantidad != $detalle->devuelto) {
                        $estado = 'parcialmente devuelta';
                    }
                }

                if ($venta->factura()->exists()) {
                    $res = FELController::devolverFacturaVenta($venta, $data['motivo'], $venta->bodega_id);
                    if (! $res['resultado']) {
                        throw new Exception($res['descripcion_errores'][0]['mensaje_error']);
                    }
                    $factura = new Factura;
                    $factura->fel_tipo = 'NCRE';
                    $factura->fel_uuid = $res['uuid'];
                    $factura->fel_serie = $res['serie'];
                    $factura->fel_numero = $res['numero'];
                    $factura->fel_fecha = $res['fecha'];
                    $factura->user_id = auth()->user()->id;
                    $factura->tipo = 'devolucion';
                    $factura->motivo = $data['motivo'];
                    $venta->factura()->save($factura);
                    $venta->factura()->delete();
                }

                $venta->estado = $estado;
                $venta->motivo = $data['motivo'];
                /* $venta->apoyo = $data['apoyo']; */
                $venta->fecha_devuelta = now();
                $venta->devolvio_id = auth()->user()->id;
                $venta->save();

                activity()->performedOn($venta)->causedBy(auth()->user())->withProperties($venta)->event('devolución')->log('Venta devuelta');
            });

            Notification::make()
                ->color('success')
                ->title('Venta devuelta correctamente')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al devolver la Venta')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function liquidar(Venta $venta)
    {
        try {
            DB::transaction(function () use ($venta) {
                if (round(floatval($venta->total), 0) != round(floatval($venta->pagos->sum('total')), 0)) {
                    throw new Exception('No se ha completado el proceso de pago de la Venta');
                }
                $venta->fecha_liquidada = now();
                $venta->liquido_id = auth()->user()->id;
                $venta->estado = 'liquidada';
                $venta->save();
            });
            activity()->performedOn($venta)->causedBy(auth()->user())->withProperties($venta)->event('liquidación')->log('Venta liquidada');
            Notification::make()
                ->color('success')
                ->title('Se ha liquidado la Venta #'.$venta->id)
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al liquidar Venta')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function liquidarBulk(Collection $ventas, array $data)
    {
        try {
            foreach ($ventas as $venta) {
                if ($venta->estado != 'creada') {
                    throw new Exception('No se puede liquidar la venta #'.$venta->id.' porque no está en estado creada');
                } else {
                    $pagoExistente = $venta->pagos->first();

                    if ($pagoExistente) {
                        $pagoExistente->update([
                            'no_documento' => $data['no_documento'],
                            'tipo_pago_id' => $data['tipo_pago_id'],
                        ]);
                    } else {
                        Pago::create([
                            'pagable_id' => $venta->id,
                            'pagable_type' => Venta::class,
                            'user_id' => auth()->id(),
                            'tipo_pago_id' => $data['tipo_pago_id'],
                            'no_documento' => $data['no_documento'],
                        ]);
                    }

                    $venta->update([
                        'estado' => 'liquidada',
                    ]);

                    Notification::make()
                        ->color('success')
                        ->title('Se ha liquidado la Venta #'.$venta->id)
                        ->success()
                        ->send();
                }
            }

        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al liquidar Venta')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

    }
}
