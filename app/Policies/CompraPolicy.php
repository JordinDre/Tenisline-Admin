<?php

namespace App\Policies;

use App\Models\Compra;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompraPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_compra');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Compra $compra): bool
    {
        return $user->can('view_compra');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_compra');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, compra $compra): bool
    {
        $estadosPermitidos = ['creada', 'completada'];

        return $user->can('update_compra') && in_array($compra->estado->value, $estadosPermitidos);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Compra $compra): bool
    {
        return $user->can('delete_compra');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_compra');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Compra $compra): bool
    {
        return $user->can('force_delete_compra');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_compra');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Compra $compra): bool
    {
        return $user->can('restore_compra');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_compra');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Compra $compra): bool
    {
        return $user->can('replicate_compra');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_compra');
    }

    public function annular(User $user, Compra $compra): bool
    {
        $estadosPermitidos = ['creada', 'completada'];

        return $user->can('annular_compra') && in_array($compra->estado->value, $estadosPermitidos);
    }

    public function confirm(User $user, Compra $compra): bool
    {
        $estadosPermitidos = ['completada'];

        return $user->can('confirm_compra') && in_array($compra->estado->value, $estadosPermitidos);
    }
}
