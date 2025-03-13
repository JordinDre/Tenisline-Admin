<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use App\Models\Traslado;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class PDFController extends Controller
{
    public function orden($id)
    {
        $orden = Orden::find($id);
        $html = view('pdf.orden', compact('orden'))->render();
        $pdf = Pdf::loadHTML($html);

        return $pdf->stream("Orden #{$id}.pdf");
    }

    public function venta($id)
    {
        $venta = Venta::find($id);
        $html = view('pdf.venta', compact('venta'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper([0, 0, 227, 842], 'portrait');

        return $pdf->stream("Venta #{$id}.pdf");
    }

    public function cotizacion($id)
    {
        $orden = Orden::find($id);
        $html = view('pdf.cotizacion', compact('orden'))->render();
        $pdf = Pdf::loadHTML($html);

        return $pdf->stream("Cotización #{$id}.pdf");
    }

    public function facturaOrden($id)
    {
        $orden = Orden::find($id);
        $html = view('pdf.facturaOrden', compact('orden'))->render();
        $pdf = Pdf::loadHTML($html);

        return $pdf->stream("Factura Orden #{$id}.pdf");
    }

    public function facturaVenta($id)
    {
        $venta = Venta::find($id);
        $html = view('pdf.facturaVenta', compact('venta'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper([0, 0, 227, 842], 'portrait');

        return $pdf->stream("Factura Venta #{$id}.pdf");
    }

    public function notaCreditoOrden($id)
    {
        $orden = Orden::find($id);
        $html = view('pdf.notaCreditoOrden', compact('orden'))->render();
        $pdf = Pdf::loadHTML($html);

        return $pdf->stream("Nota Credito Orden #{$id}.pdf");
    }

    public function notaCreditoVenta($id)
    {
        $venta = Venta::find($id);
        $html = view('pdf.notaCreditoVenta', compact('venta'))->render();
        $pdf = Pdf::loadHTML($html);

        return $pdf->stream("Nota Credito Venta #{$id}.pdf");
    }

    public function comprobanteTraslado($id)
    {
        $traslado = Traslado::find($id);
        $html = view('pdf.comprobanteTraslado', compact('traslado'))->render();
        $pdf = Pdf::loadHTML($html);

        return $pdf->stream("Comprobante Traslado #{$id}.pdf");
    }

    public function guias($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $orden = Orden::findOrFail($id);
                if ($orden->estado->value != 'recolectada') {
                    throw new Exception('La orden ya fue movida');
                }
                $zpl = GUATEXController::generarGuia($orden);

                $url = 'http://api.labelary.com/v1/printers/8dpmm/labels/3x6/';
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $zpl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/pdf',
                    ],
                ]);
                $result = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($httpCode !== 200 || $result === false) {
                    $error = curl_error($curl) ?: 'Error al convertir ZPL a PDF con Labelary.';
                    curl_close($curl);
                    throw new Exception($error);
                }
                curl_close($curl);
                $orden->estado = 'preparada';
                $orden->save();
                return response($result)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="guia.pdf"');
                /* $texto = $orden->guatex_destino;
                preg_match_all('/\((.*?)\)/', $texto, $matches);
                $codigoDestino = $matches[1][0] ?? '';
                $puntoCobertura = $matches[1][1] ?? '';
                $municipioDestino = $matches[1][2] ?? '';
                $pdf = PDF::loadView('pdf.guias', compact('orden', 'puntoCobertura'));

                $pdf->getDomPDF()->setPaper([0, 0, 250, 500], 'portrait');

                $pdfContent = $pdf->output();

                $orden->estado = 'preparada';
                $orden->save();

                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename=guia.pdf'); */
            });
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al preparar la Orden')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function reciboOrden($id)
    {
        $orden = Orden::find($id);
        $html = view('pdf.reciboOrden', compact('orden'))->render();
        $pdf = Pdf::loadHTML($html);

        return $pdf->stream("Recibo Orden #{$id}.pdf");
    }
}
