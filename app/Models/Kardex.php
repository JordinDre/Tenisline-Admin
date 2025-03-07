<?php

namespace App\Models;

use App\Enums\EventoKardexStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Kardex extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'evento' => EventoKardexStatus::class,
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kardexable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function registrar($productoId, $bodegaId, $cantidad, $existenciaInicial, $existenciaFinal, $evento, $modelo, $descripcion = null)
    {
        Kardex::create([
            'existencia_inicial' => $existenciaInicial,
            'cantidad' => $cantidad,
            'existencia_final' => $existenciaFinal,
            'producto_id' => $productoId,
            'bodega_id' => $bodegaId,
            'user_id' => auth()->user()->id,
            'evento' => $evento,
            'description' => $descripcion,
            'kardexable_type' => get_class($modelo),
            'kardexable_id' => $modelo->id,
        ]);
    }
}
