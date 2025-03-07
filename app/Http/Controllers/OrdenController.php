<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Factura;
use App\Models\Guia;
use App\Models\Inventario;
use App\Models\Kardex;
use App\Models\Orden;
use App\Models\Producto;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class OrdenController extends Controller
{
    public static function sumarInventario(Orden $orden, string $descripcion)
    {
        $orden->detalles->each(function ($detalle) use ($orden, $descripcion) {
            $inventario = Inventario::firstOrCreate(
                [
                    'producto_id' => $detalle->producto_id,
                    'bodega_id' => $orden->bodega_id,
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
                $orden->bodega_id,
                $cantidadTotal,
                $existenciaInicial,
                $inventario->existencia,
                'entrada',
                $orden,
                $descripcion
            );
        });
    }

    public static function restarInventario(Orden $orden, string $descripcion)
    {
        $orden->detalles->each(function ($detalle) use ($orden, $descripcion) {
            $inventario = Inventario::where('producto_id', $detalle->producto_id)
                ->where('bodega_id', $orden->bodega_id)
                ->first();
            $existenciaInicial = $inventario ? $inventario->existencia : 0;
            $cantidadTotal = $detalle->cantidad;
            $producto = Producto::withTrashed()->find($detalle->producto_id);
            if ($existenciaInicial < $cantidadTotal) {
                throw new Exception("No hay suficiente existencia para el producto {$producto->id} - {$producto->codigo} - {$producto->descripcion} - {$producto->marca->marca} - {$producto->presentacion->presentacion}.");
            }
            $inventario->existencia -= $cantidadTotal;
            $inventario->save();
            Kardex::registrar(
                $detalle->producto_id,
                $orden->bodega_id,
                $cantidadTotal,
                $existenciaInicial,
                $inventario->existencia,
                'salida',
                $orden,
                $descripcion
            );
        });
    }

    public static function confirmar(Orden $orden, int $bodega)
    {
        try {
            DB::transaction(function () use ($orden, $bodega, &$estadoFinal) {
                $orden->update(['bodega_id' => $bodega]);

                if ($orden->tipo_pago_id == 4) {
                    if ($orden->pago_validado == 0 || round(floatval($orden->total), 0) != round(floatval($orden->pagos->sum('total')), 0)) {
                        throw new Exception('No se ha completado el proceso de pago de la Orden');
                    }
                }
                if ($orden->tipo_pago_id == 12) {
                    throw new Exception('Tipo de Pago no Válido');
                }

                if ($orden->prefechado) {
                    $prefechado = Carbon::parse($orden->prefechado);

                    if ($prefechado->lte(now())) {
                        $estadoFinal = 'confirmada';
                    } else {
                        $estadoFinal = 'completada';
                    }
                } else {
                    $estadoFinal = 'confirmada';
                }
                $orden->estado = $estadoFinal;

                self::restarInventario($orden, "Orden $estadoFinal");
                $orden->fecha_confirmada = now();
                $orden->confirmo_id = auth()->user()->id;
                $orden->save();

                activity()
                    ->performedOn($orden)
                    ->causedBy(auth()->user())
                    ->withProperties($orden)
                    ->event('confirmación')
                    ->log("Orden $estadoFinal");
            });

            Notification::make()
                ->color('success')
                ->title("Orden $estadoFinal correctamente")
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al confirmar la Orden')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function asignar(Orden $orden, int $bodega)
    {
        try {
            DB::transaction(function () use ($orden, $bodega) {
                $orden->update(['bodega_id' => $bodega]);
            });

            Notification::make()
                ->color('success')
                ->title('Orden asignada correctamente')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al asignar la Orden')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function regresar(Orden $orden)
    {
        try {
            DB::transaction(function () use ($orden) {
                if ($orden->factura()->exists()) {
                    throw new Exception('La Orden ya ha sido facturada');
                }
                self::sumarInventario($orden, 'Orden regresada');
                $orden->estado = 'creada';
                $orden->save();
                activity()->performedOn($orden)->causedBy(auth()->user())->withProperties($orden)->event('regreso')->log('Orden regresada');
            });
            Notification::make()
                ->color('success')
                ->title('Orden regresada correctamente')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al regresar la Orden')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function recolectar(Orden $orden, $data)
    {
        try {
            DB::transaction(function () use ($orden, $data) {

                $orden->fecha_inicio_recolectada = now();
                $orden->recolector_id = $data['recolector_id'];
                $orden->save();
                activity()->performedOn($orden)->causedBy(auth()->user())->withProperties($orden)->event('recolección')->log('Orden recolectada');
            });
            Notification::make()
                ->color('success')
                ->title('Se ha iniciado la recolección de la Orden #'.$orden->id)
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al recolectar la Orden')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function terminar($data, Orden $orden)
    {
        try {
            DB::transaction(function () use ($data, $orden) {
                $canecasCubetasDetalles = $orden->detalles->filter(function ($producto) {
                    $presentacion = strtolower($producto['producto']['presentacion']['presentacion']);

                    return strpos($presentacion, 'cubeta') !== false || strpos($presentacion, 'caneca') !== false;
                });
                $cobroCanecasCubetas = $canecasCubetasDetalles->sum(function ($detalle) {
                    return $detalle['cantidad'] * $detalle['precio'];
                });
                $cantidadCanecasCubetas = $canecasCubetasDetalles->sum('cantidad');

                if ($cantidadCanecasCubetas > 0) {
                    $guia = new Guia;
                    $guia->cantidad = $cantidadCanecasCubetas;
                    $guia->costo = Guia::COSTO * $cantidadCanecasCubetas;
                    $guia->cobrar = $cobroCanecasCubetas;
                    $guia->tipo = 'cc';
                    $orden->guias()->save($guia);
                }

                if ($data['cantidad'] > 0) {
                    $guia = new Guia;
                    $guia->cantidad = $data['cantidad'];
                    $guia->cobrar = $orden->total - $cobroCanecasCubetas;
                    $guia->costo = Guia::COSTO * $data['cantidad'];
                    $guia->tipo = 'paquetes';
                    $orden->guias()->save($guia);
                }

                $orden->fecha_fin_recolectada = now();
                $orden->estado = 'recolectada';
                $orden->fecha_vencimiento = $orden->tipo_pago_id == 2 ? now()->addDays($orden->cliente->credito_dias) : null;
                $orden->save();
                activity()->performedOn($orden)->causedBy(auth()->user())->withProperties($orden)->event('terminación')->log('Orden terminada');
            });
            Notification::make()
                ->color('success')
                ->title('Se ha terminado la recolección de la Orden #'.$orden->id)
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al terminar la recolección de la Orden')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function facturar($orden)
    {
        try {
            DB::transaction(function () use ($orden) {
                if ($orden->factura()->exists()) {
                    throw new Exception('La Orden ya ha sido facturada');
                }
                /* $res = FELController::facturaOrden($orden);
                if (! $res['resultado']) {
                    throw new Exception($res['descripcion_errores'][0]['mensaje_error']);
                } */
                $factura = new Factura;
                $factura->fel_tipo = $orden->tipo_pago_id == 2 ? 'FCAM' : 'FACT';
                $factura->fel_uuid = /* $res['uuid'] */'asfds';
                $factura->fel_serie = /* $res['serie'] */ 'asfds';
                $factura->fel_numero = /* $res['numero'] */ 'asfds';
                $factura->fel_fecha = /* $res['fecha'] */ now();
                $factura->user_id = auth()->user()->id;
                $factura->tipo = 'factura';
                $orden->factura()->save($factura);
                activity()->performedOn($orden)->causedBy(auth()->user())->withProperties($orden)->event('facturación')->log('Orden facturada');
                redirect()->back();
            });
            Notification::make()
                ->color('success')
                ->title('Se ha facturado la Orden #'.$orden->id)
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al facturar la Orden')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function anular($data, Orden $orden)
    {
        try {
            DB::transaction(function () use ($data, $orden) {
                if ($orden->guias()->exists() && $orden->tipo_envio->value == 'guatex') {
                    foreach ($orden->guias as $guia) {
                        GUATEXController::eliminarGuia($guia->tracking);
                    }
                }
                if ($orden->tipo_pago_id == 2) {
                    UserController::restarSaldo($orden->cliente_id, $orden->total);
                }
                if (in_array($orden->estado->value, ['completada', 'confirmada', 'recolectada', 'preparada'])) {
                    self::sumarInventario($orden, 'Orden anulada');
                }
                if ($orden->factura()->exists()) {
                    $res = FELController::anularFacturaOrden($orden, $data['motivo']);
                    if (! $res['resultado']) {
                        throw new Exception($res['descripcion_errores'][0]['mensaje_error']);
                    }
                    $factura = new Factura;
                    $factura->fel_tipo = $orden->tipo_pago_id == 2 ? 'FCAM' : 'FACT';
                    $factura->fel_uuid = $res['uuid'];
                    $factura->fel_serie = $res['serie'];
                    $factura->fel_numero = $res['numero'];
                    $factura->fel_fecha = $res['fecha'];
                    $factura->user_id = auth()->user()->id;
                    $factura->tipo = 'anulacion';
                    $factura->motivo = $data['motivo'];
                    $orden->factura()->save($factura);
                    $orden->factura()->delete();
                }
                $orden->estado = 'anulada';
                $orden->motivo = $data['motivo'];
                $orden->fecha_anulada = now();
                $orden->anulo_id = auth()->user()->id;
                $orden->save();
                activity()->performedOn($orden)->causedBy(auth()->user())->withProperties($orden)->event('anulación')->log('Orden anulada');
            });
            Notification::make()
                ->color('success')
                ->title('Orden anulada correctamente')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al anular la Orden')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function preparar($orden)
    {
        try {
            DB::transaction(function () use ($orden) {
                $orden->fecha_preparada = now();
                $orden->empaquetador_id = auth()->user()->id;
                $orden->costo_envio = $orden->guias->sum('costo');
                $orden->save();
                activity()->performedOn($orden)->causedBy(auth()->user())->withProperties($orden)->event('preparación')->log('Orden preparada');
            });
            if ($orden->tipo_envio->value == 'propio') {
                $orden->estado = 'preparada';
                $orden->save();
            }
            Notification::make()
                ->color('success')
                ->title('Se ha asignado la Orden #'.$orden->id)
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al preparar la Orden')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function devolver($data, Orden $orden)
    {
        try {
            DB::transaction(function () use ($data, $orden) {
                $estado = 'devuelta';

                if ($orden->guias()->exists() && $orden->tipo_envio->value == 'guatex') {
                    foreach ($orden->guias as $guia) {
                        GUATEXController::eliminarGuia($guia->tracking);
                    }
                }
                if ($orden->tipo_pago_id == 2) {
                    UserController::restarSaldo($orden->cliente_id, $orden->total);
                }
                foreach ($orden->detalles as $detalle) {
                    if ($detalle->devuelto > 0) {
                        $inventario = Inventario::firstOrCreate(
                            [
                                'producto_id' => $detalle->producto_id,
                                'bodega_id' => $orden->bodega_id,
                            ],
                            [
                                'existencia' => 0,
                            ]
                        );
                        $existenciaInicial = $inventario ? $inventario->existencia : 0;
                        $cantidadTotal = $detalle->devuelto - $detalle->devuelto_mal;
                        $inventario->existencia += $cantidadTotal;
                        $inventario->save();
                        Kardex::registrar(
                            $detalle->producto_id,
                            $orden->bodega_id,
                            $cantidadTotal,
                            $existenciaInicial,
                            $inventario->existencia,
                            'entrada',
                            $orden,
                            'Devolución de orden'
                        );
                    }

                    if ($detalle->devuelto_mal > 0) {
                        $inventario = Inventario::firstOrCreate(
                            [
                                'producto_id' => $detalle->producto_id,
                                'bodega_id' => Bodega::MAL_ESTADO,
                            ],
                            [
                                'existencia' => 0,
                            ]
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
                            $orden,
                            'Devolución de orden'
                        );
                    }

                    if (($detalle->cantidad) != $detalle->devuelto) {
                        $estado = 'parcialmente devuelta';
                    }
                }

                if ($orden->factura()->exists()) {
                    $res = FELController::devolverFacturaOrden($orden, $data['motivo']);
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
                    $orden->factura()->save($factura);
                    $orden->factura()->delete();
                }

                $orden->estado = $estado;
                $orden->motivo = $data['motivo'];
                $orden->apoyo = $data['apoyo'];
                $orden->fecha_devuelta = now();
                $orden->devolvio_id = auth()->user()->id;
                $orden->save();

                activity()->performedOn($orden)->causedBy(auth()->user())->withProperties($orden)->event('devolución')->log('Orden devuelta');
            });

            Notification::make()
                ->color('success')
                ->title('Orden devuelta correctamente')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al devolver la Orden')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function anularGuias(Orden $orden)
    {
        try {
            DB::transaction(function () use ($orden) {
                if ($orden->tipo_envio->value == 'guatex') {
                    foreach ($orden->guias as $guia) {
                        GUATEXController::eliminarGuia($guia->tracking);
                    }
                }
                $orden->guias()->delete();
                $orden->estado = 'confirmada';
                $orden->fecha_preparada = null;
                $orden->empaquetador_id = null;
                $orden->costo_envio = 0;
                $orden->save();
            });
            activity()->performedOn($orden)->causedBy(auth()->user())->withProperties($orden)->event('anulación de guías')->log('Se anularon las guías de la Orden');
            Notification::make()
                ->color('success')
                ->title('Se han anulado las guías de la Orden #'.$orden->id)
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al anular guías')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function cotizacionOrden(Orden $orden)
    {
        try {
            DB::transaction(function () use ($orden) {
                $user = User::find($orden->cliente_id);
                if ($orden->tipo_pago_id == 2 && $orden->total > ($user->credito - $user->saldo)) {
                    throw new Exception('El cliente no tiene suficiente crédito para realizar la compra');
                }
                $orden->estado = 'creada';
                $orden->save();
                if ($orden->tipo_pago_id == 2) {
                    UserController::sumarSaldo($orden->cliente_id, $orden->total);
                }
            });
            activity()->performedOn($orden)->causedBy(auth()->user())->withProperties($orden)->event('conversion')->log('Cotización a Orden');
            Notification::make()
                ->color('success')
                ->title('Se ha convertido la cotización a Orden #'.$orden->id)
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al completar la Orden')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function enviar(Orden $orden)
    {
        try {
            DB::transaction(function () use ($orden) {
                $orden->fecha_enviada = now();
                $orden->estado = 'enviada';
                $orden->piloto_id = auth()->user()->id;
                $orden->save();
            });
            activity()->performedOn($orden)->causedBy(auth()->user())->withProperties($orden)->event('envío')->log('Orden enviada');
            Notification::make()
                ->color('success')
                ->title('Se ha enviado la Orden #'.$orden->id)
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al enviar la Orden')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function finalizar($data, Orden $orden)
    {
        try {
            DB::transaction(function () use ($data, $orden) {
                $orden->recibio = $data['recibio'];
                $orden->estado_envio = 'Entregado';
                $orden->fecha_finalizada = now();
                $orden->estado = 'finalizada';
                $orden->save();
            });
            activity()->performedOn($orden)->causedBy(auth()->user())->withProperties($orden)->event('finalización')->log('Orden finalizada');
            Notification::make()
                ->color('success')
                ->title('Se ha finalizado la Orden #'.$orden->id)
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al finalizar la Orden')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function liquidar(Orden $orden)
    {
        try {
            DB::transaction(function () use ($orden) {
                $ordenesAntiguas = Orden::where('estado', 'enviada')
                    ->where('tipo_envio', 'PROPIO')
                    ->whereNot('tipo_pago_id', 2)
                    ->where('created_at', '<', Carbon::now()->subHours(30))
                    ->orderBy('created_at', 'asc')
                    ->count();

                if ($ordenesAntiguas > 0) {
                    throw new Exception('Se deben finalizar las ordenes de reparto PROPIO que están pendientes en enviadas.');
                }

                if ($orden->pago_validado == 0 || round(floatval($orden->total), 0) != round(floatval($orden->pagos->sum('total')), 0)) {
                    throw new Exception('No se ha completado el proceso de pago de la Orden');
                }
                $orden->fecha_liquidada = now();
                $orden->liquido_id = auth()->user()->id;
                $orden->estado = 'liquidada';
                $orden->save();
            });
            activity()->performedOn($orden)->causedBy(auth()->user())->withProperties($orden)->event('liquidación')->log('Orden liquidada');
            Notification::make()
                ->color('success')
                ->title('Se ha liquidado la Orden #'.$orden->id)
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al liquidar Orden')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function liquidarVarias()
    {
        try {
            DB::transaction(function () {
                $ordenes = Orden::where('estado', 'finalizada')
                    ->where('pago_validado', 1)
                    ->get();

                $ordenesValidadas = [];

                foreach ($ordenes as $orden) {
                    $totalOrden = round(floatval($orden->total), 0);
                    $totalPagos = round(floatval($orden->pagos->sum('total')), 0);

                    if ($totalOrden === $totalPagos) {
                        $orden->fecha_liquidada = now();
                        $orden->liquido_id = auth()->user()->id;
                        $orden->estado = 'liquidada';
                        $orden->save();

                        activity()->performedOn($orden)->causedBy(auth()->user())
                            ->withProperties($orden)
                            ->event('liquidación')
                            ->log('Orden liquidada automáticamente');

                        $ordenesValidadas[] = $orden->id;
                    }
                }

                if (count($ordenesValidadas) > 0) {
                    Notification::make()
                        ->color('success')
                        ->title('Órdenes Liquidadas')
                        ->body('Se han liquidado las órdenes: '.implode(', ', $ordenesValidadas))
                        ->success()
                        ->send();
                } else {
                    throw new Exception('No hay órdenes que cumplan con los requisitos para ser liquidadas.');
                }
            });
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al liquidar órdenes')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function actualizarBackorder()
    {
        $ordenes = Orden::where('estado', 'backorder')->whereNotIn('tipo_pago_id', [4, 2, 12])
            ->orderBy('created_at')
            ->orderByRaw('CASE WHEN prefechado IS NULL THEN 1 ELSE 0 END')
            ->orderBy('prefechado')
            ->get();

        $ordenesValidadas = [];
        foreach ($ordenes as $orden) {
            $validar = true;
            foreach ($orden->detalles as $detalle) {
                $producto_id = $detalle['producto_id'];
                $cantidad = $detalle['cantidad'];
                $existencia = Inventario::where('producto_id', $producto_id)->where('bodega_id', 1)->first()->existencia ?? 0;
                if ($existencia < $cantidad) {
                    $validar = false;
                    break;
                }
            }
            if ($validar) {
                $ordenesValidadas[] = $orden->id;
                self::confirmar($orden, 1);
            }
        }

        Notification::make()
            ->color('success')
            ->title('Se han actualizado las órdenes backorder')
            ->body('Se han confirmado o completado las órdenes ('.implode(', ', $ordenesValidadas).')')
            ->success()
            ->send();
    }
}
