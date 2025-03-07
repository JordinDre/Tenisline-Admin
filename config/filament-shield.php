<?php

return [
    'shield_resource' => [
        'should_register_navigation' => true,
        'slug' => 'roles-permisos',
        'navigation_sort' => -1,
        'navigation_badge' => false,
        'navigation_group' => false,
        'is_globally_searchable' => true,
        'show_model_path' => false,
        'is_scoped_to_tenant' => false,
        'cluster' => null,
    ],

    'auth_provider_model' => [
        'fqcn' => 'App\\Models\\User',
    ],

    'super_admin' => [
        'enabled' => true,
        'name' => 'super_admin',
        'define_via_gate' => false,
        'intercept_gate' => 'before', // after
    ],

    'panel_user' => [
        'enabled' => false,
        'name' => 'panel_user',
    ],

    'permission_prefixes' => [
        'resource' => [
            'view_any',
            'view',
            'create',
            'update',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
        ],

        'page' => 'page',
        'widget' => 'widget',
    ],

    'entities' => [
        'pages' => false,
        'widgets' => true,
        'resources' => true,
        'custom_permissions' => true,
    ],

    'generator' => [
        'option' => 'policies_and_permissions',
        'policy_directory' => 'Policies',
        'policy_namespace' => 'Policies',
    ],

    'exclude' => [
        'enabled' => true,

        'pages' => [
            'Dashboard',
            'Actividad',
            'Themes',
        ],

        'widgets' => [
            'AccountWidget',
            'FilamentInfoWidget',
        ],

        'resources' => [
            'LockResource',
            'ActivitylogResource',
            'BancoResource',
            'BodegaResource',
            'ComercioResource',
            'DepartamentoResource',
            'MunicipioResource',
            'PaisResource',
            'MetaResource',
            'RoleResource',
            'VehiculoResource',
        ],
    ],

    'discovery' => [
        'discover_all_resources' => true,
        'discover_all_widgets' => true,
        'discover_all_pages' => true,
    ],

    'register_role_policy' => [
        'enabled' => true,
    ],

];
