<?php

namespace Tests\Feature;

use Filament\Forms\Components\FileUpload;
use Tests\TestCase;

/** Ningún archivo subido desde el sistema debe guardarse en el disco del servidor ni en una carpeta "local". */
class ArchivosEnS3Test extends TestCase
{
    public function test_los_campos_de_archivo_usan_s3_y_la_carpeta_de_produccion_por_defecto(): void
    {
        $campo = FileUpload::make('cualquiera');

        $this->assertSame('s3', $campo->getDiskName());
        $this->assertSame(config('filesystems.upload_directory'), $campo->getDirectory());
        $this->assertNotSame('local', $campo->getDirectory());
    }

    public function test_la_carpeta_de_subidas_no_se_llama_local(): void
    {
        $this->assertNotEmpty(config('filesystems.upload_directory'));
        $this->assertNotSame('local', config('filesystems.upload_directory'));
    }
}
