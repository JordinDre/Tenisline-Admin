<?php

namespace App\Support;

/**
 * Datos de SEO de cada página pública. Se envían como prop "seo" y app.blade.php los imprime
 * en el HTML del servidor, así Google, WhatsApp y Facebook los leen sin ejecutar JavaScript.
 */
class Seo
{
    public static function make(string $titulo, string $descripcion, ?string $imagen = null, ?string $canonical = null, string $tipo = 'website', bool $indexar = true, array $jsonld = []): array
    {
        return [
            'title' => $titulo,
            'description' => str($descripcion)->squish()->limit(160, '…')->toString(),
            'image' => $imagen ?: url('/images/og-tenisline.jpg'),
            'canonical' => $canonical ?: url()->current(),
            'type' => $tipo,
            'robots' => $indexar ? 'index,follow,max-image-preview:large' : 'noindex,follow',
            'jsonld' => $jsonld,
        ];
    }

    /** Foto de S3 -> versión 1200x630 en JPG (/img/og/...), el tamaño que piden WhatsApp, Facebook y X. */
    public static function imagenOg(?string $urlS3): ?string
    {
        if (! $urlS3) {
            return null;
        }
        $host = parse_url($urlS3, PHP_URL_HOST) ?: '';
        $ruta = ltrim((string) parse_url($urlS3, PHP_URL_PATH), '/');

        return str_contains($host, 'amazonaws.com') && $ruta ? url('/img/og/'.$ruta) : $urlS3;
    }

    /** Negocio local (una tienda por sucursal) y buscador interno del sitio. */
    public static function negocio(): array
    {
        $tienda = config('tienda');

        return [
            [
                '@context' => 'https://schema.org',
                '@type' => 'ShoeStore',
                'name' => $tienda['nombre'],
                'slogan' => $tienda['eslogan'],
                'url' => url('/'),
                'logo' => url('/images/logo-completo-negro.webp'),
                'image' => url('/images/og-tenisline.jpg'),
                'sameAs' => array_values($tienda['redes']),
                'priceRange' => 'Q',
                'areaServed' => ['Zacapa', 'Chiquimula', 'Esquipulas'],
                'department' => collect($tienda['sucursales'])->map(fn ($s) => [
                    '@type' => 'ShoeStore',
                    'name' => $tienda['nombre'].' '.$s['nombre'],
                    'telephone' => '+'.$s['telefono'],
                    'address' => ['@type' => 'PostalAddress', 'streetAddress' => $s['direccion'], 'addressLocality' => $s['nombre'], 'addressCountry' => 'GT'],
                ])->values()->all(),
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $tienda['nombre'],
                'url' => url('/'),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => url('/catalogo').'?search={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];
    }

    public static function migas(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn ($i, $n) => [
                '@type' => 'ListItem', 'position' => $n + 1, 'name' => $i[0], 'item' => $i[1],
            ])->all(),
        ];
    }
}
