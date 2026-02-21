<?php

namespace App\Services;

use App\Models\Guia;
use App\Models\Venta;
use App\Models\GuiaHija;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\GUATEXController;

class GuatexService
{
    public function generarYGuardarGuia(Venta $venta, int $paquetes, string $tipo = 'paquetes'): Guia
    {
        $guatex = new GUATEXController();

        $respuesta = json_decode($guatex->generarGuia($venta->id), true);

        if (! isset($respuesta['SERVICIO']['GUIAS']['GUIA']['NOGUIA'])) {
            Log::error('Error GUATEX', [
                'venta_id' => $venta->id,
                'respuesta' => $respuesta,
            ]);

            throw new \Exception('GUATEX no devolvió número de guía');
        }

        $guiaData = $respuesta['SERVICIO']['GUIAS']['GUIA'];

        $hijas = Arr::wrap(
            $guiaData['GUIAS_HIJAS']['HGUIAHIJA'] ?? []
        );

        return $venta->guias()->create([
            'tracking'  => $guiaData['NOGUIA'],
            'hijas'     => $hijas,
            'cantidad'  => $paquetes,
            'tipo'      => $tipo,
            'costo'     => Guia::COSTO,
        ]);
    }

    public function eliminarGuia(string $tracking): void
    {
        $url = 'https://jcl.guatex.gt:443/WSTomaServiciosCodigoGFIMP/WSTomaServiciosCodigoGFIMP';

        $usuario  = config('services.guatex.usuario');
        $password = config('services.guatex.password');
        $codigo   = config('services.guatex.codigo_cobro_zacapa');

        $xml = <<<XML
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://servicio.wstomaservicioscodimp.guatex.com/">
        <soapenv:Header/>
        <soapenv:Body>
            <ser:eliminarServicioGTX>
                <xmlentrada><![CDATA[
                    <ELIMINAR_GUIA>
                        <USUARIO>{$usuario}</USUARIO>
                        <PASSWORD>{$password}</PASSWORD>
                        <CODIGO_COBRO>{$codigo}</CODIGO_COBRO>
                        <NUMERO_GUIA>{$tracking}</NUMERO_GUIA>
                    </ELIMINAR_GUIA>
                ]]></xmlentrada>
            </ser:eliminarServicioGTX>
        </soapenv:Body>
    </soapenv:Envelope>
    XML;

        $response = file_get_contents($url, false, stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: text/xml; charset=utf-8",
                'content' => $xml,
            ],
        ]));

        if ($response === false) {
            throw new \Exception('Error al eliminar guía en GUATEX');
        }
    }
}
