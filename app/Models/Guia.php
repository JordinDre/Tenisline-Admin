<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Guia extends Model
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

    protected $casts = [
        'hijas' => 'array',
    ];

    protected $guarded = [];

    public const COSTO = 33;

    public const ENVIO = 35;

    public const ENVIO_GRATIS = 300;

    public const COSTO_DEVOLUCION = 16.5;

    public function guiable(): MorphTo
    {
        return $this->morphTo();
    }
}
