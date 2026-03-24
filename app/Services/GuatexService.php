<?php

namespace App\Services;

use App\Models\Guia;
use App\Models\Venta;
use App\Models\GuiaHija;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\GUATEXController;

class GuatexService
{
    public function generarYGuardarGuia(Venta $venta, int $paquetes, string $tipo = 'paquetes', $direccionId = null): Guia
    {
        $guatex = new GUATEXController();

        $json = $guatex->generarGuia($venta->id, $direccionId);
        $respuesta = json_decode($json, true);

        if (! ($respuesta['success'] ?? false)) {
            Log::error('Error GUATEX: La petición falló', [
                'venta_id' => $venta->id,
                'respuesta' => $respuesta,
            ]);
            throw new \Exception('GUATEX no pudo generar la guía. ' . ($respuesta['body'] ?? ''));
        }

        $servicios = $respuesta['data']['serviciosGenerados'] ?? [];
        $guiaData = $servicios[0] ?? null;

        if (! $guiaData || ! isset($guiaData['noguia'])) {
            Log::error('Error GUATEX: No se encontró número de guía en la respuesta JSON', [
                'venta_id' => $venta->id,
                'respuesta' => $respuesta,
            ]);

            throw new \Exception('GUATEX no devolvió número de guía en el formato esperado.');
        }

        // Extract only the guide numbers for the 'hijas' array, as the view expects strings
        $hijas = [];
        if (isset($guiaData['guiasHijas']) && is_array($guiaData['guiasHijas'])) {
            $hijas = array_column($guiaData['guiasHijas'], 'noguia');
        } elseif (isset($guiaData['hijas']) && is_array($guiaData['hijas'])) {
            // Fallback to 'hijas' key just in case
            $hijas = array_column($guiaData['hijas'], 'noguia');
        }

        return $venta->guias()->create([
            'tracking'  => (string)$guiaData['noguia'],
            'hijas'     => $hijas,
            'cantidad'  => $paquetes,
            'tipo'      => $tipo,
            'costo'     => Guia::COSTO,
        ]);
    }

    /**
     * Converts ZPL string to PDF content using Labelary API.
     */
    public function convertZplToPdf(string $zpl): ?string
    {
        // 8dpmm (203 dpi), 4x6 inch label
        $url = "http://api.labelary.com/v1/printers/8dpmm/labels/4x6/0/";

        try {
            $response = Http::timeout(30)->withHeaders([
                'Accept' => 'application/pdf',
            ])->send('POST', $url, [
                'body' => $zpl,
            ]);

            Log::debug("Labelary response [{$response->status()}] content-type: {$response->header('Content-Type')}");

            $body = $response->body();
            if ($response->successful() && str_starts_with($body, '%PDF')) {
                return $body; // Binary PDF content
            }

            Log::error("Labelary conversion failed or returned invalid header: " . substr($body, 0, 500));
        } catch (\Exception $e) {
            Log::error("Exception in Labelary conversion: " . $e->getMessage());
        }

        return null;
    }

    public function eliminarGuia(string $tracking): void
    {
        $url = 'https://guias.guatex.gt/tomarservicio/eliminar';

        $usuario  = config('services.guatex.usuario');
        $password = config('services.guatex.password');
        $codigo   = config('services.guatex.codigo_cobro_zacapa');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->timeout(30)->post($url, [
                'usuario' => $usuario,
                'password' => $password,
                'codigoCobro' => $codigo,
                'noguia' => $tracking
            ]);

            if ($response->failed()) {
                Log::error("Error al eliminar guía en GUATEX JSON [{$response->status()}]: {$response->body()}");
                throw new \Exception('Error al eliminar guía en GUATEX');
            }

            $data = $response->json();
            if (($data['codigoRespuesta'] ?? '') !== 'EXITO') {
                Log::error("GUATEX JSON respondió error al eliminar: " . json_encode($data));
                throw new \Exception('GUATEX denegó la eliminación de la guía.');
            }
        } catch (\Exception $e) {
            Log::error("Excepción eliminando guía en service: " . $e->getMessage());
            throw $e;
        }
    }
}
