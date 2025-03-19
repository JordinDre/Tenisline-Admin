<?php

namespace App\Policies;

use App\Models\Orden;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrdenPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_orden');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Orden $orden): bool
    {
        return $user->can('view_orden');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return /* $user->can('create_orden') && */ $user->hasAnyRole(User::ORDEN_ROLES);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['creada', 'cotizacion', 'backorder'];

        return $user->can('update_orden') && in_array($orden->estado->value, $estadosPermitidos);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Orden $orden): bool
    {
        return false/* $user->can('delete_orden') */;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return false/* $user->can('delete_any_orden') */;
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Orden $orden): bool
    {
        return false/* $user->can('force_delete_orden') */;
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return false/* $user->can('force_delete_any_orden') */;
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Orden $orden): bool
    {
        return false/* $user->can('restore_orden') */;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return false/* $user->can('restore_any_orden') */;
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Orden $orden): bool
    {
        return false/* $user->can('replicate_orden') */;
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return false/* $user->can('reorder_orden') */;
    }

    public function products(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['creada', 'cotizacion', 'backorder'];

        return ! $user->can('products_orden') && in_array($orden->estado->value, $estadosPermitidos);
    }

    public function confirm(User $user, Orden $orden): bool
    {
        $estadosPermitidos = [/* 'creada', */ 'backorder'];

        return $user->can('confirm_orden') && in_array($orden->estado->value, $estadosPermitidos);
    }

    public function assign(User $user, Orden $orden): bool
    {
        $estadosPermitidos = [/* 'creada', */ 'backorder'];

        return $user->can('assign_orden') && in_array($orden->estado->value, $estadosPermitidos);
    }

    public function annular(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['creada', 'backorder', 'completada', 'confirmada', 'recolectada', 'preparada'];

        return $user->can('annular_orden') && in_array($orden->estado->value, $estadosPermitidos);
    }

    public function collect(User $user, Orden $orden): bool
    {
        $ordenEnProceso = Orden::where('recolector_id', $user->id)
            ->where('estado', 'confirmada')
            ->first();

        $estadosPermitidos = ['confirmada'];

        if (
            ! $user->can('collect_orden') ||
            ! in_array($orden->estado->value, $estadosPermitidos) ||
            ! is_null($ordenEnProceso) ||
            ! is_null($orden->recolector_id)
        ) {
            return false;
        }

        $bodegasUsuario = $user->bodegas()->pluck('bodegas.id')->toArray();
        if (! in_array($orden->bodega_id, $bodegasUsuario)) {
            return false;
        }

        $recolectores = User::role('recolector')
            ->whereHas('bodegas', function ($query) use ($orden) {
                $query->where('bodegas.id', $orden->bodega_id);
            })
            ->count();

        $ordenesPendientes = Orden::where('bodega_id', $orden->bodega_id)
            ->where('estado', 'confirmada')
            ->whereNull('recolector_id')
            ->orderBy('created_at', 'asc')
            ->take($recolectores)
            ->pluck('id')
            ->toArray();

        return in_array($orden->id, $ordenesPendientes);
    }

    public function factura(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['confirmada', 'recolectada', 'preparada', 'enviada', 'finalizada', 'liquidada', 'parcialmente devuelta'];

        return $user->can('factura_orden') && in_array($orden->estado->value, $estadosPermitidos) && $orden->factura()->exists() && ! $orden->comp;
    }

    public function facturar(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['confirmada'];

        return $user->can('facturar_orden') && in_array($orden->estado->value, $estadosPermitidos) && ! $orden->factura()->exists() /* && $orden->recolector_id === $user->id */;
    }

    public function terminate(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['confirmada'];

        return $user->can('terminate_orden') && in_array($orden->estado->value, $estadosPermitidos) && /* $orden->recolector_id === $user->id && */ $orden->factura()->exists();
    }

    public function prepare(User $user, Orden $orden): bool
    {
        $ordenEnProceso = Orden::where('empaquetador_id', $user->id)
            ->where('estado', 'recolectada')
            ->first();

        $estadosPermitidos = ['recolectada'];

        if (
            ! $user->can('prepare_orden') ||
            ! in_array($orden->estado->value, $estadosPermitidos) ||
            ! is_null($ordenEnProceso) ||
            ! is_null($orden->empaquetador_id)
        ) {
            return false;
        }

        $bodegasUsuario = $user->bodegas()->pluck('bodegas.id')->toArray();
        if (! in_array($orden->bodega_id, $bodegasUsuario)) {
            return false;
        }

        $empaquetadores = User::role('empaquetador')
            ->whereHas('bodegas', function ($query) use ($orden) {
                $query->where('bodegas.id', $orden->bodega_id);
            })
            ->count();
        $ordenesPendientes = Orden::where('bodega_id', $orden->bodega_id)
            ->where('estado', 'recolectada')
            ->whereNull('empaquetador_id')
            ->orderBy('created_at', 'asc')
            ->take($empaquetadores)
            ->pluck('id')
            ->toArray();

        return in_array($orden->id, $ordenesPendientes);
    }

    public function return(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['enviada', 'finalizada', 'liquidada'];

        return $user->can('return_orden') && in_array($orden->estado->value, $estadosPermitidos);
    }

    public function goback(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['completada', 'confirmada'];

        return $user->can('goback_orden') && in_array($orden->estado->value, $estadosPermitidos) && ! $orden->factura()->exists();
    }

    public function credit_note(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['devuelta', 'parcialmente devuelta'];

        return $user->can('credit_note_orden') && in_array($orden->estado->value, $estadosPermitidos);
    }

    public function receipt(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['confirmada', 'recolectada', 'preparada', 'enviada', 'finalizada', 'liquidada', 'parcialmente devuelta'];

        return $user->can('receipt_orden') && in_array($orden->estado->value, $estadosPermitidos);
    }

    public function validate_pay(User $user, Orden $orden): bool
    {
        return $user->can('validate_pay_orden');
    }

    public function send(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['preparada'];

        return $user->can('send_orden') && in_array($orden->estado->value, $estadosPermitidos) && $orden->tipo_envio->value == 'propio';
    }

    public function cancelguide(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['preparada'];

        return $user->can('cancelguide_orden') && in_array($orden->estado->value, $estadosPermitidos);
    }

    public function finish(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['enviada'];

        return $user->can('finish_orden') && in_array($orden->estado->value, $estadosPermitidos) && $orden->tipo_envio->value == 'propio' && ($orden->tipo_pago_id == 2 || $orden->pagos->sum('total') == $orden->total);
    }

    public function liquidate(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['finalizada'];

        return $user->can('liquidate_orden') && in_array($orden->estado->value, $estadosPermitidos) && ($orden->total == $orden->pagos->sum('total'));
    }

    public function print_guides(User $user, Orden $orden): bool
    {
        $estadosPermitidos = ['recolectada'];

        return $user->can('print_guides_orden') && in_array($orden->estado->value, $estadosPermitidos) && $orden->tipo_envio->value == 'guatex' && $orden->guias()->exists() && $orden->empaquetador_id;
        /* $ordenEnProceso = Orden::where('empaquetador_id', $user->id)
            ->where('estado', 'recolectada')
            ->first();

        $estadosPermitidos = ['recolectada'];
        if (
            ! $user->can('print_guides_orden') ||
            ! in_array($orden->estado->value, $estadosPermitidos) ||
            ! is_null($ordenEnProceso) ||
            ! $orden->guias()->exists() ||
            ! is_null($orden->empaquetador_id) || $orden->tipo_envio->value == 'propio'
        ) {
            return false;
        }

        $bodegasUsuario = $user->bodegas()->pluck('bodegas.id')->toArray();
        if (! in_array($orden->bodega_id, $bodegasUsuario)) {
            return false;
        }

        $empaquetadores = User::role('empaquetador')
            ->whereHas('bodegas', function ($query) use ($orden) {
                $query->where('bodegas.id', $orden->bodega_id);
            })
            ->count();

        $ordenesPendientes = Orden::where('bodega_id', $orden->bodega_id)
            ->where('estado', 'recolectada')
            ->whereNull('empaquetador_id')
            ->orderBy('created_at', 'asc')
            ->take($empaquetadores)
            ->pluck('id')
            ->toArray();

        return in_array($orden->id, $ordenesPendientes); */
    }
}
