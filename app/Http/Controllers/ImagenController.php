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
