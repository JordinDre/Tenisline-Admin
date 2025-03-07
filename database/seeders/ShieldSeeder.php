<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"administrador","guard_name":"web","permissions":[]},{"name":"facturador","guard_name":"web","permissions":[]},{"name":"proveedor","guard_name":"web","permissions":[]},{"name":"cliente","guard_name":"web","permissions":[]},{"name":"supervisor preventa","guard_name":"web","permissions":[]},{"name":"supervisor venta directa","guard_name":"web","permissions":[]},{"name":"supervisor telemarketing","guard_name":"web","permissions":[]},{"name":"asesor preventa","guard_name":"web","permissions":[]},{"name":"asesor venta directa","guard_name":"web","permissions":[]},{"name":"asesor telemarketing","guard_name":"web","permissions":[]},{"name":"recolector","guard_name":"web","permissions":[]},{"name":"empaquetador","guard_name":"web","permissions":[]},{"name":"creditos","guard_name":"web","permissions":[]},{"name":"dise\u00f1ador","guard_name":"web","permissions":[]},{"name":"bodeguero","guard_name":"web","permissions":[]},{"name":"repartidor","guard_name":"web","permissions":[]},{"name":"super_admin","guard_name":"web","permissions":["view_activitylog","view_any_activitylog","create_activitylog","update_activitylog","restore_activitylog","restore_any_activitylog","replicate_activitylog","reorder_activitylog","delete_activitylog","delete_any_activitylog","force_delete_activitylog","force_delete_any_activitylog","view_banco","view_any_banco","create_banco","update_banco","restore_banco","restore_any_banco","replicate_banco","reorder_banco","delete_banco","delete_any_banco","force_delete_banco","force_delete_any_banco","view_bodega","view_any_bodega","create_bodega","update_bodega","restore_bodega","restore_any_bodega","replicate_bodega","reorder_bodega","delete_bodega","delete_any_bodega","force_delete_bodega","force_delete_any_bodega","view_comercio","view_any_comercio","create_comercio","update_comercio","restore_comercio","restore_any_comercio","replicate_comercio","reorder_comercio","delete_comercio","delete_any_comercio","force_delete_comercio","force_delete_any_comercio","view_cuenta::bancaria","view_any_cuenta::bancaria","create_cuenta::bancaria","update_cuenta::bancaria","restore_cuenta::bancaria","restore_any_cuenta::bancaria","replicate_cuenta::bancaria","reorder_cuenta::bancaria","delete_cuenta::bancaria","delete_any_cuenta::bancaria","force_delete_cuenta::bancaria","force_delete_any_cuenta::bancaria","view_departamento","view_any_departamento","create_departamento","update_departamento","restore_departamento","restore_any_departamento","replicate_departamento","reorder_departamento","delete_departamento","delete_any_departamento","force_delete_departamento","force_delete_any_departamento","view_lock","view_any_lock","create_lock","update_lock","restore_lock","restore_any_lock","replicate_lock","reorder_lock","delete_lock","delete_any_lock","force_delete_lock","force_delete_any_lock","view_municipio","view_any_municipio","create_municipio","update_municipio","restore_municipio","restore_any_municipio","replicate_municipio","reorder_municipio","delete_municipio","delete_any_municipio","force_delete_municipio","force_delete_any_municipio","view_pais","view_any_pais","create_pais","update_pais","restore_pais","restore_any_pais","replicate_pais","reorder_pais","delete_pais","delete_any_pais","force_delete_pais","force_delete_any_pais","view_permission","view_any_permission","create_permission","update_permission","restore_permission","restore_any_permission","replicate_permission","reorder_permission","delete_permission","delete_any_permission","force_delete_permission","force_delete_any_permission","view_role","view_any_role","create_role","update_role","delete_role","delete_any_role","view_user","view_any_user","create_user","update_user","restore_user","restore_any_user","replicate_user","reorder_user","delete_user","delete_any_user","force_delete_user","force_delete_any_user","view_vehiculo","view_any_vehiculo","create_vehiculo","update_vehiculo","restore_vehiculo","restore_any_vehiculo","replicate_vehiculo","reorder_vehiculo","delete_vehiculo","delete_any_vehiculo","force_delete_vehiculo","force_delete_any_vehiculo","page_HealthCheckResults","page_Backups"]}]';
        $directPermissions = '[{"name":"unlock","guard_name":"web"}]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
