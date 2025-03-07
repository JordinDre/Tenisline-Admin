<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_producto');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Producto $producto): bool
    {
        return $user->can('view_producto');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_producto');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Producto $producto): bool
    {
        return $user->can('update_producto');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Producto $producto): bool
    {
        return $user->can('delete_producto') && ! $producto->trashed();
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_producto');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Producto $producto): bool
    {
        return $user->can('force_delete_producto') && ! $producto->trashed();
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_producto');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Producto $producto): bool
    {
        return $user->can('restore_producto');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_producto');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Producto $producto): bool
    {
        return $user->can('replicate_producto');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_producto');
    }
}
