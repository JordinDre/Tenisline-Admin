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
}
