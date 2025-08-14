<?php

namespace App\Policies;

use App\Models\CajaChica;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CajaChicaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CajaChica $cajachica): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CajaChica $cajachica): bool
    {
        return  false /* $user->can('update_bodega') */ /* $user->hasRole(['super_admin', 'administrador']) */;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CajaChica $cajachica): bool
    {
        return  false /* $user->can('delete_bodega') */ /* $user->hasRole(['super_admin', 'administrador']) */;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return /* $user->can('delete_any_bodega') */ false;
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, CajaChica $cajachica): bool
    {
        return /* $user->can('force_delete_bodega') */ false;
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return /* $user->can('force_delete_any_bodega') */ false;
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, CajaChica $cajachica): bool
    {
        return /* $user->can('restore_bodega') */ false;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return /* $user->can('restore_any_bodega') */ false;
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, CajaChica $cajachica): bool
    {
        return /* $user->can('replicate_bodega') */ false;
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return /* $user->can('reorder_bodega') */ false;
    }

    public function annular(User $user, CajaChica $compra): bool
    {
        $estadosPermitidos = ['creada'];

        return $user->hasPermissionTo('annular_caja::chica') && in_array($compra->estado, $estadosPermitidos);
    }

    public function confirm(User $user, CajaChica $compra): bool
    {
        $estadosPermitidos = ['creada'];

        return $user->hasPermissionTo('confirm_caja::chica') && in_array($compra->estado, $estadosPermitidos);
    }
}
