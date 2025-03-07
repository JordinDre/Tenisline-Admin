<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Columns
    |--------------------------------------------------------------------------
    */

    'column.name' => 'Name',
    'column.guard_name' => 'Guard Name',
    'column.roles' => 'Roles',
    'column.permissions' => 'Permissions',
    'column.updated_at' => 'Updated At',

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */

    'field.name' => 'Name',
    'field.guard_name' => 'Guard Name',
    'field.permissions' => 'Permissions',
    'field.select_all.name' => 'Select All',
    'field.select_all.message' => 'Enable all Permissions currently <span class="text-primary font-medium">Enabled</span> for this role',

    /*
    |--------------------------------------------------------------------------
    | Navigation & Resource
    |--------------------------------------------------------------------------
    */

    'nav.group' => 'Filament Shield',
    'nav.role.label' => 'Roles',
    'nav.role.icon' => 'heroicon-o-shield-check',
    'resource.label.role' => 'Role',
    'resource.label.roles' => 'Roles',

    /*
    |--------------------------------------------------------------------------
    | Section & Tabs
    |--------------------------------------------------------------------------
    */

    'section' => 'Entities',
    'resources' => 'Resources',
    'widgets' => 'Widgets',
    'pages' => 'Pages',
    'custom' => 'Custom Permissions',

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    'forbidden' => 'You do not have permission to access',

    /*
    |--------------------------------------------------------------------------
    | Resource Permissions' Labels
    |--------------------------------------------------------------------------
    */

    'resource_permission_prefixes_labels' => [
        'view' => 'View',
        'view_any' => 'View Any',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'delete_any' => 'Delete Any',
        'force_delete' => 'Force Delete',
        'force_delete_any' => 'Force Delete Any',
        'restore' => 'Restore',
        'reorder' => 'Reorder',
        'restore_any' => 'Restore Any',
        'replicate' => 'Replicate',
        'annular' => 'Annular',
        'return' => 'Return',
        'collect' => 'Collect',
        'confirm' => 'Confirm',
        'terminate' => 'Terminate',
        'prepare' => 'Prepare',
        'products' => 'Products',
        'impersonate' => 'Impersonate',
        'send' => 'Send',
        'receive' => 'Receive',
        'receipt' => 'Receipt',
        'factura' => 'Factura',
        'goback' => 'Go Back',
        'validate_pay' => 'Validate Pay',
        'facturar' => 'Facturar',
        'finish' => 'Finish',
        'send' => 'Send',
        'cancelguide' => 'Cancel Guide',
        'liquidate' => 'Liquidate',
        'adjust' => 'Adjust',
        'print_guides' => 'Print Guides',
        'credit_note' => 'Credit Note',
        'view_costs' => 'View Costs',
        'view_supplier' => 'View Supplier',
        'credit' => 'Credit Payments',
        'deliver' => 'Deliver',
        'assign' => 'Assign',
    ],
];
