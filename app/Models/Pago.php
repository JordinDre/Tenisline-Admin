<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pago extends Model
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

    public function pagable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tipoPago(): BelongsTo
    {
        return $this->belongsTo(TipoPago::class);
    }

    public function banco(): BelongsTo
    {
        return $this->belongsTo(Banco::class);
    }

    public function valeRegalo(): BelongsTo
    {
        return $this->belongsTo(ValeRegalo::class);
    }

    protected static function booted(): void
    {
        static::created(function (Pago $pago) {
            if ($pago->vale_regalo_id) {
                $vale = ValeRegalo::find($pago->vale_regalo_id);
                if ($vale) {
                    $esVenta = $pago->pagable_type === Venta::class || $pago->pagable_type === 'App\Models\Venta';
                    $ventaExiste = $esVenta && $pago->pagable_id && Venta::where('id', $pago->pagable_id)->exists();

                    $vale->update([
                        'estado' => 'canjeado',
                        'pago_id' => $pago->id,
                        'venta_id' => $ventaExiste ? $pago->pagable_id : null,
                        'fecha_canje' => now(),
                    ]);
                }
            }
        });

        static::updated(function (Pago $pago) {
            if ($pago->isDirty('vale_regalo_id')) {
                $oldValeId = $pago->getOriginal('vale_regalo_id');
                if ($oldValeId) {
                    $oldVale = ValeRegalo::find($oldValeId);
                    if ($oldVale) {
                        $oldVale->update([
                            'estado' => 'disponible',
                            'pago_id' => null,
                            'venta_id' => null,
                            'fecha_canje' => null,
                        ]);
                    }
                }

                if ($pago->vale_regalo_id) {
                    $newVale = ValeRegalo::find($pago->vale_regalo_id);
                    if ($newVale) {
                        $esVenta = $pago->pagable_type === Venta::class || $pago->pagable_type === 'App\Models\Venta';
                        $ventaExiste = $esVenta && $pago->pagable_id && Venta::where('id', $pago->pagable_id)->exists();

                        $newVale->update([
                            'estado' => 'canjeado',
                            'pago_id' => $pago->id,
                            'venta_id' => $ventaExiste ? $pago->pagable_id : null,
                            'fecha_canje' => now(),
                        ]);
                    }
                }
            }
        });

        static::deleted(function (Pago $pago) {
            if ($pago->vale_regalo_id) {
                $vale = ValeRegalo::find($pago->vale_regalo_id);
                if ($vale) {
                    $vale->update([
                        'estado' => 'disponible',
                        'pago_id' => null,
                        'venta_id' => null,
                        'fecha_canje' => null,
                    ]);
                }
            }
        });
    }
}
