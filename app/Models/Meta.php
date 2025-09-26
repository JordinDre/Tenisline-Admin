<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Meta extends Model
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

    protected $casts = [
        'meta' => 'decimal:2',
        'mes' => 'integer',
        'anio' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class);
    }

    /**
     * Obtener el alcance de la meta en porcentaje
     */
    public function getAlcanceAttribute(): float
    {
        if ($this->meta <= 0) {
            return 0;
        }

        // Obtener ventas de la bodega para el mes y año
        $ventas = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->whereYear('ventas.created_at', $this->anio)
            ->whereMonth('ventas.created_at', $this->mes)
            ->where('ventas.bodega_id', $this->bodega_id)
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->sum('precio');

        return round(($ventas * 100) / $this->meta, 2);
    }

    /**
     * Obtener las ventas reales de la bodega para el período
     */
    public function getVentasRealesAttribute(): float
    {
        return VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->whereYear('ventas.created_at', $this->anio)
            ->whereMonth('ventas.created_at', $this->mes)
            ->where('ventas.bodega_id', $this->bodega_id)
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->sum('precio');
    }

    /**
     * Verificar si la meta se ha cumplido
     */
    public function getCumplidaAttribute(): bool
    {
        return $this->alcance >= 100;
    }

    public function getProyeccionAttribute(): float
    {
        $year = $this->anio;
        $month = $this->mes;
        $bodegaId = $this->bodega_id;

        $hoy = now();
        
        // Si es el mes y año actual, usar los días transcurridos reales
        if ($year == $hoy->year && $month == $hoy->month) {
            $diasTranscurridos = $hoy->day;
        } else {
            // Para meses pasados o futuros, usar todos los días del mes
            $diasTranscurridos = $hoy->setYear($year)->setMonth($month)->daysInMonth;
        }

        $totalDiasMes = $hoy->setYear($year)->setMonth($month)->daysInMonth;

        // Obtener el total de ventas reales para el período
        $totalVentas = \App\Models\VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->where('ventas.bodega_id', $bodegaId)
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->sum('venta_detalles.precio');

        // Proyectar basándose en el promedio diario de ventas
        return $diasTranscurridos > 0
            ? ($totalVentas / $diasTranscurridos) * $totalDiasMes
            : 0;
    }

    public function getProyeccion2Attribute(): float
    {
        if ($this->meta <= 0) {
            return 0;
        }

        $proyeccion = $this->proyeccion;

        return round(($proyeccion * 100) / $this->meta, 2);
    }

    public function getRendimientoAttribute(): float
    {
        $year = $this->anio;
        $month = $this->mes;
        $bodegaId = $this->bodega_id;

        $detalles = \App\Models\VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->where('ventas.bodega_id', $bodegaId)
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->get();

        $total = $detalles->sum('precio');
        $costo = $detalles->sum(fn ($d) => $d->cantidad * ($d->producto->precio_costo ?? 0));

        if ($total <= 0) {
            return 0;
        }

        return round((($total - $costo) / $total) * 100, 2); // porcentaje
    }
}
