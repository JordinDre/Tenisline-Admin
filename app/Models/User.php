<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, SoftDeletes;
    use HasRoles;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Se ha registrado un {$eventName}")
            ->dontSubmitEmptyLogs();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $roles = $this->roles->pluck('name')->toArray();

        if (in_array('cliente', $roles) || in_array('proveedor', $roles)) {
            $allowedRoles = array_diff($roles, ['cliente', 'proveedor']);
            if (empty($allowedRoles)) {
                return false;
            }
        }

        return true;
    }

    public const ASESOR_ROLES = [
        'asesor telemarketing',
        'asesor preventa',
        'asesor venta directa',
    ];

    public const SUPERVISOR_ROLES = [
        'supervisor venta directa',
        'supervisor telemarketing',
        'supervisor preventa',
    ];

    public const ORDEN_ROLES = [
        'asesor telemarketing',
        'asesor preventa',
    ];

    public const SUPERVISORES_ORDEN = [
        'supervisor telemarketing',
        'supervisor preventa',
    ];

    public const SUPERVISORES_VENTA = [
        'supervisor venta directa',
    ];

    public const VENTA_ROLES = [
        'asesor venta directa',
    ];

    public const ROLES_ADMIN = ['super_admin', 'administrador', 'gerente'];

    protected $guarded = [];
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    /* protected $fillable = [
        'name',
        'email',
        'password',
    ]; */

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    /* protected $hidden = [
        'password',
        'remember_token',
    ]; */

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'imagenes' => 'array',
        ];
    }

    public function asesores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'asesor_user', 'user_id', 'asesor_id')->using(AsesorUser::class);
    }

    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'asesor_user', 'asesor_id', 'user_id')->using(AsesorUser::class)->withTimestamps();
    }

    public function tipo_pagos(): BelongsToMany
    {
        return $this->belongsToMany(TipoPago::class)->using(TipoPagoUser::class);
    }

    public function comercios(): BelongsToMany
    {
        return $this->belongsToMany(Comercio::class)->using(ComercioUser::class);
    }

    public function supervisores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'supervisor_user', 'user_id', 'supervisor_id')->using(SupervisorUser::class);
    }

    public function asesoresSupervisados(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'supervisor_user', 'supervisor_id', 'user_id')->using(SupervisorUser::class);
    }

    public function bodegas(): BelongsToMany
    {
        return $this->belongsToMany(Bodega::class)->using(BodegaUser::class);
    }

    public function direcciones(): HasMany
    {
        return $this->hasMany(Direccion::class);
    }

    public function municipios(): BelongsToMany
    {
        return $this->belongsToMany(Municipio::class)->using(MunicipioUser::class);
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class, 'cliente_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }

    public function carrito(): HasMany
    {
        return $this->hasMany(Carrito::class);
    }

    public function observaciones(): MorphMany
    {
        return $this->morphMany(Observacion::class, 'observacionable');
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(Seguimiento::class, 'user_id');
    }

    public function ultimoSeguimiento()
    {
        return $this->hasOne(Seguimiento::class)->latestOfMany();
    }

    public function metas(): HasMany
    {
        return $this->hasMany(Meta::class);
    }

    public function creditosOrdenesPendientes(): HasMany
    {
        return $this->hasMany(Orden::class, 'cliente_id')
            ->where('tipo_pago_id', 2)
            ->whereIn('estado', ['creada', 'backorder', 'completada', 'confirmada', 'recolectada', 'preparada', 'enviada', 'finalizada'])
            ->whereRaw('total > (SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE pagable_type = ? AND pagable_id = ordens.id)', [Orden::class]);
    }

    public function creditosOrdenesAtrasados(): HasMany
    {
        return $this->creditosOrdenesPendientes()
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<', now()->endOfDay());
    }

    public function creditosVentasPendientes(): HasMany
    {
        return $this->hasMany(Venta::class, 'cliente_id')
            ->where('tipo_pago_id', 2)
            ->whereIn('estado', ['creada'])
            ->whereRaw('total > (SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE pagable_type = ? AND pagable_id = ventas.id)', [Venta::class]);
    }

    public function creditosVentasAtrasados(): HasMany
    {
        return $this->creditosVentasPendientes()
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<', now()->endOfDay());
    }

    public function pagosOrdenes(): HasManyThrough
    {
        return $this->hasManyThrough(
            Pago::class,          // Modelo final (Pagos)
            Orden::class,         // Modelo intermedio (Órdenes)
            'cliente_id',         // Foreign key en `ordenes` que apunta a `users`
            'pagable_id',         // Foreign key en `pagos` que apunta a `ordenes`
            'id',                 // Local key en `users`
            'id'                  // Local key en `ordenes`
        )->where('pagable_type', Orden::class); // Filtramos para incluir solo pagos de órdenes
    }

    public function pagosVentas(): HasManyThrough
    {
        return $this->hasManyThrough(
            Pago::class,          // Modelo final (Pagos)
            Venta::class,         // Modelo intermedio (Órdenes)
            'cliente_id',         // Foreign key en `ordenes` que apunta a `users`
            'pagable_id',         // Foreign key en `pagos` que apunta a `ordenes`
            'id',                 // Local key en `users`
            'id'                  // Local key en `ordenes`
        )->where('pagable_type', Venta::class);
    }
}
