<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Columns
    |--------------------------------------------------------------------------
    */

    'column.name' => 'Nombre',
    'column.guard_name' => 'Guard',
    'column.roles' => 'Roles',
    'column.permissions' => 'Permisos',
    'column.updated_at' => 'Actualizado el',

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */

    'field.name' => 'Nombre',
    'field.guard_name' => 'Guard',
    'field.permissions' => 'Permisos',
    'field.select_all.name' => 'Seleccionar todos',
    'field.select_all.message' => 'Habilitar todos los permisos actualmente <span class="text-primary font-medium">habilitados</span> para este rol',

    /*
    |--------------------------------------------------------------------------
    | Navigation & Resource
    |--------------------------------------------------------------------------
    */

    'nav.group' => 'Roles y Permisos',
    'nav.role.label' => 'Roles y Permisos',
    'nav.role.icon' => 'heroicon-o-shield-check',
    'resource.label.role' => 'Rol',
    'resource.label.roles' => 'Roles',

    /*
    |--------------------------------------------------------------------------
    | Section & Tabs
    |--------------------------------------------------------------------------
    */

    'section' => 'Entidades',
    'resources' => 'Recursos',
    'widgets' => 'Widgets',
    'pages' => 'Páginas',
    'custom' => 'Permisos personalizados',

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    'forbidden' => 'Usted no tiene permiso de acceso',

    /*
    |--------------------------------------------------------------------------
    | Resource Permissions' Labels
    |--------------------------------------------------------------------------
    */

    'resource_permission_prefixes_labels' => [
        'view' => 'Ver',
        'view_any' => 'Acceder',
        'create' => 'Crear',
        'update' => 'Editar',
        'delete' => 'Eliminar',
        'delete_any' => 'Eliminar varios',
        'force_delete' => 'Forzar elminación',
        'force_delete_any' => 'Forzar eliminación de varios',
        'restore' => 'Restaurar',
        'reorder' => 'Reordenar',
        'restore_any' => 'Restaurar varios',
        'replicate' => 'Duplicar',
        'annular' => 'Anular',
        'return' => 'Devolver',
        'collect' => 'Recolectar',
        'terminate' => 'Terminar',
        'confirm' => 'Confirmar',
        'prepare' => 'Preparar',
        'products' => 'Editar Productos',
        'impersonate' => 'Suplantar',
        'send' => 'Enviar',
        'receive' => 'Recibir',
        'factura' => 'Factura',
        'receipt' => 'Recibo',
        'goback' => 'Regresar',
        'validate_pay' => 'Validar Pago',
        'facturar' => 'Facturar',
        'finish' => 'Finalizar',
        'cancelguide' => 'Anular Guías',
        'liquidate' => 'Liquidar',
        'adjust' => 'Ajustar',
        'print_guides' => 'Imprimir Guías',
        'credit_note' => 'Nota de Crédito',
        'view_costs' => 'Ver Costos',
        'view_supplier' => 'Ver Proveedor',
        'credit' => 'Pagos Crédito',
        'deliver' => 'Entregar',
        'assign' => 'Asignar',
    ],
];
