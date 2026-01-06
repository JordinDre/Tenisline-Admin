<?php

namespace App\Models;

use App\Enums\EstadoVentaStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Kenepa\ResourceLock\Models\Concerns\HasLocks;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Venta extends Model
{
    use HasFactory;
    use HasLocks;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Se ha registrado un {$eventName}")
            ->dontSubmitEmptyLogs();
    }

    public const ESTADOS_EXCLUIDOS = [
        'anulada',
        'devuelta',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'estado' => EstadoVentaStatus::class,
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id')->withTrashed();
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id')->withTrashed();
    }

    public function tipo_pago(): BelongsTo
    {
        return $this->belongsTo(TipoPago::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function pagos(): MorphMany
    {
        return $this->morphMany(Pago::class, 'pagable');
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class);
    }

    public function factura(): MorphOne
    {
        return $this->morphOne(Factura::class, 'facturable')->where('tipo', 'factura');
    }

    public function anulacion(): MorphOne
    {
        return $this->morphOne(Factura::class, 'facturable')->where('tipo', 'anulacion');
    }

    public function devolucion(): MorphOne
    {
        return $this->morphOne(Factura::class, 'facturable')->where('tipo', 'devolucion');
    }

    public function cierreDia(): BelongsTo
    {
        return $this->belongsTo(CierreDia::class);
    }

    /**
     * Verifica si la venta debe mostrar información de factura
     * No debe mostrar factura si: comp == false && facturar_cf == true && tipo_pago_id == 1
     */
    public function debeOcultarFactura(): bool
    {
        // Si comp es true, no ocultar
        if ($this->comp == true) {
            return false;
        }

        // Si facturar_cf es false, no ocultar
        if ($this->facturar_cf != true) {
            return false;
        }

        // Verificar que exista al menos un pago y que el tipo_pago_id sea 1
        $primerPago = $this->relationLoaded('pagos')
            ? $this->pagos->first()
            : $this->pagos()->first();

        return $primerPago !== null && $primerPago->tipo_pago_id == 1;
    }
}
