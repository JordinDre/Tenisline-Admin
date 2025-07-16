<?php

namespace App\Models;

use App\Enums\EnvioStatus;
use App\Enums\EstadoOrdenStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Kenepa\ResourceLock\Models\Concerns\HasLocks;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Orden extends Model
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

    protected $guarded = [];

    public const ESTADOS_EXCLUIDOS = [
        'anulada',
        'devuelta',
        'cotizacion',
        'parcialmente_devuelta',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoOrdenStatus::class,
            'tipo_envio' => EnvioStatus::class,
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function direccion(): BelongsTo
    {
        return $this->belongsTo(Direccion::class, 'direccion_id');
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class);
    }

    public function empaquetador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'empaquetador_id');
    }

    public function recolector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recolector_id');
    }

    public function tipo_pago(): BelongsTo
    {
        return $this->belongsTo(TipoPago::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(OrdenDetalle::class);
    }

    public function pagos(): MorphMany
    {
        return $this->morphMany(Pago::class, 'pagable');
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

    public function guias(): MorphMany
    {
        return $this->morphMany(Guia::class, 'guiable');
    }
}
