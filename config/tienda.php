<?php

// Datos públicos del sitio web de Tenisline (header, footer y pedidos por WhatsApp).
return [
    'nombre' => 'Tenisline',
    'eslogan' => 'No box, sí precio',

    // Sucursales a las que se puede enviar el pedido por WhatsApp
    'sucursales' => [
        ['nombre' => 'Zacapa', 'telefono' => '50239900606', 'direccion' => env('DIRECCION', '')],
        ['nombre' => 'Chiquimula', 'telefono' => '50239910917', 'direccion' => env('DIRECCION2', '')],
        ['nombre' => 'Esquipulas', 'telefono' => '50239999952', 'direccion' => env('DIRECCION3', '')],
    ],

    // Redes sociales (footer, menú y datos estructurados para Google)
    'redes' => [
        'instagram' => 'https://www.instagram.com/tenisline_gt',
        'tiktok' => 'https://www.tiktok.com/@tenisline.gt',
        'facebook' => 'https://www.facebook.com/share/1C7FaFFoUF/',
    ],

    // Categorías del catálogo: clave => [etiqueta, filtro]
    'categorias' => [
        'caballero' => ['label' => 'Caballero', 'genero' => 'CABALLERO'],
        'dama' => ['label' => 'Dama', 'genero' => 'DAMA'],
        'nino' => ['label' => 'Niño', 'genero' => 'NIÑO'],
        'infante' => ['label' => 'Infante', 'genero' => 'INFANTE'],
        'ofertas' => ['label' => 'Ofertas', 'ofertas' => true],
    ],

    // Marcas que no se muestran en el sitio (productos que no son calzado)
    'marcas_ocultas' => ['4X'],

    // Logos de marca disponibles en public/images/marcas (nombre de marca en mayúsculas => archivo)
    'logos_marcas' => [
        'ADIDAS' => 'adidas.svg',
        'ASICS' => 'asics.svg',
        'CAT' => 'caterpillar.svg',
        'JORDAN' => 'jordan.svg',
        'LACOSTE' => 'lacoste.svg',
        'MIZUNO' => 'mizuno.svg',
        'NEW BALANCE' => 'newbalance.svg',
        'NIKE' => 'nike.svg',
        'ON' => 'on.svg',
        'PUMA' => 'puma.svg',
        'REEBOK' => 'reebok.svg',
        'SALOMON' => 'salomon.svg',
        'SAUCONY' => 'saucony.svg',
        'SKECHERS' => 'skechers.svg',
    ],
];
