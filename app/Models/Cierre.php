<?php

namespace App\Models;

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
        return Venta::where('bodega_id', $this->bodega_id)
            ->whereIn('estado', ['creada', 'liquidada'])
            ->where('created_at', '>=', $this->apertura)
            ->when($this->cierre, fn ($q) => $q->where('created_at', '<=', $this->cierre))
            ->pluck('id')
            ->map(fn ($id) => "Venta #{$id}");
    }

    public function getVentasDetallesAttribute()
    {
        return Venta::with(['detalles.producto'])
            ->where('bodega_id', $this->bodega_id)
            ->whereIn('estado', ['creada', 'liquidada'])
            ->where('created_at', '>=', $this->apertura)
            ->when($this->cierre, fn ($q) => $q->where('created_at', '<=', $this->cierre))
            ->get();
    }

    public function getTotalVentasAttribute()
    {
        return Venta::where('bodega_id', $this->bodega_id)
            ->whereIn('estado', ['creada', 'liquidada'])
            ->where('created_at', '>=', $this->apertura)
            ->when($this->cierre, fn ($q) => $q->where('created_at', '<=', $this->cierre))
            ->sum('total');
    }

    public function getTotalTenisAttribute()
    {
        return VentaDetalle::whereHas('venta', function ($query) {
            $query->where('bodega_id', $this->bodega_id)
                ->whereIn('estado', ['creada', 'liquidada'])
                ->where('created_at', '>=', $this->apertura)
                ->when($this->cierre, fn ($q) => $q->where('created_at', '<=', $this->cierre));
        })->count('producto_id');
    }

    public function getResumenPagosAttribute()
    {
        $ventas = Venta::where('bodega_id', $this->bodega_id)
            ->whereIn('estado', ['creada', 'liquidada'])
            ->where('created_at', '>=', $this->apertura)
            ->when($this->cierre, fn ($q) => $q->where('created_at', '<=', $this->cierre))
            ->pluck('id');

        $pagos = Pago::whereIn('pagable_id', $ventas)
            ->where('pagable_type', Venta::class)
            ->with('tipoPago')
            ->get();

        return $pagos
            ->groupBy(fn ($pago) => $pago->tipoPago->tipo_pago ?? 'Desconocido')
            ->map(fn ($group) => 'Q'.number_format($group->sum('monto'), 2))
            ->map(fn ($monto, $tipo) => "{$tipo}: {$monto}")
            ->values()
            ->toArray();
    }
}
