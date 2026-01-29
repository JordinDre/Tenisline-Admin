<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Cierre extends Model
{
    use HasFactory;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Se ha registrado un {$eventName}")
            ->dontSubmitEmptyLogs();
    }

    protected $guarded = [];

    public function pagos()
    {
        return $this->morphMany(Pago::class, 'pagable');
    }

    /**
     * Ventas asociadas al cierre
     */
    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getVentasIdsAttribute()
    {
        return Venta::with(['detalles.producto'])
            ->where('bodega_id', $this->bodega_id)
            ->where(function ($q) {
                $q->whereIn('estado', ['creada', 'liquidada'])
                    ->orWhere(function ($subQ) {
                        $subQ->where('estado', 'parcialmente_devuelta')
                            ->whereHas('detalles.producto', fn ($q2) => $q2->where('devuelto', 0));
                    });
            })
            ->where('created_at', '>=', $this->apertura)
            ->when($this->cierre, fn ($q) => $q->where('created_at', '<=', $this->cierre))
            ->pluck('id')
            ->map(fn ($id) => "Venta #{$id}");
    }

    public function getVentasDetallesAttribute()
    {
        return Venta::with(['detalles.producto.marca'])
            ->where('bodega_id', $this->bodega_id)
            ->where(function ($q) {
                $q->whereIn('estado', ['creada', 'liquidada'])
                    ->orWhere(function ($subQ) {
                        $subQ->where('estado', 'parcialmente_devuelta')
                            ->whereHas('detalles.producto', fn ($q2) => $q2->where('devuelto', 0));
                    });
            })
            ->where('created_at', '>=', $this->apertura)
            ->when($this->cierre, fn ($q) => $q->where('created_at', '<=', $this->cierre))
            ->get();
    }

    public function getTotalVentasAttribute()
    {
        return Venta::with(['detalles.producto'])
            ->where('bodega_id', $this->bodega_id)
            ->where(function ($q) {
                $q->whereIn('estado', ['creada', 'liquidada'])
                    ->orWhere(function ($subQ) {
                        $subQ->where('estado', 'parcialmente_devuelta')
                            ->whereHas('detalles.producto', fn ($q2) => $q2->where('devuelto', 0));
                    });
            })
            ->where('created_at', '>=', $this->apertura)
            ->when($this->cierre, fn ($q) => $q->where('created_at', '<=', $this->cierre))
            ->sum('total');
    }

    public function getTotalTenisAttribute()
    {
        return VentaDetalle::whereHas('venta', function ($query) {
            $query->where('bodega_id', $this->bodega_id)
                ->where('created_at', '>=', $this->apertura)
                ->when($this->cierre, fn ($q) => $q->where('created_at', '<=', $this->cierre))
                ->where(function ($q) {
                    $q->whereIn('estado', ['creada', 'liquidada'])
                        ->orWhere(function ($subQ) {
                            $subQ->where('estado', 'parcialmente_devuelta');
                        });
                });
        })
            ->where(function ($detalle) {
                $detalle->whereHas('venta', function ($q) {
                    $q->where('estado', '!=', 'parcialmente_devuelta');
                })
                    ->orWhere('devuelto', 0);
            })
            ->count('producto_id');
    }

    public function getDatosCajaChicaAttribute()
    {
        $query = CajaChica::with('pagos', 'usuario')
            ->where('bodega_id', $this->bodega_id);

        if ($this->tieneVentaContado()) {
            return $query
                ->where(function ($q) {
                    $q->whereBetween('created_at', [$this->apertura, $this->cierre ?? now()])
                    ->orWhere('aplicado_en_cierre_id', $this->id);
                })
                ->get();
        }
        return $query
            ->whereBetween('created_at', [$this->apertura, $this->cierre ?? now()])
            ->get();
    }

    public function getTotalCajaChicaAttribute()
    {
        if ($this->tieneVentaContado()) {
            return CajaChica::where('bodega_id', $this->bodega_id)
                ->where(function ($q) {
                    $q->whereBetween('created_at', [$this->apertura, $this->cierre ?? now()])
                    ->orWhere('aplicado_en_cierre_id', $this->id);
                })
                ->with('pagos')
                ->get()
                ->sum(fn ($caja) => $caja->pagos->sum('monto'));
        }

        return 0;
    }

    public function tieneVentaContado(): bool
    {
        return Venta::where('bodega_id', $this->bodega_id)
            ->whereBetween('created_at', [$this->apertura, $this->cierre ?? now()])
            ->whereHas('pagos', function ($q) {
                $q->whereHas('tipoPago', function ($t) {
                    $t->whereIn('tipo_pago', [
                        'CONTADO',
                    ]);
                })
                ->where('pagable_type', Venta::class);
            })
            ->exists();
    }

    public function getResumenPagosAttribute()
    {
        $ventas = Venta::where('bodega_id', $this->bodega_id)
            ->where(function ($q) {
                $q->whereIn('estado', ['creada', 'liquidada'])
                    ->orWhere(function ($subQ) {
                        $subQ->where('estado', 'parcialmente_devuelta')
                            ->whereHas('detalles.producto', fn ($q2) => $q2->where('devuelto', 0));
                    });
            })
            ->where('created_at', '>=', $this->apertura)
            ->when($this->cierre, fn ($q) => $q->where('created_at', '<=', $this->cierre))
            ->pluck('id');

        $pagos = Pago::whereIn('pagable_id', $ventas)
            ->where('pagable_type', Venta::class)
            ->with('tipoPago')
            ->get();

        $agrupados = $pagos
            ->groupBy(fn ($pago) => strtoupper($pago->tipoPago->tipo_pago ?? 'DESCONOCIDO'))
            ->map(fn ($group) => $group->sum('monto'))
            ->toArray();

        $orden = [
            'CONTADO',
            'TARJETA',
            'CUOTAS',
            'PAGO CONTRA ENTREGA',
        ];

        $resultado = [];

        foreach ($orden as $tipo) {
            $monto = $agrupados[$tipo] ?? 0;
            $resultado[$tipo] = 'Q' . number_format($monto, 2);
            unset($agrupados[$tipo]);
        }

        foreach ($agrupados as $tipo => $monto) {
            $resultado[$tipo] = 'Q' . number_format($monto, 2);
        }

        return collect($resultado)
            ->map(fn ($monto, $tipo) => "{$tipo}: {$monto}")
            ->values()
            ->toArray();
    }

    protected function resumenPagosLiquidacion(): Attribute
    {
        return Attribute::make(
            get: function () {
                $pagos = $this->pagos()
                    ->where('pagable_type', \App\Models\Cierre::class)
                    ->with('tipoPago')
                    ->get()
                    ->groupBy(fn ($pago) => $pago->tipoPago?->tipo_pago ?? 'Desconocido');

                $resumen = [];
                foreach ($pagos as $tipoPago => $coleccionPagos) {
                    $totalMonto = $coleccionPagos->sum('monto');
                    $resumen[] = "{$tipoPago}: Q".number_format($totalMonto, 2);
                }

                return $resumen;
            },
        );
    }

    /**
     * Obtiene el resumen de pagos esperados por tipo de pago
     */
    public function getResumenPagosEsperados()
    {
        $ventas = Venta::with(['detalles.producto'])
            ->where('bodega_id', $this->bodega_id)
            ->where(function ($q) {
                $q->whereIn('estado', ['creada', 'liquidada'])
                    ->orWhere(function ($subQ) {
                        $subQ->where('estado', 'parcialmente_devuelta')
                            ->whereHas('detalles.producto', fn ($q2) => $q2->where('devuelto', 0));
                    });
            })
            ->where('created_at', '>=', $this->apertura)
            ->when($this->cierre, fn ($q) => $q->where('created_at', '<=', $this->cierre))
            ->pluck('id');

        $pagos = Pago::whereIn('pagable_id', $ventas)
            ->where('pagable_type', Venta::class)
            ->with('tipoPago')
            ->get();

        return $pagos
            ->groupBy(fn ($pago) => $pago->tipoPago?->tipo_pago ?? 'Desconocido')
            ->map(fn ($group) => round($group->sum('monto'), 2))
            ->toArray();
    }

    /**
     * Obtiene los pagos ya realizados en la liquidación
     */
    public function getPagosLiquidacionRealizados()
    {
        $pagos = $this->pagos()
            ->where('pagable_type', \App\Models\Cierre::class)
            ->with('tipoPago')
            ->get()
            ->groupBy(fn ($pago) => $pago->tipoPago?->tipo_pago ?? 'Desconocido');

        return $pagos->map(fn ($coleccionPagos) => round($coleccionPagos->sum('monto'), 2))->toArray();
    }

    /**
     * Valida si se puede agregar un pago de liquidación
     */
    public function validarPagoLiquidacion($tipoPagoId, $monto)
    {
        $tipoPago = TipoPago::find($tipoPagoId);
        if (! $tipoPago) {
            throw new \Exception('Tipo de pago no válido');
        }

        $resumenEsperados = $this->getResumenPagosEsperados();
        $pagosRealizados = $this->getPagosLiquidacionRealizados();

        // 1. Validar que el tipo de pago esté en el resumen esperado
        if (! array_key_exists($tipoPago->tipo_pago, $resumenEsperados)) {
            throw new \Exception("El tipo de pago '{$tipoPago->tipo_pago}' no está en el resumen de pagos esperados");
        }

        // 2. Validar que el monto acumulado no exceda el esperado
        $montoEsperado = $resumenEsperados[$tipoPago->tipo_pago];
        $montoYaPagado = $pagosRealizados[$tipoPago->tipo_pago] ?? 0;
        $montoRestante = round($montoEsperado - $montoYaPagado, 2);

        if ($montoRestante <= 0) {
            throw new \Exception("El tipo de pago '{$tipoPago->tipo_pago}' ya está completamente pagado (Q".number_format($montoEsperado, 2).')');
        }

        if (round($monto, 2) > $montoRestante) {
            throw new \Exception('El monto Q'.number_format($monto, 2).' excede el monto restante Q'.number_format($montoRestante, 2)." para {$tipoPago->tipo_pago}");
        }

        return true;
    }

    /**
     * Obtiene los montos restantes por tipo de pago
     */
    public function getMontosRestantes()
    {
        $resumenEsperados = $this->getResumenPagosEsperados();
        $pagosRealizados = $this->getPagosLiquidacionRealizados();

        $restantes = [];
        foreach ($resumenEsperados as $tipoPago => $montoEsperado) {
            $montoYaPagado = $pagosRealizados[$tipoPago] ?? 0;
            $montoRestante = round($montoEsperado - $montoYaPagado, 2);

            if ($montoRestante > 0) {
                $restantes[$tipoPago] = $montoRestante;
            }
        }

        return $restantes;
    }

    /**
     * Verifica si se puede liquidar el cierre (todos los pagos completos)
     */
    public function puedeLiquidar()
    {
        $resumenEsperados = $this->getResumenPagosEsperados();
        $pagosRealizados = $this->getPagosLiquidacionRealizados();

        // Verificar que todos los tipos de pago esperados estén completos
        foreach ($resumenEsperados as $tipoPago => $montoEsperado) {
            if (! array_key_exists($tipoPago, $pagosRealizados)) {
                return false; // Falta este tipo de pago
            }

            if (round($pagosRealizados[$tipoPago], 2) < round($montoEsperado, 2)) {
                return false; // El monto no es suficiente
            }
        }

        return true;
    }

    public function getCajasChicasRelacionadasAttribute()
    {
        $inicioCambio = '2025-10-27';

        $apertura = $this->apertura;
        $cierre   = $this->cierre ?? now();

        $creadasEnRango = \App\Models\CajaChica::query()
            ->with('pagos')
            ->where('bodega_id', $this->bodega_id)
            ->whereDate('created_at', '>=', $inicioCambio)              
            ->whereBetween('created_at', [$apertura, $cierre])
            ->whereIn('estado', ['creada', 'confirmada'])
            ->get();

        $aplicadasEnEste = \App\Models\CajaChica::query()
            ->with('pagos')
            ->where('bodega_id', $this->bodega_id)
            ->where('aplicado_en_cierre_id', $this->id)
            ->get();

        $cajas = $creadasEnRango->merge($aplicadasEnEste)
            ->unique('id')
            ->sortBy('created_at');

        return $cajas->map(function ($caja) {
            $estadoLabel = match ($caja->estado) {
                'confirmada' => 'Confirmada',
                'creada'     => 'Pendiente',
                'anulada'    => 'Anulada',
                default      => ucfirst($caja->estado),
            };

            if ($caja->aplicado && $caja->aplicado_en_cierre_id === $this->id) {
                $estadoLabel = 'Aplicada en este cierre';
            }

            $monto = $caja->pagos->sum('monto');

            return "{$caja->detalle_gasto} (Q" . number_format($monto, 2) . ") - {$estadoLabel}";
        })->values()->toArray();
    }
}
