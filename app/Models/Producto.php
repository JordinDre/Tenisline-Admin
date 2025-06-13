<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Producto extends Model
{
    use HasFactory, SoftDeletes;
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
        'imagenes' => 'array',
        'videos' => 'array',
        'documentos' => 'array',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    /*  public function presentacion(): BelongsTo
     {
         return $this->belongsTo(Presentacion::class);
     } */

    public function escalas(): HasMany
    {
        return $this->hasMany(Escala::class);
    }

    /*  public function enlinea(): HasOne
     {
         return $this->hasOne(Escala::class)->where('escala', 'ENLINEA');
     } */

    public function inventario(): HasMany
    {
        return $this->hasMany(Inventario::class);
    }

    /*  public function comercios(): BelongsToMany
     {
         return $this->belongsToMany(Comercio::class)->using(ComercioProducto::class);
     } */

    public function observaciones(): MorphMany
    {
        return $this->morphMany(Observacion::class, 'observacionable');
    }

}
