<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'auxiliar']);

        $permissions = [
            'view_any_traslado',
            'view_traslado',
            'create_traslado',
            'update_traslado',
            'collect_traslado',
            'prepare_traslado',
            'deliver_traslado',
            'annular_traslado',
            'regresar_traslado',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
            $role->givePermissionTo($permissionName);
        }

        // Explicitly revoke confirm_traslado just in case
        $role->revokePermissionTo('confirm_traslado');
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::where('name', 'auxiliar')->first();
        if ($role) {
            $permissions = [
                'view_any_traslado',
                'view_traslado',
                'create_traslado',
                'update_traslado',
                'collect_traslado',
                'prepare_traslado',
                'deliver_traslado',
                'annular_traslado',
                'regresar_traslado',
            ];

            foreach ($permissions as $permissionName) {
                try {
                    $role->revokePermissionTo($permissionName);
                } catch (\Exception $e) {
                    // Ignore if permission doesn't exist or wasn't assigned
                }
            }
        }
    }
};
