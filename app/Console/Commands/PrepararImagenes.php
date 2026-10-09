<?php

namespace App\Console\Commands;

use App\Http\Controllers\ImagenController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Deja listos los tamaños de las fotos nuevas antes de que las pida un cliente. */
class PrepararImagenes extends Command
{
    protected $signature = 'imagenes:preparar {--carpeta= : Carpeta de S3 (por defecto la de subidas)} {--pausa=120 : Milisegundos de pausa entre fotos}';

    protected $description = 'Genera los tamaños (240, 480, 800 y 1200 px) de las fotos subidas, una por una y sin saturar el servidor';

    public function handle(): int
    {
        $carpeta = trim($this->option('carpeta') ?: config('filesystems.upload_directory'), '/').'/';
        $cliente = Storage::disk('s3')->getClient();
        $hechas = $falladas = 0;

        foreach ($cliente->getPaginator('ListObjectsV2', ['Bucket' => config('filesystems.disks.s3.bucket'), 'Prefix' => $carpeta]) as $pagina) {
            foreach ($pagina['Contents'] ?? [] as $objeto) {
                $ruta = $objeto['Key'];
                if (! preg_match('#^[A-Za-z0-9_\\-]+/[A-Za-z0-9_\\-]+\\.(webp|jpe?g|png)$#i', $ruta)) {
                    continue;
                }
                ImagenController::generar($ruta) ? $hechas++ : $falladas++;
                usleep((int) $this->option('pausa') * 1000);
            }
        }

        $this->info("Fotos preparadas: {$hechas} | no encontradas: {$falladas}");

        return self::SUCCESS;
    }
}
