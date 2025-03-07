<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fel' => [
        'usuario_api' => env('FEL_USUARIO_API', 'CALIDADES_DEMO'),
        'llave_api' => env('FEL_LLAVE_API', '5885D496FAB7DF356E09C6734E34FC04'),
        'usuario_firma' => env('FEL_USUARIO_FIRMA', 'CALIDADES_DEMO'),
        'identificador' => env('FEL_IDENTIFICADOR', 'CALIDADES_DEMO'),
        'llave_firma' => env('FEL_LLAVE_FIRMA', 'f8f8afb5c114c4f5aa485c41084c092e'),
    ],

    // Configuración de Guatex
    'guatex' => [
        'url_guias' => env('GUATEX_URL_GUIAS', 'https://guias.guatex.gt/tomarservicio/servicio'),
        'usuario' => env('GUATEX_USUARIO', 'WSGTX'),
        'password' => env('GUATEX_PASSWORD', 'GTXH4M123'),
        'password_municipios' => env('GUATEX_PASSWORD_MUNICIPIOS', 'GTXCLDHMS'),

        'codigo_cobro' => env('GUATEX_CODIGO_COBRO', 'CON5723'),
        'codigo_cobro_cod' => env('GUATEX_CODIGO_COBRO_COD', 'U8400016'),
        'codigo_caneca_cubeta' => env('GUATEX_CODIGO_CANECA_CUBETA', 'CON6430'),
        'codigo_caneca_cubeta_cod' => env('GUATEX_CODIGO_CANECA_CUBETA_COD', 'T32000B4'),

        'codigo_cobro_capital' => env('GUATEX_CODIGO_COBRO_CAPITAL', 'CON5723'),
        'codigo_cobro_cod_capital' => env('GUATEX_CODIGO_COBRO_COD_CAPITAL', 'U8400016'),
        'codigo_caneca_cubeta_capital' => env('GUATEX_CODIGO_CANECA_CUBETA_CAPITAL', 'CON6430'),
        'codigo_caneca_cubeta_cod_capital' => env('GUATEX_CODIGO_CANECA_CUBETA_COD_CAPITAL', 'T32000B4'),
    ],

];
