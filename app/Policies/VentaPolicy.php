<?php

namespace App\Policies;

use App\Models\Cierre;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Auth\Access\HandlesAuthorization;

class VentaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_venta');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Venta $venta): bool
    {
        return $user->can('view_venta');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Verificar que el usuario tenga los roles necesarios
        if (!$user->hasAnyRole(User::VENTA_ROLES)) {
            return false;
        }
        
        // Verificar que el usuario tenga al menos un cierre abierto
        $tieneCierreAbierto = Cierre::where('user_id', $user->id)
            ->whereNull('cierre')
            ->exists();
            
        return $tieneCierreAbierto;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Venta $venta): bool
    {
        return /* $user->can('update_venta') */ false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Venta $venta): bool
    {
        return /* $user->can('delete_venta') */ false;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return /* $user->can('delete_any_venta') */ false;
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Venta $venta): bool
    {
        return /* $user->can('force_delete_venta') */ false;
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return /* $user->can('force_delete_any_venta') */ false;
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Venta $venta): bool
    {
        return /* $user->can('restore_venta') */ false;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return /* $user->can('restore_any_venta') */ false;
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Venta $venta): bool
    {
        return /* $user->can('replicate_venta') */ false;
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return /* $user->can('reorder_venta') */ false;
    }

    public function annular(User $user, Venta $venta): bool
    {
        $estadosPermitidos = ['creada'];

        return $user->can('annular_venta') && in_array($venta->estado->value, $estadosPermitidos);
    }

    public function factura(User $user, Venta $venta): bool
    {
        $estadosPermitidos = ['creada', 'liquidada'];

        return $user->can('factura_venta') && in_array($venta->estado->value, $estadosPermitidos) && $venta->factura()->exists();
    }

    public function facturar(User $user, Venta $venta): bool
    {
        return false;
        $estadosPermitidos = ['creada'];

        return $user->can('facturar_venta') && in_array($venta->estado->value, $estadosPermitidos) && ! $venta->factura()->exists();
    }

    public function return(User $user, Venta $venta): bool
    {
        $estadosPermitidos = ['creada', 'liquidada'];

        return $user->can('return_venta') && in_array($venta->estado->value, $estadosPermitidos);
    }

    public function credit_note(User $user, Venta $venta): bool
    {
        $estadosPermitidos = ['devuelta', 'parcialmente_devuelta'];

        return $user->can('credit_note_venta') && in_array($venta->estado->value, $estadosPermitidos);
    }

    public function liquidate(User $user, Venta $venta): bool
    {
        $estadosPermitidos = ['creada'];

        return $user->can('liquidate_venta') && in_array($venta->estado->value, $estadosPermitidos) && ($venta->total == $venta->pagos->sum('total'));
    }
}
