<?php

namespace App\Models;

use App\Enums\EstadoTrasladoStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kenepa\ResourceLock\Models\Concerns\HasLocks;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Traslado extends Model
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

    protected function casts(): array
    {
        return [
            'estado' => EstadoTrasladoStatus::class,
        ];
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(TrasladoDetalle::class);
    }

    public function entrada(): BelongsTo
    {
        return $this->belongsTo(Bodega::class, 'entrada_id');
    }

    public function salida(): BelongsTo
    {
        return $this->belongsTo(Bodega::class, 'salida_id');
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emisor_id');
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receptor_id');
    }

    public function piloto(): BelongsTo
    {
        return $this->belongsTo(User::class, 'piloto_id');
    }
}
