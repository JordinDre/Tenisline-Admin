<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CajaChica extends Model
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

    public function pagos(): MorphMany
    {
        return $this->morphMany(Pago::class, 'pagable');
    }

    public function bodega(): BelongsTo
    {
        return $this->BelongsTo(Bodega::class, 'bodega_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proveedor_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
