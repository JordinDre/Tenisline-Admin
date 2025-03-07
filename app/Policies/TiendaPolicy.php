<?php

namespace App\Policies;

use App\Models\Tienda;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TiendaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_tienda');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tienda $tienda): bool
    {
        return $user->can('view_tienda');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return /* $user->can('create_tienda') */ false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tienda $tienda): bool
    {
        return $user->can('update_tienda');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tienda $tienda): bool
    {
        return /* $user->can('delete_tienda') */ false;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return /* $user->can('delete_any_tienda') */ false;
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Tienda $tienda): bool
    {
        return /* $user->can('force_delete_tienda') */ false;
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return /* $user->can('force_delete_any_tienda') */ false;
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Tienda $tienda): bool
    {
        return /* $user->can('restore_tienda') */ false;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return /* $user->can('restore_any_tienda') */ false;
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Tienda $tienda): bool
    {
        return /* $user->can('replicate_tienda') */ false;
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return /* $user->can('reorder_tienda') */ false;
    }
}
