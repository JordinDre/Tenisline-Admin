<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**j
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $models = [
            'banco',
            'bodega',
            'promocion',
            'comercio',
            'compra',
            'venta::detalle',
            'orden::detalle',
            'departamento',
            'municipio',
            'pais',
            'marca',
            'presentacion',
            'orden',
            'producto',
            'traslado',
            'venta',
            'user',
            'vehiculo',
            'role',
            'permission',
            'pago',
            'guia',
            'meta',
            'tienda',
            'carrito',
            'caja::chica'
        ];

        $actions = [
            'view_any',
            'view',
            'create',
            'update',
            'restore',
            'replicate',
            'delete',
            'reorder',
            'restore_any',
            'delete_any',
            'force_delete',
            'force_delete_any',
        ];

        foreach ($models as $model) {
            foreach ($actions as $action) {
                Permission::create(['name' => "{$action}_{$model}"]);
            }
        }

        // PERMISSIONS
        Permission::create(['name' => 'unlock']);
        Permission::create(['name' => 'impersonate_user']);
        Permission::create(['name' => 'banner-manager']);

        Permission::create(['name' => 'confirm_orden']);
        Permission::create(['name' => 'annular_orden']);
        Permission::create(['name' => 'return_orden']);
        Permission::create(['name' => 'collect_orden']);
        Permission::create(['name' => 'terminate_orden']);
        Permission::create(['name' => 'prepare_orden']);
        Permission::create(['name' => 'products_orden']);
        Permission::create(['name' => 'send_orden']);
        Permission::create(['name' => 'receipt_orden']);
        Permission::create(['name' => 'factura_orden']);
        Permission::create(['name' => 'goback_orden']);
        Permission::create(['name' => 'validate_pay_orden']);
        Permission::create(['name' => 'facturar_orden']);
        Permission::create(['name' => 'finish_orden']);
        Permission::create(['name' => 'cancelguide_orden']);
        Permission::create(['name' => 'liquidate_orden']);
        Permission::create(['name' => 'print_guides_orden']);
        Permission::create(['name' => 'credit_note_orden']);
        Permission::create(['name' => 'assign_orden']);

        Permission::create(['name' => 'credit_pago']);

        Permission::create(['name' => 'liquidate_venta']);
        Permission::create(['name' => 'factura_venta']);
        Permission::create(['name' => 'annular_venta']);
        Permission::create(['name' => 'return_venta']);
        Permission::create(['name' => 'facturar_venta']);
        Permission::create(['name' => 'credit_note_venta']);

        Permission::create(['name' => 'confirm_compra']);
        Permission::create(['name' => 'annular_compra']);

        Permission::create(['name' => 'confirm_traslado']);
        Permission::create(['name' => 'annular_traslado']);
        Permission::create(['name' => 'prepare_traslado']);
        Permission::create(['name' => 'collect_traslado']);
        Permission::create(['name' => 'deliver_traslado']);

        Permission::create(['name' => 'view_any_inventario']);
        Permission::create(['name' => 'view_inventario']);
        Permission::create(['name' => 'adjust_inventario']);
        Permission::create(['name' => 'view_kardex']);
        Permission::create(['name' => 'view_any_kardex']);

        /* CHART */
        Permission::create(['name' => 'widget_InformacionEnvios']);
        Permission::create(['name' => 'widget_VentasGeneral']);
        Permission::create(['name' => 'widget_OrdenEstados']);
        Permission::create(['name' => 'widget_OrdenesGeneral']);
        Permission::create(['name' => 'widget_CalendarioLaboral']);
        Permission::create(['name' => 'widget_OrdenesEmpaquetadas']);
        Permission::create(['name' => 'widget_OrdenesRecolectadas']);
        Permission::create(['name' => 'widget_AsesoresAsignados']);
        Permission::create(['name' => 'widget_OrdenesPreVenta']);
        Permission::create(['name' => 'widget_OrdenesTelemarketing']);
        Permission::create(['name' => 'widget_Ventas']);
        /* STAT */
        Permission::create(['name' => 'widget_CostoInventario']);
        Permission::create(['name' => 'widget_ResumenOrdenes']);
        Permission::create(['name' => 'widget_ResumenVentas']);
        Permission::create(['name' => 'widget_RecoleccionEmpaquetado']);
        Permission::create(['name' => 'widget_ComisionesOrdenes']);
        Permission::create(['name' => 'widget_ComisionesVentas']);

        /* PAGES */
        Permission::create(['name' => 'widget_Cartera']);
        Permission::create(['name' => 'select_asesor']);
        Permission::create(['name' => 'view_supplier_producto']);
        Permission::create(['name' => 'view_costs_producto']);
        Permission::create(['name' => 'view_any_clients']);
        Permission::create(['name' => 'view_any_caidos']);

        /* REPORTES */
        Permission::create(['name' => 'report_clientes_compras']);
        Permission::create(['name' => 'report_backorder']);
        Permission::create(['name' => 'report_guatex']);
        Permission::create(['name' => 'report_creditos_atrasados']);
        Permission::create(['name' => 'report_ordenes']);
        Permission::create(['name' => 'report_ventas']);
        Permission::create(['name' => 'report_orden_productos']);
        Permission::create(['name' => 'report_venta_productos']);
        Permission::create(['name' => 'report_recolectores']);
        Permission::create(['name' => 'report_empaquetadores']);
        Permission::create(['name' => 'report_pedidos']);
        Permission::create(['name' => 'report_resultados']);
        Permission::create(['name' => 'report_inventario']);

        // ROLES
        $super_admin = Role::create(['name' => 'super_admin']);
        $administrador = Role::create(['name' => 'administrador']);
        $administrador = Role::create(['name' => 'proveedor']);
        $administrador = Role::create(['name' => 'cliente']);
        $administrador = Role::create(['name' => 'vendedor']);
        $administrador = Role::create(['name' => 'mayorista']);

        /* $facturador = Role::create(['name' => 'facturador']);
        $proveedor = Role::create(['name' => 'proveedor']);
        $cliente = Role::create(['name' => 'cliente']);
        $supervisor_preventa = Role::create(['name' => 'supervisor preventa']);
        $supervisor_venta_directa = Role::create(['name' => 'supervisor venta directa']);
        $supervisor_telemarketing = Role::create(['name' => 'supervisor telemarketing']);
        $asesor_preventa = Role::create(['name' => 'asesor preventa']);
        $asesor_venta_directa = Role::create(['name' => 'asesor venta directa']);
        $asesor_telemarketing = Role::create(['name' => 'asesor telemarketing']);
        $recolector = Role::create(['name' => 'recolector']);
        $empaquetador = Role::create(['name' => 'empaquetador']);
        $creditos = Role::create(['name' => 'creditos']);
        $diseñador = Role::create(['name' => 'diseñador']);
        $bodeguero = Role::create(['name' => 'bodeguero']);
        $piloto = Role::create(['name' => 'piloto']);
        $rrhh = Role::create(['name' => 'rrhh']);
        $gerente = Role::create(['name' => 'gerente']); */

        // ASIGNAR PERMISOS A ROLES
        $super_admin->givePermissionTo(Permission::all());

        $user = User::create([
            'name' => 'Jordin',
            'email' => 'jordindredev@gmail.com',
            'password' => bcrypt('administrador'),
        ]);
        $user->assignRole('super_admin');
        $user->assignRole('vendedor');

        // assign created permissions
        // this can be done as separate widgetements
        /*$role = Role::create(['name' => 'writer']);
         $role->givePermissionTo('edit articles'); */

        // or may be done by chaining
        /*$role = Role::create(['name' => 'moderator']);
             ->givePermissionTo(['publish articles', 'unpublish articles']); */
    }
}
