<?php

namespace App\Http\Controllers;

use App\Models\Cierre;
use App\Models\Compra;
use App\Models\Orden;
use App\Models\Producto;
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

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Orden-'.$id.'.pdf"')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function venta($id)
    {
        $venta = Venta::find($id);
        $html = view('pdf.venta', compact('venta'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper([0, 0, 227, 842], 'portrait');

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Venta-'.$id.'.pdf"')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function cierre($id)
    {
        $cierre = Cierre::with([
            'bodega',
            'user',
        ])->findOrFail($id);
        $html = view('pdf.cierre', compact('cierre'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper([0, 0, 227, 842], 'portrait');

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Cierre-'.$id.'.pdf"')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function compra($id)
    {
        $compra = Compra::with([
            'detalles',
            'bodega',
            'proveedor',
            'pagos',
        ])->findOrFail($id);
        $html = view('pdf.compras', compact('compra'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper([0, 0, 227, 842], 'portrait');

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Compra-'.$id.'.pdf"')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function traslado($id)
    {
        $traslado = Traslado::with([
            'detalles',
            'entrada',
            'salida',
            'emisor',
            'receptor',
        ])->findOrFail($id);
        $html = view('pdf.traslado', compact('traslado'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper([0, 0, 227, 842], 'portrait');

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Traslado-'.$id.'.pdf"')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function cotizacion($id)
    {
        $orden = Orden::find($id);
        $html = view('pdf.cotizacion', compact('orden'))->render();
        $pdf = Pdf::loadHTML($html);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Cotizacion-'.$id.'.pdf"')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function facturaOrden($id)
    {
        $orden = Orden::find($id);
        $html = view('pdf.facturaOrden', compact('orden'))->render();
        $pdf = Pdf::loadHTML($html);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Factura-Orden-'.$id.'.pdf"')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function facturaVenta($id)
    {
        $venta = Venta::find($id);
        $emisor = $venta->bodega_id == 6 ? config('services.fel2')
        : ($venta->bodega_id == 1 ? config('services.fel')
        : ($venta->bodega_id == 8 ? config('services.fel3') : config('services.fel')));
        $html = view('pdf.facturaVenta', compact('venta', 'emisor'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper([0, 0, 227, 842], 'portrait');

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Factura-Venta-'.$id.'.pdf"')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function notaCreditoOrden($id)
    {
        $orden = Orden::find($id);
        $html = view('pdf.notaCreditoOrden', compact('orden'))->render();
        $pdf = Pdf::loadHTML($html);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Nota-Credito-Orden-'.$id.'.pdf"')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function notaCreditoVenta($id)
    {
        $venta = Venta::with([
            'cliente',
            'asesor',
            'detalles.producto.marca',
            'factura',
            'devolucion',
        ])->findOrFail($id);
        $emisor = $venta->bodega_id == 6 ? config('services.fel2')
        : ($venta->bodega_id == 1 ? config('services.fel')
        : ($venta->bodega_id == 8 ? config('services.fel3') : config('services.fel')));
        $html = view('pdf.notaCreditoVenta', compact('venta', 'emisor'))->render();
        $pdf = Pdf::loadHTML($html);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Nota-Credito-Venta-'.$id.'.pdf"')
            ->header('X-Frame-Options', 'SAMEORIGIN');
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
                    ->header('Content-Disposition', 'inline; filename="guia.pdf"')
                    ->header('X-Frame-Options', 'SAMEORIGIN');
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

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Recibo-Orden-'.$id.'.pdf"')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function catalogo()
    {
        // Obtener máximo 10 productos con sus relaciones
        $productos = Producto::with(['marca', 'inventario.bodega.municipio'])
            ->whereHas('inventario', function ($query) {
                $query->where('existencia', '>', 0)
                    ->where('bodega_id', '!=', 2); // Excluir bodega central (bodega_id = 2)
            })
            ->limit(10)
            ->get()
            ->map(function ($producto) {
                $user = auth()->user();

                return [
                    'id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'descripcion' => $producto->descripcion,
                    'precio' => $producto->precio_venta ?? null,
                    'talla' => $producto->talla ?? null,
                    'color' => $producto->color ?? null,
                    'genero' => $producto->genero ?? null,
                    'imagen' => $this->getImageAsBase64($producto),
                    'marca' => $producto->marca->marca ?? null,
                    'bodegas' => $user
                        ? $producto->inventario
                            ->filter(function ($inv) {
                                $bodega = $inv->bodega;
                                if (! $bodega) {
                                    return false;
                                }

                                // Excluir bodega central (bodega_id = 2)
                                if ($bodega->id == 2) {
                                    return false;
                                }

                                // Excluir bodegas específicas que no deben mostrar existencia
                                if (in_array($bodega->bodega, ['Mal estado', 'Traslado', 'Central Bodega'])) {
                                    return false;
                                }

                                // Solo incluir bodegas que estén en Zacapa, Chiquimula o Esquipulas
                                $municipio = $bodega->municipio;
                                if (! $municipio) {
                                    return false;
                                }

                                return in_array(strtolower($municipio->municipio), ['zacapa', 'chiquimula', 'esquipulas']);
                            })
                            ->groupBy(function ($inv) {
                                return $inv->bodega->municipio->municipio ?? 'Desconocida';
                            })
                            ->map(function ($inventarios, $municipio) {
                                $totalExistencia = $inventarios->sum('existencia');

                                return [
                                    'bodega' => $municipio,
                                    'existencia' => $totalExistencia,
                                ];
                            })
                            ->values()
                            ->toArray()
                        : null,
                ];
            });

        $html = view('pdf.catalogo', compact('productos'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('letter', 'portrait');

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Catalogo-'.date('Y-m-d').'.pdf"')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    private function getImageAsBase64($producto)
    {
        try {
            $imageUrl = isset($producto->imagenes[0])
                ? config('filesystems.disks.s3.url').$producto->imagenes[0]
                : public_path('images/icono.png');

            // Si es una URL externa, intentar obtener el contenido
            if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $imageContent = @file_get_contents($imageUrl);
                if ($imageContent !== false) {
                    $mimeType = $this->getMimeTypeFromContent($imageContent);

                    return 'data:'.$mimeType.';base64,'.base64_encode($imageContent);
                }
            } else {
                // Si es una ruta local
                if (file_exists($imageUrl)) {
                    $imageContent = file_get_contents($imageUrl);
                    $mimeType = $this->getMimeTypeFromContent($imageContent);

                    return 'data:'.$mimeType.';base64,'.base64_encode($imageContent);
                }
            }
        } catch (\Exception $e) {
            // En caso de error, usar imagen por defecto
        }

        // Fallback a imagen por defecto
        $defaultImagePath = public_path('images/icono.png');
        if (file_exists($defaultImagePath)) {
            $imageContent = file_get_contents($defaultImagePath);

            return 'data:image/png;base64,'.base64_encode($imageContent);
        }

        return '';
    }

    private function getMimeTypeFromContent($content)
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->buffer($content);
    }
}
