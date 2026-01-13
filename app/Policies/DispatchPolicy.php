<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Dispatch\Dispatch;

class DispatchPolicy
{
    /**
     * Ver listado de despachos
     */
    public function viewAny(User $user): bool
    {
        if ($user->can("list_dispatch")) {
            return true;
        }
        return false;
    }

    /**
     * Ver un despacho específico
     */
    public function view(User $user, Dispatch $dispatch): bool
    {
        return false;
    }

    /**
     * Crear despacho (salida)
     */
    public function create(User $user): bool
    {
        if ($user->can("register_dispatch")) {
            return true;
        }
        return false;
    }

    /**
     * Editar despacho
     */
    public function update(User $user, Dispatch $dispatch = null): bool
    {
        if ($user->can("edit_dispatch")) {
            return true;
        }
        return false;
    }

    /**
     * Eliminar despacho
     */
    public function delete(User $user, Dispatch $dispatch = null): bool
    {
        if ($user->can("delete_dispatch")) {
            return true;
        }
        return false;
    }

    /**
     * Restaurar despacho
     */
    public function restore(User $user, Dispatch $dispatch): bool
    {
        return false;
    }

    /**
     * Eliminación permanente
     */
    public function forceDelete(User $user, Dispatch $dispatch): bool
    {
        return false;
    }
}
