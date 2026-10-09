<?php

// Ziggy copia en el HTML de cada página la lista de rutas que usa el JavaScript.
// Por defecto publica TODAS (panel, Horizon, reportes...) y los robots de Meta las visitan una por una.
// Aquí solo se publican las rutas que realmente usa el sitio público.
return [
    'only' => [
        'inicio', 'catalogo', 'producto', 'marcas', 'sitemap', 'imagen', 'imagen.og',
        'login', 'logout', 'profile.*',
    ],
];
