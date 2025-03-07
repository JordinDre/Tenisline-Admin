<?php

namespace App\Policies;

use App\Models\Traslado;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrasladoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_traslado');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Traslado $traslado): bool
    {
        return $user->can('view_traslado');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_traslado');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Traslado $traslado): bool
    {
        if ($traslado->estado->value == 'creado') {
            if ($user->bodegas->contains('id', $traslado->salida_id) && $user->can('update_traslado')) {
                return true;
            }

            return false;
        }
        if ($traslado->estado->value == 'recibido') {
            if ($user->bodegas->contains('id', $traslado->entrada_id) && $user->can('confirm_traslado')) {
                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Traslado $traslado): bool
    {
        return /* $user->can('delete_traslado') */ false;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return /* $user->can('delete_any_traslado') */ false;
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Traslado $traslado): bool
    {
        return /* $user->can('force_delete_traslado') */ false;
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return /* $user->can('force_delete_any_traslado') */ false;
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Traslado $traslado): bool
    {
        return /* $user->can('restore_traslado') */ false;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return /* $user->can('restore_any_traslado') */ false;
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Traslado $traslado): bool
    {
        return /* $user->can('replicate_traslado') */ false;
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return /* $user->can('reorder_traslado') */ false;
    }

    public function annular(User $user, Traslado $traslado): bool
    {
        $estadosPermitidos = ['creado', 'preparado'];

        return $user->can('annular_traslado') && in_array($traslado->estado->value, $estadosPermitidos) && $user->bodegas->contains('id', $traslado->salida_id);
    }

    public function collect(User $user, Traslado $traslado): bool
    {
        $estadosPermitidos = ['preparado'];

        return $user->can('collect_traslado') && in_array($traslado->estado->value, $estadosPermitidos);
    }

    public function prepare(User $user, Traslado $traslado): bool
    {
        $estadosPermitidos = ['creado'];

        return $user->can('prepare_traslado') && in_array($traslado->estado->value, $estadosPermitidos) && $user->bodegas->contains('id', $traslado->salida_id);
    }

    public function deliver(User $user, Traslado $traslado): bool
    {
        $estadosPermitidos = ['en tránsito'];

        return $user->can('deliver_traslado') && in_array($traslado->estado->value, $estadosPermitidos);
    }

    public function confirm(User $user, Traslado $traslado): bool
    {
        $estadosPermitidos = ['recibido'];

        return $user->can('confirm_traslado') && in_array($traslado->estado->value, $estadosPermitidos) && $user->bodegas->contains('id', $traslado->entrada_id);
    }
}
