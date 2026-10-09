<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entrega las fotos de S3 en tamaños livianos (/img/480/tenisline-produccion/xxx.webp).
 * La primera visita genera el archivo en public/img/...; desde entonces Nginx lo sirve directo,
 * sin pasar por PHP. Así una tarjeta de producto pesa ~15 KB en lugar de ~300 KB.
 */
class ImagenController extends Controller
{
    private const ANCHOS = [240, 480, 800, 1200];

    private const CARPETAS = ['tenisline-produccion', 'local', 'production'];

    /** Vista previa para compartir: 1200x630 JPG con el tenis centrado sobre fondo claro (menos de ~100 KB). */
    public function og(string $ruta): Response
    {
        abort_unless(
            in_array(strtok($ruta, '/'), self::CARPETAS, true)
            && preg_match('#^[A-Za-z0-9_\-]+/[A-Za-z0-9_\-]+\.(webp|jpe?g|png)$#i', $ruta),
            404
        );

        $destino = public_path('img/og/'.preg_replace('#\.[A-Za-z0-9]+$#', '.jpg', $ruta));

        if (! is_file($destino)) {
            $disco = Storage::disk('s3');
            abort_unless($disco->exists($ruta), 404);

            $foto = Image::make($disco->get($ruta))->orientate()->heighten(570, fn ($c) => $c->upsize());
            $lienzo = Image::canvas(1200, 630, '#f4f4f4')->insert($foto, 'center');

            @mkdir(dirname($destino), 0775, true);
            file_put_contents($destino, (string) $lienzo->encode('jpg', 82));
        }

        return response()->file($destino, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    public function __invoke(int $ancho, string $ruta): Response
    {
        $carpeta = strtok($ruta, '/');

        abort_unless(
            in_array($ancho, self::ANCHOS, true)
            && in_array($carpeta, self::CARPETAS, true)
            && preg_match('#^[A-Za-z0-9_\-]+/[A-Za-z0-9_\-]+\.(webp|jpe?g|png)$#i', $ruta),
            404
        );

        $destino = public_path("img/{$ancho}/{$ruta}");

        if (! is_file($destino)) {
            $disco = Storage::disk('s3');
            abort_unless($disco->exists($ruta), 404);

            $imagen = Image::make($disco->get($ruta))
                ->orientate()
                ->widen($ancho, fn ($c) => $c->upsize())
                ->encode('webp', 78);

            @mkdir(dirname($destino), 0775, true);
            file_put_contents($destino, (string) $imagen);
        }

        return response()->file($destino, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
