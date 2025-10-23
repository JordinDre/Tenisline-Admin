<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Pago;
use App\Models\Venta;
use App\Models\Bodega;
use App\Models\Cierre;
use App\Models\Kardex;
use App\Models\Factura;
use App\Models\Producto;
use App\Models\Inventario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

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
            $res = FELController::facturaVenta($venta, $venta->bodega_id);
            
            if (
                ! isset($res['resultado']) ||
                ! $res['resultado'] ||
                ! isset($res['uuid'], $res['serie'], $res['numero'], $res['fecha'])
            ) {
                $errorMessage = $res['descripcion_errores'][0]['mensaje_error'] ?? 'No se pudo generar la factura.';
                \Log::error('Error en facturación FEL', [
                    'venta_id' => $venta->id,
                    'error' => $errorMessage,
                    'response' => $res
                ]);
                throw new Exception($errorMessage);
            }

            self::restarInventario($venta, 'Venta Confirmada');
            $venta->fecha_vencimiento = $venta->pagos->first()->tipo_pago_id == 2 ? now()->addDays($venta->cliente->credito_dias) : null;
            $factura = new Factura;
            $factura->fel_tipo = $venta->tipo_pago_id == 2 ? 'FCAM' : 'FACT';
            $factura->fel_uuid = $res['uuid'];
            $factura->fel_serie = $res['serie'];
            $factura->fel_numero = $res['numero'];
            $factura->fel_fecha = $res['fecha'];
            $factura->user_id = Auth::user()->id;
            $factura->tipo = 'factura';
            $venta->factura()->save($factura);
            activity()->performedOn($venta)->causedBy(Auth::user())->withProperties($venta)->event('confirmacion')->log('Venta confirmada');
        } catch (\Exception $e) {
            \Log::error('Error en facturación', [
                'venta_id' => $venta->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
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
                    $factura->user_id = Auth::user()->id;
                    $factura->tipo = 'anulacion';
                    $factura->motivo = $data['motivo'];
                    $venta->factura()->save($factura);
                    $venta->factura()->delete();
                }
                $venta->estado = 'anulada';
                $venta->motivo = $data['motivo'];
                $venta->fecha_anulada = now();
                $venta->anulo_id = Auth::user()->id;
                $pago = Pago::where('pagable_id', $venta->id);
                $pago->delete();
                $venta->save();
                activity()->performedOn($venta)->causedBy(Auth::user())->withProperties($venta)->event('anulación')->log('Venta anulada');
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

                foreach ($data['detalles'] as $detalleData) {
                    $detalle = $venta->detalles->firstWhere('id', $detalleData['id']);
                    if (! $detalle) {
                        continue;
                    }

                    $detalle->devuelto = $detalleData['devuelto'] ?? 0;
                    $detalle->devuelto_mal = $detalleData['devuelto_mal'] ?? 0;
                    $detalle->save();

                    $codigoNuevo = $detalleData['codigo_nuevo'] ?? null;
                    $producto = $detalle->producto;
                    $codigoAnterior = $producto->codigo;

                    if ($codigoNuevo && $codigoNuevo !== $codigoAnterior) {
                        if ($producto->codigos_antiguos) {
                            $producto->codigos_antiguos .= ','.$codigoAnterior;
                        } else {
                            $producto->codigos_antiguos = $codigoAnterior;
                        }

                        $producto->codigo = $codigoNuevo;
                        $producto->save();

                        Kardex::registrar(
                            $producto->id,
                            $venta->bodega_id,
                            0,
                            0,
                            0,
                            'entrada',
                            $producto,
                            "Cambio de código: de {$codigoAnterior} a {$codigoNuevo} por devolución"
                        );
                    }
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
                        $estado = 'parcialmente_devuelta';
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
                    $factura->user_id = Auth::user()->id;
                    $factura->tipo = 'devolucion';
                    $factura->motivo = $data['motivo'];
                    $venta->factura()->save($factura);
                    $venta->factura()->delete();
                }

                $venta->estado = $estado;
                $venta->motivo = $data['motivo'];
                /* $venta->apoyo = $data['apoyo']; */
                $venta->fecha_devuelta = now();
                $venta->devolvio_id = Auth::user()->id;
                $venta->save();

                activity()->performedOn($venta)->causedBy(Auth::user())->withProperties($venta)->event('devolución')->log('Venta devuelta');
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
                $venta->liquido_id = Auth::user()->id;
                $venta->estado = 'liquidada';
                $venta->save();
            });
            activity()->performedOn($venta)->causedBy(Auth::user())->withProperties($venta)->event('liquidación')->log('Venta liquidada');
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
                            'user_id' => Auth::id(),
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

    public static function liquidar_cierre(array $data, Cierre $cierre)
    {
        try {
            DB::transaction(function () use ($data, $cierre) {

                $tipoPagoId = $data['tipo_pago_id'] ?? null;
                $montoPago = floatval($data['monto']);

                // Validar el pago antes de procesarlo
                $cierre->validarPagoLiquidacion($tipoPagoId, $montoPago);

                $pagoData = [
                    'tipo_pago_id'          => $tipoPagoId,
                    'banco_id'          => $data['banco_id'] ?? null,
                    'fecha_transaccion' => $data['fecha_transaccion'],
                    'no_documento'      => $data['no_documento'] ?? null,
                    'monto'             => $montoPago,
                    'tipo_pago_id'      => $data['tipo_pago_id'] ?? null,
                    'user_id'           => Auth::id(),
                ];

                if (!empty($data['imagen'])) {
                    $pagoData['imagen'] = $data['imagen'];
                }

                // Crear el pago de liquidación
                $cierre->pagos()->create($pagoData);

                // Verificar si ya se completaron todos los pagos necesarios
                if ($cierre->puedeLiquidar()) {
                    // Si se completaron todos los pagos, liquidar automáticamente todas las ventas
                    self::liquidar_ventas_cierre_completo($cierre);
                }
            });

            // Verificar si se liquidó automáticamente
            $cierre->refresh();
            if ($cierre->liquidado_completo) {
                Notification::make()
                    ->color('success')
                    ->title("Cierre #{$cierre->id} liquidado completamente")
                    ->body("Se agregó el pago y se liquidaron automáticamente todas las ventas del cierre")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->color('success')
                    ->title("Pago agregado al cierre #{$cierre->id}")
                    ->body("Se agregó el pago correctamente. Faltan más pagos para completar la liquidación.")
                    ->success()
                    ->send();
            }
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al agregar el pago')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function liquidar_ventas_cierre_completo(Cierre $cierre)
    {
        // Obtener todas las ventas del cierre que no estén liquidadas
        $ventasPendientes = Venta::where('bodega_id', $cierre->bodega_id)
            ->whereBetween('created_at', [$cierre->apertura, $cierre->cierre ?? now()])
            ->whereIn('estado', ['creada'])
            ->get();

        // Liquidar todas las ventas pendientes
        foreach ($ventasPendientes as $venta) {
            $venta->update([
                'fecha_liquidada' => now(),
                'liquido_id' => Auth::id(),
                'estado' => 'liquidada',
            ]);

            activity()
                ->performedOn($venta)
                ->causedBy(Auth::user())
                ->withProperties($venta)
                ->event('liquidación')
                ->log("Venta #{$venta->id} liquidada automáticamente desde cierre #{$cierre->id}");
        }

        // Marcar el cierre como completamente liquidado
        $cierre->update([
            'liquidado_completo' => true,
            'fecha_liquidado_completo' => now(),
        ]);

        activity()
            ->performedOn($cierre)
            ->causedBy(Auth::user())
            ->withProperties($cierre)
            ->event('liquidación automática')
            ->log("Cierre #{$cierre->id} liquidado automáticamente al completar todos los pagos");
    }

    public static function liquidar_cierre_completo(Cierre $cierre)
    {
        try {
            DB::transaction(function () use ($cierre) {
                // Verificar que el cierre esté cerrado
                if ($cierre->cierre === null) {
                    throw new \Exception('El cierre debe estar cerrado antes de liquidar completamente');
                }

                // Verificar que todos los pagos estén completos
                if (!$cierre->puedeLiquidar()) {
                    throw new \Exception('No se pueden liquidar todas las ventas. Faltan pagos por ingresar.');
                }

                // Obtener todas las ventas del cierre que no estén liquidadas
                $ventasPendientes = Venta::where('bodega_id', $cierre->bodega_id)
                    ->whereBetween('created_at', [$cierre->apertura, $cierre->cierre])
                    ->whereIn('estado', ['creada'])
                    ->get();

                // Liquidar todas las ventas pendientes
                foreach ($ventasPendientes as $venta) {
                    $venta->update([
                        'fecha_liquidada' => now(),
                        'liquido_id' => Auth::id(),
                        'estado' => 'liquidada',
                    ]);

                    activity()
                        ->performedOn($venta)
                        ->causedBy(Auth::user())
                        ->withProperties($venta)
                        ->event('liquidación')
                        ->log("Venta #{$venta->id} liquidada completamente desde cierre #{$cierre->id}");
                }

                // Marcar el cierre como completamente liquidado
                $cierre->update([
                    'liquidado_completo' => true,
                    'fecha_liquidado_completo' => now(),
                ]);

                activity()
                    ->performedOn($cierre)
                    ->causedBy(Auth::user())
                    ->withProperties($cierre)
                    ->event('liquidación completa')
                    ->log("Cierre #{$cierre->id} liquidado completamente");
            });

            Notification::make()
                ->color('success')
                ->title("Cierre #{$cierre->id} liquidado completamente")
                ->body("Se han liquidado todas las ventas del cierre correctamente")
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al liquidar el cierre completamente')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
