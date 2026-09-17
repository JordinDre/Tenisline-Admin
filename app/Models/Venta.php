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
            'requiere_validacion_pago' => 'boolean',
            'requiere_evidencia_oferta20' => 'boolean',
            'foto_evidencia_lat' => 'decimal:7',
            'foto_evidencia_lng' => 'decimal:7',
            'foto_evidencia_capturada_en' => 'datetime',
            'requiere_codigo_confirmacion' => 'boolean',
            'codigo_generado_en' => 'datetime',
            'codigo_confirmado_en' => 'datetime',
        ];
    }

    /**
     * Motivos legibles por los que la venta está pendiente de validación.
     */
    public function motivosPendientes(): array
    {
        $motivos = [];

        if ($this->requiere_validacion_pago) {
            $motivos[] = 'Pago pendiente de validar';
        }

        if ($this->requiere_evidencia_oferta20) {
            $motivos[] = $this->foto_evidencia_oferta20
                ? 'Descuento aplicado (foto subida, falta validar)'
                : 'Descuento aplicado (falta foto de evidencia)';
        }

        if ($this->requiere_codigo_confirmacion) {
            $motivos[] = match (true) {
                (bool) $this->codigo_confirmado_en => 'Descuento aplicado (código confirmado, falta validar)',
                blank($this->codigo_confirmacion) => 'Descuento aplicado (falta que el asesor envíe el código)',
                $this->codigoExpirado() => 'Descuento aplicado (código expirado, debe reenviarse)',
                default => 'Descuento aplicado (esperando que el cliente confirme el código)',
            };
        }

        return $motivos;
    }

    /**
     * Genera y asigna un código de confirmación de 6 dígitos para el cliente.
     */
    public function generarCodigoConfirmacion(): string
    {
        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->codigo_confirmacion = $codigo;
        $this->codigo_generado_en = now();

        return $codigo;
    }

    public function codigoExpirado(): bool
    {
        return $this->codigo_generado_en !== null && $this->codigo_generado_en->addMinutes(30)->isPast();
    }

    /**
     * Texto listo para copiar y enviar al cliente con el código de confirmación.
     */
    public function mensajeConfirmacionCliente(): string
    {
        $totalOriginal = $this->detalles()
            ->with('producto')
            ->get()
            ->sum(fn (VentaDetalle $detalle) => ($detalle->oferta_cliente_20 || $detalle->aplica_liquidacion)
                ? round(((float) ($detalle->producto?->precio_venta ?? 0)) * $detalle->cantidad, 2)
                : (float) $detalle->subtotal);

        $totalAPagar = (float) $this->total;
        $descuento = round($totalOriginal - $totalAPagar, 2);

        return sprintf(
            'Hola %s, se ha aplicado un descuento a tu compra de Q%s en TenisLine %s. Descuento de Q%s, total a pagar Q%s. Tu código de confirmación es: %s. Compártelo para completar la compra.',
            $this->cliente?->name ?? 'cliente',
            number_format($totalOriginal, 2),
            $this->bodega?->bodega ?? '',
            number_format($descuento, 2),
            number_format($totalAPagar, 2),
            $this->codigo_confirmacion,
        );
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

    public function guias(): MorphMany
    {
        return $this->morphMany(Guia::class, 'guiable');
    }
}
