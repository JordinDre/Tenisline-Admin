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

    private const CARPETAS = ['tenisline-produccion', 'tenisline', 'local', 'production'];

    /** Carpetas válidas, incluida la configurada para las subidas (UPLOAD_DIRECTORY). */
    private static function carpetas(): array
    {
        return [...self::CARPETAS, config('filesystems.upload_directory')];
    }

    /** Vista previa para compartir: 1200x630 JPG con el tenis centrado sobre fondo claro (menos de ~100 KB). */
    public function og(string $ruta): Response
    {
        abort_unless(
            in_array(strtok($ruta, '/'), self::carpetas(), true)
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
            && in_array($carpeta, self::carpetas(), true)
            && preg_match('#^[A-Za-z0-9_\-]+/[A-Za-z0-9_\-]+\.(webp|jpe?g|png)$#i', $ruta),
            404
        );

        $destino = public_path("img/{$ancho}/{$ruta}");

        if (! is_file($destino)) {
            self::generar($ruta);
            abort_unless(is_file($destino), 404);
        }

        return response()->file($destino, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    /**
     * Genera de una vez todos los tamaños de una foto (1200, 800, 480 y 240 px, WebP).
     * Se lee y decodifica el original una sola vez y cada tamaño se saca del anterior; antes cada tamaño
     * repetía todo el trabajo. Con candado por foto: si varias visitas piden la misma foto a la vez,
     * una sola la genera y las demás esperan el resultado.
     */
    public static function generar(string $ruta): bool
    {
        $lock = fopen(storage_path('framework/cache/img-'.md5($ruta).'.lock'), 'c');
        flock($lock, LOCK_EX);

        try {
            $faltan = array_filter(self::ANCHOS, fn ($w) => ! is_file(public_path("img/{$w}/{$ruta}")));
            if (! $faltan) {
                return true; // otro proceso ya la generó mientras esperábamos el candado
            }

            try {
                $original = Storage::disk('s3')->get($ruta);
                if (empty($original)) {
                    return false; // no existe en S3
                }
                $imagen = Image::make($original)->orientate();
            } catch (\Throwable) {
                return false; // no existe o no es una imagen válida
            }
            unset($original);

            foreach (array_reverse(self::ANCHOS) as $w) { // 1200 -> 800 -> 480 -> 240
                $imagen->widen($w, fn ($c) => $c->upsize());
                if (in_array($w, $faltan, true)) {
                    $destino = public_path("img/{$w}/{$ruta}");
                    @mkdir(dirname($destino), 0775, true);
                    file_put_contents($destino, (string) $imagen->encode('webp', 78));
                }
            }
            $imagen->destroy();

            return true;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
