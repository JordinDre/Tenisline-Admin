<?php

namespace App\Http\Controllers;

use DOMXPath;
use Exception;
use DOMDocument;
use Carbon\Carbon;
use App\Models\Guia;
use App\Models\Pago;
use App\Models\User;
use App\Models\Venta;
use App\Models\Direccion;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Utils\Functions;
use App\Http\Requests\Ordenes\LiquidarGuatexRequest;

class GUATEXController extends Controller
{
     public function generarGuiasPdf($id)
    {
        $orden = Venta::with(
            'estado',
            'asesor',
            'cliente',
            'direccion.municipio.departamento',
            'detalle.producto.marca:id,marca',
            'detalle.producto.presentacion:id,presentacion',
            'tipo_pago',
            'guias_hijas:guia,guia_madre,orden_id'
        )->find($id);

        $pdf = PDF::loadView('impresiones.guiaGuatex', compact('orden'));

        $pdf->getDomPDF()->setPaper([0, 0, 250, 500], 'portrait');

        $pdfContent = $pdf->output();

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename=guia.pdf');
    }

    public function generarGuiasCanecasCubetasPdf($id)
    {
        $orden = Venta::with(
            'estado',
            'asesor',
            'cliente',
            'direccion.municipio.departamento',
            'detalle.producto.marca:id,marca',
            'detalle.producto.presentacion:id,presentacion',
            'tipo_pago',
            'guias_hijas_canecas_cubetas:guia,guia_madre,orden_id'
        )->find($id);

        $pdf = PDF::loadView('impresiones.guiaGuatexCanecasCubetas', compact('orden'));

        $pdf->getDomPDF()->setPaper([0, 0, 250, 500], 'portrait');

        $pdfContent = $pdf->output();

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename=guia.pdf');
    }

    public function recolectar($tracking)
    {
        $orden = Venta::where('estado_id', '<', 7)
            ->where(function ($query) use ($tracking) {
                $query->where('tracking', $tracking)
                    ->orWhere('tracking_canecas_cubetas', $tracking);
            })->first();

        if ($orden) {
            $orden->estado_id = 7;
            $orden->fecha_recoleccion = Carbon::now()->format('Y-m-d H:i:s');
            $orden->save();

            activity($orden->id)->performedOn($orden)->withProperties($orden)->event('RECOLECCIÓN')->log('ORDEN '.$orden->id.' RECOLECTADA POR GUATEX');

            /* if ($orden->direccion['telefono']) {
                WhatsappController::recolectar($orden->id);
            } */

            return [
                'message' => 'Orden Recolectada con éxito',
                'status' => true,
                'orden' => $orden,
            ];
        }

        return [
            'message' => 'Orden no encontrada',
            'status' => false,
        ];
    }

    public function actualizarOrdenesConTracking(Request $request)
    {
        $pagina = $request->input('pagina', 1); // Página por defecto 1
        $porPagina = 10; // Número de órdenes por página

        $ordenes = Venta::where('estado_id', 7)
            ->where('tipo_envio', 'GUATEX')
            ->paginate($porPagina, ['*'], 'page', $pagina);

        foreach ($ordenes as $orden) {
            $consultaJson = $this->consultarTracking($orden->tracking);
            $consultaArray = json_decode($consultaJson, true);

            if (! empty($consultaArray)) {
                $ultimoRegistro = end($consultaArray);

                if (! empty($ultimoRegistro['recibio']) && ! empty($ultimoRegistro['operacion'])) {
                    $orden->update([
                        'recibido_por' => $ultimoRegistro['recibio'],
                        'estado_tracking' => $ultimoRegistro['operacion'],
                        'estado_id' => 8,
                        'fecha_entrega' => now(),
                    ]);
                }
            }
        }

        return response()->json([
            'mensaje' => 'Órdenes actualizadas correctamente',
            'pagina_actual' => $ordenes->currentPage(),
            'total_paginas' => $ordenes->lastPage(),
            'total_registros' => $ordenes->total(),
            'por_pagina' => $ordenes->perPage(),
        ]);
    }

    public function entregar($tracking)
    {
        $orden = Venta::whereIn('estado_id', [6, 7, 14])
            ->where(function ($query) use ($tracking) {
                $query->where('tracking', $tracking)
                    ->orWhere('tracking_canecas_cubetas', $tracking);
            })->first();

        if ($orden) {
            $consultaJson = $this->consultarTracking($tracking);
            $consultaArray = json_decode($consultaJson, true);
            $ultimoRegistro = end($consultaArray);

            // Verificar si existe la clave 'recibio' en el último registro
            if (! isset($ultimoRegistro['recibio']) || empty($ultimoRegistro['recibio'])) {
                return [
                    'message' => 'No se puede entregar la orden porque falta la información de recepción.',
                    'status' => false,
                ];
            }

            // Si 'recibio' existe, actualizar la orden
            $orden->recibido_por = $ultimoRegistro['recibio'];
            $orden->estado_tracking = $ultimoRegistro['operacion'];
            $orden->estado_id = 8;
            $orden->fecha_entrega = Carbon::now()->format('Y-m-d H:i:s');
            $orden->save();

            activity($orden->id)->performedOn($orden)->withProperties($orden)->event('ENTREGA')->log('ORDEN '.$orden->id.' ENTREGADA POR GUATEX');

            return [
                'message' => 'Orden Entregada con éxito',
                'status' => true,
            ];
        }

        return [
            'message' => 'Orden no encontrada',
            'status' => false,
        ];
    }

    public function liquidar(LiquidarGuatexRequest $request)
    {
        $orden = Venta::where('estado_id', '<', 10)
            ->where(function ($query) use ($request) {
                $query->where('tracking', $request->tracking)
                    ->orWhere('tracking_canecas_cubetas', $request->tracking);
            })->first();

        if (! $orden) {
            return [
                'message' => 'Orden no encontrada',
                'status' => false,
            ];
        }

        $pagoExistente = Pago::where('no_acreditamiento', $request->no_acreditamiento)
            ->whereDate('fecha_transaccion', Carbon::createFromFormat('d-m-y', $request->fecha_transaccion)->format('Y-m-d'))
            ->exists();

        if ($pagoExistente) {
            return [
                'message' => 'Ya se ha registrado el pago a la Orden',
                'status' => false,
            ];
        }

        if ($request->total_liquidacion > $orden->total) {
            return [
                'message' => 'La Liquidación no debe ser mayor al total de la Orden',
                'status' => false,
            ];
        }

        $pago = new Pago();
        $pago->orden_id = $orden->id;
        $pago->tipo_pago_id = 9;
        $pago->cuenta_bancaria_id = 4;
        $pago->no_acreditamiento = $request->no_acreditamiento;
        $pago->no_documento = $request->no_acreditamiento;
        $pago->fecha_transaccion = Carbon::createFromFormat('d-m-y', $request->fecha_transaccion)->format('Y-m-d');
        $pago->monto = $request->monto;
        $pago->monto_cod = $request->monto_cod;
        $pago->total_liquidacion = $request->total_liquidacion;
        $pago->user_id = 1002;
        $pago->save();

        activity($orden->id)->performedOn($pago)->withProperties($pago)->event('CREACIÓN')->log('PAGO DE ORDEN POR GUATEX'.$orden->id);

        if (intval($request->total_liquidacion) == intval($orden->total)) {
            $orden->save();

            return [
                'message' => 'Orden Liquidada con éxito',
                'status' => true,
            ];
        } else {
            return [
                'message' => 'Orden Abonada con éxito',
                'status' => true,
            ];
        }

        return [
            'message' => 'Ocurrió un error ponerse en contacto con administración',
            'status' => false,
        ];
    }

    public function consultarMunicipios()
    {
        $url = 'https://jcl.guatex.gt/WSMunicipiosGTXGF/WSMunicipiosGTXGF';
        $usuario = config('services.guatex.usuario');
        $password = config('services.guatex.password_municipios');
        $codigo = config('services.guatex.codigo_cobro');

        $headers = [
            'Content-Type: text/xml',
        ];

        $content = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://servicio.wsmunicipiosgtx.guatex.com/">
        <soapenv:Header/>
        <soapenv:Body>
            <ser:consultarMunicipios>
                <!--Optional:-->
                <xmlCredenciales>
                    <![CDATA[
                        <CONSULTA_MUNICIPIOS>
                            <USUARIO>'.$usuario.'</USUARIO>
                            <PASSWORD>'.$password.'</PASSWORD>
                            <CODIGO_COBRO>'.$codigo.'</CODIGO_COBRO>
                        </CONSULTA_MUNICIPIOS>
                    ]]>
                </xmlCredenciales>
            </ser:consultarMunicipios>
        </soapenv:Body>
    </soapenv:Envelope>';

        $options = [
            'http' => [
                'header' => implode("\r\n", $headers),
                'method' => 'POST',
                'content' => $content,
            ],
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            return false;
        }

        $dom = new DOMDocument;
        $dom->loadXML($response);
        $xpath = new DOMXPath($dom);
        $result = $xpath->evaluate('string(//return)');

        $result = htmlspecialchars_decode($result);
        $result = str_replace('&lt;', '<', $result);
        $result = str_replace('&gt;', '>', $result);

        $xmlResult = simplexml_load_string($result);
        $json = json_encode($xmlResult);

        return $json;
    }

    public function obtenerDestinos($departamento, $municipio = null)
    {
        $destinosJson = $this->consultarMunicipios();
        $destinos = json_decode($destinosJson, true);
        $departamento = Functions::eliminarTildes($departamento);
        $municipio = $municipio ? Functions::eliminarTildes($municipio) : null;

        if (is_array($destinos) && isset($destinos['DESTINOS']['DESTINO'])) {
            $destinosFiltrados = array_filter($destinos['DESTINOS']['DESTINO'], function ($destino) use ($departamento, $municipio) {
                $coincideDepartamento = isset($destino['DEPARTAMENTO']) && strcasecmp(Functions::eliminarTildes($destino['DEPARTAMENTO']), $departamento) === 0;
                $coincideMunicipio = isset($destino['MUNICIPIO']) && strcasecmp(Functions::eliminarTildes($destino['MUNICIPIO']), $municipio) === 0;

                if ($municipio) {
                    return $coincideDepartamento && $coincideMunicipio;
                } else {
                    return $coincideDepartamento;
                }
            });

            $destinosFiltrados = array_values($destinosFiltrados);

            return $destinosFiltrados;
        }

        return false;
    }

    public function consultarCodigoDestino($codigo)
    {
        $destinosJson = $this->consultarMunicipios();
        $destinos = json_decode($destinosJson, true);

        if (is_array($destinos) && isset($destinos['DESTINOS']['DESTINO'])) {
            $destinosFiltrados = array_filter($destinos['DESTINOS']['DESTINO'], function ($destino) use ($codigo) {
                return isset($destino['CODIGO']) && $destino['CODIGO'] === $codigo;
            });

            $destinosFiltrados = array_values($destinosFiltrados);

            return $destinosFiltrados;
        }

        return false;
    }

    public function consultarTracking($tracking)
    {
        $url = 'https://jcl.guatex.gt/WSTracking/WSTracking';

        $headers = [
            'Content-Type: text/xml',
        ];

        $content = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.tracking.guatex.com/">
                    <soapenv:Header/>
                    <soapenv:Body>
                        <ws:consultaws>
                            <noguia>'.$tracking.'</noguia>
                        </ws:consultaws>
                    </soapenv:Body>
                </soapenv:Envelope>';

        $options = [
            'http' => [
                'header' => implode("\r\n", $headers),
                'method' => 'POST',
                'content' => $content,
            ],
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        $xml = simplexml_load_string($response);
        $jsonArray = [];

        foreach ($xml->xpath('//return') as $return) {
            $returnArray = [];
            foreach ($return->children() as $key => $value) {
                $returnArray[$key] = (string) $value;
            }
            $jsonArray[] = $returnArray;
        }

        // Función de ordenamiento
        usort($jsonArray, function ($a, $b) {
            $dateTimeA = substr($a['tfecha'], 0, 10).' '.$a['thora']; // 2024-01-18 17:20
            $dateTimeB = substr($b['tfecha'], 0, 10).' '.$b['thora']; // 2024-01-18 19:20

            return strtotime($dateTimeA) <=> strtotime($dateTimeB);
        });

        return json_encode($jsonArray, JSON_PRETTY_PRINT);
    }

    public function generarGuia($id)
    {
        $usuario = config('services.guatex.usuario');
        $password = config('services.guatex.password');
        $codigo = config('services.guatex.codigo_cobro');

        $orden = Venta::with([
            'estado',
            'asesor',
            'cliente',
            'direccion.municipio.departamento',
            'detalle.producto.marca:id,marca',
            'detalle.producto.presentacion:id,presentacion',
            'tipo_pago',
            'pagos',
        ])->findOrFail($id);

        // Configuración de códigos
        if ($orden->tienda_id == 1) {
            $codigoCobro = config('services.guatex.codigo_cobro');
            $codigoCobroCOD = config('services.guatex.codigo_cobro_cod');
        } elseif ($orden->tienda_id == 2) {
            $codigoCobro = config('services.guatex.codigo_cobro_capital');
            $codigoCobroCOD = config('services.guatex.codigo_cobro_cod_capital');
        } else {
            $codigoCobro = config('services.guatex.codigo_cobro');
            $codigoCobroCOD = config('services.guatex.codigo_cobro_cod');
        }

        $ordenID = $orden->id;
        $remitente = 'CALIDADES HARMISH S.A.';
        $remitenteTel = $orden->asesor['telefono'].'/54934520';
        $receptorNombre = $orden->direccion['nombre_comercial'];
        $receptorTelefono = $orden->direccion['telefono'];
        $receptorCodigoDestino = $orden->codigo_destino_guatex;
        $municipioDestino = $orden->municipio_destino_guatex;
        $puntoDestino = $orden->punto_destino_guatex;
        $receptorDireccion = $orden->direccion['direccion'].', zona '.@$orden->direccion['zona'].', '.$orden->direccion['referencia'].', '.$orden->direccion['municipio']['municipio'].', '.$orden->direccion['municipio']['departamento']['departamento'];
        $paquetes = $orden->paquetes;
        $tipoPaquete = 2;

        // Calcular monto a cobrar
        $total_cubeta_caneca = 0;
        foreach ($orden->detalle as $producto) {
            if (
                strpos(strtolower($producto['producto']['presentacion']['presentacion']), 'cubeta') !== false ||
                strpos(strtolower($producto['producto']['presentacion']['presentacion']), 'caneca') !== false
            ) {
                $total_cubeta_caneca += ($producto['cantidad'] * $producto['precio']);
            }
        }

        // Configuración de URL y COD
        if ($orden->tipo_pago_id == 3) {
            $url = 'https://jcl.guatex.gt:443/WSTomaServiciosCodigoCODGFIMP/WSTomaServiciosCodigoGFIMP';
            $COD = '<COD_VALORACOBRAR>'.($orden->total - $total_cubeta_caneca - $orden->pagos->sum('total_liquidacion')).'</COD_VALORACOBRAR>
            <SEABREPAQUETE>S</SEABREPAQUETE>
            <CODIGO_COBRO_GUIA>'.$codigoCobroCOD.'</CODIGO_COBRO_GUIA>';
        } else {
            $url = 'https://jcl.guatex.gt:443/WSTomaServiciosCodigoGFIMP/WSTomaServiciosCodigoGFIMP';
            $COD = '<CODIGO_COBRO_GUIA>'.$codigoCobro.'</CODIGO_COBRO_GUIA>';
        }

        $guiasHija = '';
        for ($i = 0; $i < $paquetes; $i++) {
            $guiasHija .= '
            <LINEA_DETALLE_GUIA>
                <PIEZAS_DETALLE>1</PIEZAS_DETALLE>
                <TIPO_ENVIO_DETALLE>'.$tipoPaquete.'</TIPO_ENVIO_DETALLE>
                <PESO_DETALLE>10</PESO_DETALLE>
            </LINEA_DETALLE_GUIA>';
        }

        $direccionRemitente = $orden->tienda_id == 2
            ? 'Colonia el Naranjo, Complejo Logística Naranjo'
            : 'Residenciales El Sol, Barrio La Reforma Zona 2';

        $municipioOrigen = $orden->tienda_id == 2 ? 'CAPITAL' : 'ZACAPA';
        $puntoOrigen = $orden->tienda_id == 2 ? 'CAP' : 'ZAC';
        $codOrigen = $orden->tienda_id == 2 ? '395' : '707';

        // XML SOAP content
        $content = '
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://servicio.wstomaservicioscodimp.guatex.com/">
        <soapenv:Header/>
        <soapenv:Body>
            <ser:tomaServicioGTX>
                <xmlentrada>
                <![CDATA[
        <TOMA_SERVICIO>
            <USUARIO>'.$usuario.'</USUARIO>
            <PASSWORD>'.$password.'</PASSWORD>
            <CODIGO_COBRO>'.$codigo.'</CODIGO_COBRO>
            <SERVICIO>
                <TIPO_USUARIO>C</TIPO_USUARIO>
                <NOMBRE_REMITENTE>'.$remitente.'</NOMBRE_REMITENTE>
                <TELEFONO_REMITENTE>'.$remitenteTel.'</TELEFONO_REMITENTE>
                <DIRECCION_REMITENTE>'.$direccionRemitente.'</DIRECCION_REMITENTE>
                <MUNICIPIO_ORIGEN>'.$municipioOrigen.'</MUNICIPIO_ORIGEN>
                <PUNTO_ORIGEN>'.$puntoOrigen.'</PUNTO_ORIGEN>
                <ESTA_LISTO>S</ESTA_LISTO>
                <CODORIGEN>'.$codOrigen.'</CODORIGEN>
                <GUIA>
                    <LLAVE_CLIENTE>'.$ordenID.'</LLAVE_CLIENTE>
                    '.$COD.'
                    <NOMBRE_DESTINATARIO>'.$receptorNombre.'</NOMBRE_DESTINATARIO>
                    <TELEFONO_DESTINATARIO>'.$receptorTelefono.'</TELEFONO_DESTINATARIO>
                    <DIRECCION_DESTINATARIO>'.substr($receptorDireccion, 0, 100).'</DIRECCION_DESTINATARIO>
                    <MUNICIPIO_DESTINO>'.$municipioDestino.'</MUNICIPIO_DESTINO>
                    <PUNTO_DESTINO>'.$puntoDestino.'</PUNTO_DESTINO>
                    <DESCRIPCION_ENVIO>'.substr($receptorDireccion, 0, 100).'</DESCRIPCION_ENVIO>
                    <RECOGE_OFICINA>N</RECOGE_OFICINA>
                    <CODDESTINO>'.$receptorCodigoDestino.'</CODDESTINO>
                    <DETALLE_GUIA>'.$guiasHija.'</DETALLE_GUIA>
                </GUIA>
            </SERVICIO>
        </TOMA_SERVICIO>
        ]]>
                </xmlentrada>
            </ser:tomaServicioGTX>
        </soapenv:Body>
    </soapenv:Envelope>';

        // cURL: Enviar solicitud
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml; charset=utf-8',
            'Content-Length: '.strlen($content),
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180); // Aumentado a 3 minutos
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // desactívalo si tienes problemas de SSL

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            Log::error("Error Guatex [$httpCode]: $error\n$response");

            return response()->json(['error' => 'No se pudo generar la guía.'], 500);
        }

        // Procesar respuesta XML
        $dom = new DOMDocument;
        $dom->loadXML($response);
        $xpath = new DOMXPath($dom);
        $result = $xpath->evaluate('string(//return)');

        $result = htmlspecialchars_decode($result);
        $result = str_replace(['&lt;', '&gt;'], ['<', '>'], $result);

        $xmlResult = simplexml_load_string($result);

        return json_encode($xmlResult);
    }

    public function generarGuiaCanecasCubetas($id)
    {
        $usuario = config('services.guatex.usuario');
        $password = config('services.guatex.password');
        $codigo = config('services.guatex.codigo_cobro');

        $orden = Venta::with([
            'estado',
            'asesor',
            'cliente',
            'direccion.municipio.departamento',
            'detalle.producto.marca:id,marca',
            'detalle.producto.presentacion:id,presentacion',
            'tipo_pago',
            'pagos',
        ])->findOrFail($id);

        // Códigos según tienda
        if ($orden->tienda_id == 1) {
            $codigoCanecaCubeta = config('services.guatex.codigo_caneca_cubeta');
            $codigoCanecaCubetaCOD = config('services.guatex.codigo_caneca_cubeta_cod');
        } elseif ($orden->tienda_id == 2) {
            $codigoCanecaCubeta = config('services.guatex.codigo_caneca_cubeta_capital');
            $codigoCanecaCubetaCOD = config('services.guatex.codigo_caneca_cubeta_cod_capital');
        } else {
            $codigoCanecaCubeta = config('services.guatex.codigo_caneca_cubeta');
            $codigoCanecaCubetaCOD = config('services.guatex.codigo_caneca_cubeta_cod');
        }

        $ordenID = $orden->id;
        $remitente = 'CALIDADES HARMISH S.A.';
        $remitenteTel = $orden->asesor['telefono'].'/54934520';
        $receptorNombre = $orden->direccion['nombre_comercial'];
        $receptorTelefono = $orden->direccion['telefono'];
        $receptorCodigoDestino = $orden->codigo_destino_guatex;
        $municipioDestino = $orden->municipio_destino_guatex;
        $puntoDestino = $orden->punto_destino_guatex;
        $receptorDireccion = $orden->direccion['direccion'].', zona '.@$orden->direccion['zona'].', '.$orden->direccion['referencia'].', '.$orden->direccion['municipio']['municipio'].', '.$orden->direccion['municipio']['departamento']['departamento'];
        $canecas_cubetas = $orden->canecas_cubetas;
        $tipoPaquete = 326;

        // Calcular valores
        $total_cubeta_caneca = 0;
        $extras = 0;
        foreach ($orden->detalle as $producto) {
            $presentacion = strtolower($producto['producto']['presentacion']['presentacion']);
            if (strpos($presentacion, 'cubeta') !== false || strpos($presentacion, 'caneca') !== false) {
                $total_cubeta_caneca += $producto['cantidad'] * $producto['precio'];
            } elseif ($orden->paquetes == 0) {
                $extras += $producto['cantidad'] * $producto['precio'];
            }
        }

        // Definir URL y contenido COD
        if ($orden->tipo_pago_id == 3) {
            $url = 'https://jcl.guatex.gt:443/WSTomaServiciosCodigoCODGFIMP/WSTomaServiciosCodigoGFIMP';
            $valorACobrar = $orden->paquetes > 0
                ? $total_cubeta_caneca
                : $total_cubeta_caneca + $orden->envio + $extras - $orden->pagos->sum('total_liquidacion');
            $COD = '<COD_VALORACOBRAR>'.$valorACobrar.'</COD_VALORACOBRAR>
            <SEABREPAQUETE>S</SEABREPAQUETE>
            <CODIGO_COBRO_GUIA>'.$codigoCanecaCubetaCOD.'</CODIGO_COBRO_GUIA>';
        } else {
            $url = 'https://jcl.guatex.gt:443/WSTomaServiciosCodigoGFIMP/WSTomaServiciosCodigoGFIMP';
            $COD = '<CODIGO_COBRO_GUIA>'.$codigoCanecaCubeta.'</CODIGO_COBRO_GUIA>';
        }

        // Armar detalle guía
        $guiasHija = '';
        for ($i = 0; $i < $canecas_cubetas; $i++) {
            $guiasHija .= '
            <LINEA_DETALLE_GUIA>
                <PIEZAS_DETALLE>1</PIEZAS_DETALLE>
                <TIPO_ENVIO_DETALLE>'.$tipoPaquete.'</TIPO_ENVIO_DETALLE>
                <PESO_DETALLE>10</PESO_DETALLE>
            </LINEA_DETALLE_GUIA>';
        }

        $direccionRemitente = $orden->tienda_id == 2
            ? 'Colonia el Naranjo, Complejo Logística Naranjo'
            : 'Residenciales El Sol, Barrio La Reforma Zona 2';

        $municipioOrigen = $orden->tienda_id == 2 ? 'CAPITAL' : 'ZACAPA';
        $puntoOrigen = $orden->tienda_id == 2 ? 'CAP' : 'ZAC';
        $codOrigen = $orden->tienda_id == 2 ? '395' : '707';

        // Crear contenido XML SOAP
        $content = '
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://servicio.wstomaservicioscodimp.guatex.com/">
        <soapenv:Header/>
        <soapenv:Body>
            <ser:tomaServicioGTX>
                <xmlentrada>
                <![CDATA[
        <TOMA_SERVICIO>
            <USUARIO>'.$usuario.'</USUARIO>
            <PASSWORD>'.$password.'</PASSWORD>
            <CODIGO_COBRO>'.$codigo.'</CODIGO_COBRO>
            <SERVICIO>
                <TIPO_USUARIO>C</TIPO_USUARIO>
                <NOMBRE_REMITENTE>'.$remitente.'</NOMBRE_REMITENTE>
                <TELEFONO_REMITENTE>'.$remitenteTel.'</TELEFONO_REMITENTE>
                <DIRECCION_REMITENTE>'.$direccionRemitente.'</DIRECCION_REMITENTE>
                <MUNICIPIO_ORIGEN>'.$municipioOrigen.'</MUNICIPIO_ORIGEN>
                <PUNTO_ORIGEN>'.$puntoOrigen.'</PUNTO_ORIGEN>
                <ESTA_LISTO>S</ESTA_LISTO>
                <CODORIGEN>'.$codOrigen.'</CODORIGEN>
                <GUIA>
                    <LLAVE_CLIENTE>'.$ordenID.'</LLAVE_CLIENTE>
                    '.$COD.'
                    <NOMBRE_DESTINATARIO>'.$receptorNombre.'</NOMBRE_DESTINATARIO>
                    <TELEFONO_DESTINATARIO>'.$receptorTelefono.'</TELEFONO_DESTINATARIO>
                    <DIRECCION_DESTINATARIO>'.substr($receptorDireccion, 0, 100).'</DIRECCION_DESTINATARIO>
                    <MUNICIPIO_DESTINO>'.$municipioDestino.'</MUNICIPIO_DESTINO>
                    <PUNTO_DESTINO>'.$puntoDestino.'</PUNTO_DESTINO>
                    <DESCRIPCION_ENVIO>'.substr($receptorDireccion, 0, 100).'</DESCRIPCION_ENVIO>
                    <RECOGE_OFICINA>N</RECOGE_OFICINA>
                    <CODDESTINO>'.$receptorCodigoDestino.'</CODDESTINO>
                    <DETALLE_GUIA>'.$guiasHija.'</DETALLE_GUIA>
                </GUIA>
            </SERVICIO>
        </TOMA_SERVICIO>
        ]]>
                </xmlentrada>
            </ser:tomaServicioGTX>
        </soapenv:Body>
    </soapenv:Envelope>';

        // Enviar usando cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml; charset=utf-8',
            'Content-Length: '.strlen($content),
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180); // Aumentado a 3 minutos
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Solo si hay problemas de SSL

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            Log::error("Error Guatex [$httpCode]: $error\n$response");

            return response()->json(['error' => 'No se pudo generar la guía.'], 500);
        }

        // Procesar respuesta XML
        $dom = new DOMDocument;
        $dom->loadXML($response);
        $xpath = new DOMXPath($dom);
        $result = $xpath->evaluate('string(//return)');

        $result = htmlspecialchars_decode($result);
        $result = str_replace(['&lt;', '&gt;'], ['<', '>'], $result);

        $xmlResult = simplexml_load_string($result);

        return json_encode($xmlResult);
    }

    public function obtenerGuiasFaltantes()
    {
        $query = DB::select("
        WITH guias_limpias AS (
            SELECT 
                LEFT(tracking, 6) AS prefijo,
                CAST(RIGHT(tracking, 5) AS UNSIGNED) AS correlativo
            FROM ordenes
            WHERE estado_id = 6 AND tipo_envio = 'GUATEX' AND tracking IS NOT NULL

            UNION ALL

            SELECT 
                LEFT(tracking_canecas_cubetas, 6) AS prefijo,
                CAST(RIGHT(tracking_canecas_cubetas, 5) AS UNSIGNED) AS correlativo
            FROM ordenes
            WHERE estado_id = 6 AND tipo_envio = 'GUATEX' AND tracking_canecas_cubetas IS NOT NULL
        ),
        rangos AS (
            SELECT prefijo, MIN(correlativo) AS min_corr, MAX(correlativo) AS max_corr
            FROM guias_limpias
            GROUP BY prefijo
        ),
        numeros_posibles AS (
            SELECT 
                r.prefijo,
                r.min_corr + units.i + tens.i + hundreds.i + thousands.i AS posible_corr
            FROM rangos r
            JOIN (
                SELECT 0 i UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL
                SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL
                SELECT 8 UNION ALL SELECT 9
            ) units
            JOIN (
                SELECT 0 i UNION ALL SELECT 10 UNION ALL SELECT 20 UNION ALL SELECT 30 UNION ALL
                SELECT 40 UNION ALL SELECT 50 UNION ALL SELECT 60 UNION ALL SELECT 70 UNION ALL
                SELECT 80 UNION ALL SELECT 90
            ) tens
            JOIN (
                SELECT 0 i UNION ALL SELECT 100 UNION ALL SELECT 200 UNION ALL SELECT 300 UNION ALL
                SELECT 400 UNION ALL SELECT 500 UNION ALL SELECT 600 UNION ALL SELECT 700 UNION ALL
                SELECT 800 UNION ALL SELECT 900
            ) hundreds
            JOIN (
                SELECT 0 i UNION ALL SELECT 1000 UNION ALL SELECT 2000 UNION ALL SELECT 3000 UNION ALL
                SELECT 4000 UNION ALL SELECT 5000 UNION ALL SELECT 6000 UNION ALL SELECT 7000 UNION ALL
                SELECT 8000 UNION ALL SELECT 9000
            ) thousands
            WHERE r.min_corr + units.i + tens.i + hundreds.i + thousands.i <= r.max_corr
        ),
        faltantes AS (
            SELECT 
                n.prefijo,
                n.posible_corr
            FROM numeros_posibles n
            LEFT JOIN guias_limpias g 
                ON g.prefijo = n.prefijo AND g.correlativo = n.posible_corr
            WHERE g.correlativo IS NULL
        )
        SELECT 
            CONCAT(prefijo, LPAD(posible_corr, 5, '0')) AS guia_faltante
        FROM faltantes
        ORDER BY prefijo, posible_corr
    ");

        return collect($query)->pluck('guia_faltante')->toArray();
    }

    public function eliminarGuia($tracking)
    {
        $url = 'https://jcl.guatex.gt:443/WSTomaServiciosCodigoGFIMP/WSTomaServiciosCodigoGFIMP';
        $usuario = config('services.guatex.usuario');
        $password = config('services.guatex.password');
        $codigo = config('services.guatex.codigo_cobro');
        $headers = [
            'Content-Type: text/xml',
        ];

        $content = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://servicio.wstomaservicioscodimp.guatex.com/">
            <soapenv:Header/>
            <soapenv:Body>
                <ser:eliminarServicioGTX>
                    <!--Optional:-->
                    <xmlentrada>
                    <![CDATA[
            <ELIMINAR_GUIA>
                <USUARIO>'.$usuario.'</USUARIO>
                <PASSWORD>'.$password.'</PASSWORD>
                <CODIGO_COBRO>'.$codigo.'</CODIGO_COBRO>
                <NUMERO_GUIA>'.$tracking.'</NUMERO_GUIA>
            </ELIMINAR_GUIA>

            ]]>
                    </xmlentrada>
                </ser:eliminarServicioGTX>
            </soapenv:Body>
            </soapenv:Envelope>';

        $options = [
            'http' => [
                'header' => implode("\r\n", $headers),
                'method' => 'POST',
                'content' => $content,
            ],
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        return $response;
    }

    public function obtenerContent($id)
    {
        $usuario = config('services.guatex.usuario');
        $password = config('services.guatex.password');
        $codigo = config('services.guatex.codigo_cobro');

        $orden = Venta::with([
            'estado',
            'asesor',
            'cliente',
            'direccion.municipio.departamento',
            'detalle.producto.marca:id,marca',
            'detalle.producto.presentacion:id,presentacion',
            'tipo_pago',
            'pagos',
        ])->findOrFail($id);

        // Configuración de códigos
        if ($orden->tienda_id == 1) {
            $codigoCobro = config('services.guatex.codigo_cobro');
            $codigoCobroCOD = config('services.guatex.codigo_cobro_cod');
        } elseif ($orden->tienda_id == 2) {
            $codigoCobro = config('services.guatex.codigo_cobro_capital');
            $codigoCobroCOD = config('services.guatex.codigo_cobro_cod_capital');
        } else {
            $codigoCobro = config('services.guatex.codigo_cobro');
            $codigoCobroCOD = config('services.guatex.codigo_cobro_cod');
        }

        $ordenID = $orden->id;
        $remitente = 'CALIDADES HARMISH S.A.';
        $remitenteTel = $orden->asesor['telefono'].'/54934520';
        $receptorNombre = $orden->direccion['nombre_comercial'];
        $receptorTelefono = $orden->direccion['telefono'];
        $receptorCodigoDestino = $orden->codigo_destino_guatex;
        $municipioDestino = $orden->municipio_destino_guatex;
        $puntoDestino = $orden->punto_destino_guatex;
        $receptorDireccion = $orden->direccion['direccion'].', zona '.@$orden->direccion['zona'].', '.$orden->direccion['referencia'].', '.$orden->direccion['municipio']['municipio'].', '.$orden->direccion['municipio']['departamento']['departamento'];
        $paquetes = $orden->paquetes;
        $tipoPaquete = 2;

        // Calcular monto a cobrar
        $total_cubeta_caneca = 0;
        foreach ($orden->detalle as $producto) {
            if (
                strpos(strtolower($producto['producto']['presentacion']['presentacion']), 'cubeta') !== false ||
                strpos(strtolower($producto['producto']['presentacion']['presentacion']), 'caneca') !== false
            ) {
                $total_cubeta_caneca += ($producto['cantidad'] * $producto['precio']);
            }
        }

        // Configuración de URL y COD
        if ($orden->tipo_pago_id == 3) {
            $url = 'https://jcl.guatex.gt:443/WSTomaServiciosCodigoCODGFIMP/WSTomaServiciosCodigoGFIMP';
            $COD = '<COD_VALORACOBRAR>'.($orden->total - $total_cubeta_caneca - $orden->pagos->sum('total_liquidacion')).'</COD_VALORACOBRAR>
            <SEABREPAQUETE>S</SEABREPAQUETE>
            <CODIGO_COBRO_GUIA>'.$codigoCobroCOD.'</CODIGO_COBRO_GUIA>';
        } else {
            $url = 'https://jcl.guatex.gt:443/WSTomaServiciosCodigoGFIMP/WSTomaServiciosCodigoGFIMP';
            $COD = '<CODIGO_COBRO_GUIA>'.$codigoCobro.'</CODIGO_COBRO_GUIA>';
        }

        $guiasHija = '';
        for ($i = 0; $i < $paquetes; $i++) {
            $guiasHija .= '
            <LINEA_DETALLE_GUIA>
                <PIEZAS_DETALLE>1</PIEZAS_DETALLE>
                <TIPO_ENVIO_DETALLE>'.$tipoPaquete.'</TIPO_ENVIO_DETALLE>
                <PESO_DETALLE>10</PESO_DETALLE>
            </LINEA_DETALLE_GUIA>';
        }

        $direccionRemitente = $orden->tienda_id == 2
            ? 'Colonia el Naranjo, Complejo Logística Naranjo'
            : 'Residenciales El Sol, Barrio La Reforma Zona 2';

        $municipioOrigen = $orden->tienda_id == 2 ? 'CAPITAL' : 'ZACAPA';
        $puntoOrigen = $orden->tienda_id == 2 ? 'CAP' : 'ZAC';
        $codOrigen = $orden->tienda_id == 2 ? '395' : '707';

        // XML SOAP content
        $content = '
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://servicio.wstomaservicioscodimp.guatex.com/">
        <soapenv:Header/>
        <soapenv:Body>
            <ser:tomaServicioGTX>
                <xmlentrada>
                <![CDATA[
        <TOMA_SERVICIO>
            <USUARIO>'.$usuario.'</USUARIO>
            <PASSWORD>'.$password.'</PASSWORD>
            <CODIGO_COBRO>'.$codigo.'</CODIGO_COBRO>
            <SERVICIO>
                <TIPO_USUARIO>C</TIPO_USUARIO>
                <NOMBRE_REMITENTE>'.$remitente.'</NOMBRE_REMITENTE>
                <TELEFONO_REMITENTE>'.$remitenteTel.'</TELEFONO_REMITENTE>
                <DIRECCION_REMITENTE>'.$direccionRemitente.'</DIRECCION_REMITENTE>
                <MUNICIPIO_ORIGEN>'.$municipioOrigen.'</MUNICIPIO_ORIGEN>
                <PUNTO_ORIGEN>'.$puntoOrigen.'</PUNTO_ORIGEN>
                <ESTA_LISTO>S</ESTA_LISTO>
                <CODORIGEN>'.$codOrigen.'</CODORIGEN>
                <GUIA>
                    <LLAVE_CLIENTE>'.$ordenID.'</LLAVE_CLIENTE>
                    '.$COD.'
                    <NOMBRE_DESTINATARIO>'.$receptorNombre.'</NOMBRE_DESTINATARIO>
                    <TELEFONO_DESTINATARIO>'.$receptorTelefono.'</TELEFONO_DESTINATARIO>
                    <DIRECCION_DESTINATARIO>'.substr($receptorDireccion, 0, 100).'</DIRECCION_DESTINATARIO>
                    <MUNICIPIO_DESTINO>'.$municipioDestino.'</MUNICIPIO_DESTINO>
                    <PUNTO_DESTINO>'.$puntoDestino.'</PUNTO_DESTINO>
                    <DESCRIPCION_ENVIO>'.substr($receptorDireccion, 0, 100).'</DESCRIPCION_ENVIO>
                    <RECOGE_OFICINA>N</RECOGE_OFICINA>
                    <CODDESTINO>'.$receptorCodigoDestino.'</CODDESTINO>
                    <DETALLE_GUIA>'.$guiasHija.'</DETALLE_GUIA>
                </GUIA>
            </SERVICIO>
        </TOMA_SERVICIO>
        ]]>
                </xmlentrada>
            </ser:tomaServicioGTX>
        </soapenv:Body>
    </soapenv:Envelope>';

        return $url.$content;
    }

    public function obtenerContentCanecasCubetas($id)
    {
        $usuario = config('services.guatex.usuario');
        $password = config('services.guatex.password');
        $codigo = config('services.guatex.codigo_cobro');

        $orden = Venta::with([
            'estado',
            'asesor',
            'cliente',
            'direccion.municipio.departamento',
            'detalle.producto.marca:id,marca',
            'detalle.producto.presentacion:id,presentacion',
            'tipo_pago',
            'pagos',
        ])->findOrFail($id);

        // Códigos según tienda
        if ($orden->tienda_id == 1) {
            $codigoCanecaCubeta = config('services.guatex.codigo_caneca_cubeta');
            $codigoCanecaCubetaCOD = config('services.guatex.codigo_caneca_cubeta_cod');
        } elseif ($orden->tienda_id == 2) {
            $codigoCanecaCubeta = config('services.guatex.codigo_caneca_cubeta_capital');
            $codigoCanecaCubetaCOD = config('services.guatex.codigo_caneca_cubeta_cod_capital');
        } else {
            $codigoCanecaCubeta = config('services.guatex.codigo_caneca_cubeta');
            $codigoCanecaCubetaCOD = config('services.guatex.codigo_caneca_cubeta_cod');
        }

        $ordenID = $orden->id;
        $remitente = 'CALIDADES HARMISH S.A.';
        $remitenteTel = $orden->asesor['telefono'].'/54934520';
        $receptorNombre = $orden->direccion['nombre_comercial'];
        $receptorTelefono = $orden->direccion['telefono'];
        $receptorCodigoDestino = $orden->codigo_destino_guatex;
        $municipioDestino = $orden->municipio_destino_guatex;
        $puntoDestino = $orden->punto_destino_guatex;
        $receptorDireccion = $orden->direccion['direccion'].', zona '.@$orden->direccion['zona'].', '.$orden->direccion['referencia'].', '.$orden->direccion['municipio']['municipio'].', '.$orden->direccion['municipio']['departamento']['departamento'];
        $canecas_cubetas = $orden->canecas_cubetas;
        $tipoPaquete = 326;

        // Calcular valores
        $total_cubeta_caneca = 0;
        $extras = 0;
        foreach ($orden->detalle as $producto) {
            $presentacion = strtolower($producto['producto']['presentacion']['presentacion']);
            if (strpos($presentacion, 'cubeta') !== false || strpos($presentacion, 'caneca') !== false) {
                $total_cubeta_caneca += $producto['cantidad'] * $producto['precio'];
            } elseif ($orden->paquetes == 0) {
                $extras += $producto['cantidad'] * $producto['precio'];
            }
        }

        // Definir URL y contenido COD
        if ($orden->tipo_pago_id == 3) {
            $url = 'https://jcl.guatex.gt:443/WSTomaServiciosCodigoCODGFIMP/WSTomaServiciosCodigoGFIMP';
            $valorACobrar = $orden->paquetes > 0
                ? $total_cubeta_caneca
                : $total_cubeta_caneca + $orden->envio + $extras - $orden->pagos->sum('total_liquidacion');
            $COD = '<COD_VALORACOBRAR>'.$valorACobrar.'</COD_VALORACOBRAR>
            <SEABREPAQUETE>S</SEABREPAQUETE>
            <CODIGO_COBRO_GUIA>'.$codigoCanecaCubetaCOD.'</CODIGO_COBRO_GUIA>';
        } else {
            $url = 'https://jcl.guatex.gt:443/WSTomaServiciosCodigoGFIMP/WSTomaServiciosCodigoGFIMP';
            $COD = '<CODIGO_COBRO_GUIA>'.$codigoCanecaCubeta.'</CODIGO_COBRO_GUIA>';
        }

        // Armar detalle guía
        $guiasHija = '';
        for ($i = 0; $i < $canecas_cubetas; $i++) {
            $guiasHija .= '
            <LINEA_DETALLE_GUIA>
                <PIEZAS_DETALLE>1</PIEZAS_DETALLE>
                <TIPO_ENVIO_DETALLE>'.$tipoPaquete.'</TIPO_ENVIO_DETALLE>
                <PESO_DETALLE>10</PESO_DETALLE>
            </LINEA_DETALLE_GUIA>';
        }

        $direccionRemitente = $orden->tienda_id == 2
            ? 'Colonia el Naranjo, Complejo Logística Naranjo'
            : 'Residenciales El Sol, Barrio La Reforma Zona 2';

        $municipioOrigen = $orden->tienda_id == 2 ? 'CAPITAL' : 'ZACAPA';
        $puntoOrigen = $orden->tienda_id == 2 ? 'CAP' : 'ZAC';
        $codOrigen = $orden->tienda_id == 2 ? '395' : '707';

        // Crear contenido XML SOAP
        $content = '
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://servicio.wstomaservicioscodimp.guatex.com/">
        <soapenv:Header/>
        <soapenv:Body>
            <ser:tomaServicioGTX>
                <xmlentrada>
                <![CDATA[
        <TOMA_SERVICIO>
            <USUARIO>'.$usuario.'</USUARIO>
            <PASSWORD>'.$password.'</PASSWORD>
            <CODIGO_COBRO>'.$codigo.'</CODIGO_COBRO>
            <SERVICIO>
                <TIPO_USUARIO>C</TIPO_USUARIO>
                <NOMBRE_REMITENTE>'.$remitente.'</NOMBRE_REMITENTE>
                <TELEFONO_REMITENTE>'.$remitenteTel.'</TELEFONO_REMITENTE>
                <DIRECCION_REMITENTE>'.$direccionRemitente.'</DIRECCION_REMITENTE>
                <MUNICIPIO_ORIGEN>'.$municipioOrigen.'</MUNICIPIO_ORIGEN>
                <PUNTO_ORIGEN>'.$puntoOrigen.'</PUNTO_ORIGEN>
                <ESTA_LISTO>S</ESTA_LISTO>
                <CODORIGEN>'.$codOrigen.'</CODORIGEN>
                <GUIA>
                    <LLAVE_CLIENTE>'.$ordenID.'</LLAVE_CLIENTE>
                    '.$COD.'
                    <NOMBRE_DESTINATARIO>'.$receptorNombre.'</NOMBRE_DESTINATARIO>
                    <TELEFONO_DESTINATARIO>'.$receptorTelefono.'</TELEFONO_DESTINATARIO>
                    <DIRECCION_DESTINATARIO>'.substr($receptorDireccion, 0, 100).'</DIRECCION_DESTINATARIO>
                    <MUNICIPIO_DESTINO>'.$municipioDestino.'</MUNICIPIO_DESTINO>
                    <PUNTO_DESTINO>'.$puntoDestino.'</PUNTO_DESTINO>
                    <DESCRIPCION_ENVIO>'.substr($receptorDireccion, 0, 100).'</DESCRIPCION_ENVIO>
                    <RECOGE_OFICINA>N</RECOGE_OFICINA>
                    <CODDESTINO>'.$receptorCodigoDestino.'</CODDESTINO>
                    <DETALLE_GUIA>'.$guiasHija.'</DETALLE_GUIA>
                </GUIA>
            </SERVICIO>
        </TOMA_SERVICIO>
        ]]>
                </xmlentrada>
            </ser:tomaServicioGTX>
        </soapenv:Body>
    </soapenv:Envelope>';

        return $url.$content;
    }

    public function enviarMensajeWhatsApp($id)
    {
        $DatosOrden = DB::select(
            'select ordenes.id, tracking, total, users.whatsapp, razon_social, nombre_comercial
            from ordenes 
            inner join users on users.id = ordenes.cliente_id
            inner join direcciones on direcciones.id = ordenes.direccion_id
            where ordenes.id = ?',
            [
                $id,
            ]
        );

        if (empty($DatosOrden)) {
            return response()->json(['success' => false, 'error' => 'Orden no encontrada']);
        }

        $DatosOrden = $DatosOrden[0];

        $url = 'https://api.bird.com/workspaces/511d525b-48b9-46da-a54c-916bde57e6b7/channels/f3b3c643-59ac-488f-92a5-2d7de298bc05/messages';
        $accessKey = config('services.bird.api_key');
        $phoneNumber = /* '+50237100158' */ '+502'.($DatosOrden->whatsapp ?? '');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'AccessKey '.$accessKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'receiver' => [
                    'contacts' => [
                        [
                            'identifierValue' => $phoneNumber,
                            'identifierKey' => 'phonenumber',
                        ],
                    ],
                ],
                'template' => [
                    'projectId' => 'c2abd29a-e807-4542-8a84-3b5233a2fd6f',
                    'version' => 'e186629b-69c5-43b6-8ebb-b1fe8a460842',
                    'locale' => 'es-MX',
                    'variables' => [
                        'nombre_comercial' => (string) ($DatosOrden->nombre_comercial ?? ''),
                        'numero_orden' => (string) ($DatosOrden->id ?? ''),
                        'total' => (string) ($DatosOrden->total ?? ''),
                        'numero_guia' => (string) ($DatosOrden->tracking ?? 'SIN NUMERO GUIA'),
                    ],
                ],
            ]);

            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'Mensaje enviado correctamente']);
            } else {
                Log::error('Error al enviar mensaje de WhatsApp: '.$response->body());

                return response()->json(['success' => false, 'error' => 'Error al enviar mensaje de WhatsApp']);
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar mensaje de WhatsApp: '.$e->getMessage());

            return response()->json(['success' => false, 'error' => 'Error al enviar mensaje de WhatsApp']);
        }
    }
}
