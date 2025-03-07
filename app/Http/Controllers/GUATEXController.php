<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Utils\Functions;
use App\Http\Requests\Ordenes\LiquidarGuatexRequest;
use App\Models\Direccion;
use App\Models\Guia;
use App\Models\Pago;
use App\Models\User;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Exception;

class GUATEXController extends Controller
{
    public function consultarMunicipios()
    {
        $url = 'https://jcl.guatex.gt/WSMunicipiosGTXGF/WSMunicipiosGTXGF';
        $usuario = config('services.guatex.usuario');
        $password = config('services.guatex.password_municipios');
        $codigoCobro = config('services.guatex.codigo_cobro');

        $headers = [
            'Content-Type: text/xml',
        ];

        $content = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://servicio.wsmunicipiosgtx.guatex.com/">
        <soapenv:Header/>
        <soapenv:Body>
            <ser:consultarMunicipios>
                <xmlCredenciales>
                    <![CDATA[
                        <CONSULTA_MUNICIPIOS>
                            <USUARIO>'.$usuario.'</USUARIO>
                            <PASSWORD>'.$password.'</PASSWORD>
                            <CODIGO_COBRO>'.$codigoCobro.'</CODIGO_COBRO>
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

                return $municipio ? ($coincideDepartamento && $coincideMunicipio) : $coincideDepartamento;
            });

            $destinosFiltrados = array_map(function ($destino) {
                return [
                    'codigo' => $destino['CODIGO'],
                    'nombre' => $destino['NOMBRE'],
                    'municipio' => $destino['MUNICIPIO'],
                    'departamento' => $destino['DEPARTAMENTO'],
                    'punto_cobertura' => $destino['PUNTO_COBERTURA'] ?? 'N/A',
                    'frecuencia_visita' => $destino['FRECUENCIA_VISITA'],
                    'texto' => sprintf(
                        '%s, %s, %s - Frecuencia: %s - Cobertura: (%s)(%s)(%s)',
                        $destino['NOMBRE'],
                        $destino['MUNICIPIO'],
                        $destino['DEPARTAMENTO'],
                        $destino['FRECUENCIA_VISITA'],
                        $destino['CODIGO'],
                        $destino['PUNTO_COBERTURA'],
                        $destino['MUNICIPIO'],
                    ),
                ];
            }, $destinosFiltrados);

            return array_values($destinosFiltrados);
        }

        return [];
    }

    public static function generarGuia($orden)
    {
        $texto = $orden->guatex_destino;
        preg_match_all('/\((.*?)\)/', $texto, $matches);
        $codigoDestino = $matches[1][0] ?? '';
        /* $puntoCobertura = $matches[1][1] ?? ''; */
        $municipioDestino = $matches[1][2] ?? '';
        $zpl = [];
        $totalPagos = $orden->pagos->sum('total');
        $montoPendiente = $orden->tipo_pago_id == 3 ? $totalPagos : 0;
        foreach ($orden->guias as $guia) {
            $codigoCobro = '';
            $tipoEnvio = '';
            $descripcionEnvio = '';
            $cobrar = 0;
            if ($orden->tipo_pago_id == 3) {
                if ($guia->tipo == 'cc') {
                    $tipoEnvio = 326;
                    $descripcionEnvio = 'CANECA/CUBETA';
                    $codigoCobro = $orden->bodega_id == 2 ? config('services.guatex.codigo_caneca_cubeta_cod_capital') : config('services.guatex.codigo_caneca_cubeta_cod');
                    $cobrar = max(0, $guia->cobrar - $montoPendiente);
                    $montoPendiente -= $guia->cobrar;
                    $montoPendiente = max(0, $montoPendiente);
                } else {
                    $tipoEnvio = 2;
                    $descripcionEnvio = 'PAQUETE';
                    $codigoCobro = $orden->bodega_id == 2 ? config('services.guatex.codigo_cobro_cod_capital') : config('services.guatex.codigo_cobro_cod');
                    $cobrar = max(0, $guia->cobrar - $montoPendiente);
                    $montoPendiente -= $guia->cobrar;
                    $montoPendiente = max(0, $montoPendiente);
                }
            } else {
                if ($guia->tipo == 'cc') {
                    $tipoEnvio = 326;
                    $descripcionEnvio = 'CANECA/CUBETA';
                    $codigoCobro = $orden->bodega_id == 2 ? config('services.guatex.codigo_caneca_cubeta_capital') : config('services.guatex.codigo_caneca_cubeta');
                } else {
                    $tipoEnvio = 2;
                    $descripcionEnvio = 'PAQUETE';
                    $codigoCobro = $orden->bodega_id == 2 ? config('services.guatex.codigo_cobro_capital') : config('services.guatex.codigo_cobro');
                }
            }
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => config('services.guatex.url_guias'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    'usuario' => 'APIGUATEX', /*  config('services.guatex.usuario') */
                    'password' => config('services.guatex.password'),
                    'codigoCobro' => config('services.guatex.codigo_cobro'),
                    'tipoUsuario' => 'C',
                    'nombreRemitente' => 'PRUEBA API CALIDADES HARMISH S.A.'/* 'CALIDADES HARMISH S.A.' */,
                    'direccionRemitente' => $orden->bodega_id == 2 ? 'Colonia el Naranjo, Complejo Logística Naranjo' : 'Residenciales El Sol, Barrio La Reforma Zona 2',
                    'telefonoRemitente' => '54934520',
                    'codigoOrigen' => $orden->bodega_id == 2 ? '395' : '707',
                    'generaZPL' => 'S',
                    'generaPDF' => 'N',
                    'estaListo' => 'S',
                    'guias' => [
                        [
                            'llaveCliente' => $orden->id,
                            'nombreDestinatario' => User::withTrashed()->find($orden->cliente_id)->name,
                            'telefonoDestinatario' => Direccion::withTrashed()->find($orden->direccion_id)->telefono ?? User::withTrashed()->find($orden->cliente_id)->telefono,
                            'direccionDestinatario' => substr(implode(', ', array_filter([
                                Direccion::withTrashed()->find($orden->direccion_id)->direccion ?? null,
                                Direccion::withTrashed()->find($orden->direccion_id)->referencia ? 'Ref: '.Direccion::withTrashed()->find($orden->direccion_id)->referencia : null,
                                Direccion::withTrashed()->find($orden->direccion_id)->zona ? 'Zona: '.Direccion::withTrashed()->find($orden->direccion_id)->zona : null,
                                optional(Direccion::withTrashed()->find($orden->direccion_id)->municipio)->municipio,
                                optional(Direccion::withTrashed()->find($orden->direccion_id)->departamento)->departamento,
                            ])), 0, 80),
                            'municipioDestino' => $municipioDestino,
                            'descripcionEnvio' => 'TIPO ENVIO:'.$descripcionEnvio.' - Cel:'.User::withTrashed()->find($orden->asesor_id)->telefono,
                            'recogeOficina' => 'N',
                            'codigoCobroGuia' => $codigoCobro,
                            'codigoDestino' => $codigoDestino,
                            'valorCOD' => number_format($cobrar, 2, '.', ''),
                            'lineasDetalle' => [
                                ['piezas' => $guia->cantidad, 'tipoEnvio' => $tipoEnvio, 'peso' => '10'],
                            ],
                        ],
                    ],
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            ]);
            $response = curl_exec($curl);
            curl_close($curl);
            $responseData = json_decode($response, true);

            if (! isset($responseData['codigoRespuesta']) || $responseData['codigoRespuesta'] !== 'EXITO' || $responseData['serviciosGenerados'][0]['noguia'] == null) {
                throw new Exception($responseData['message'] ?? $responseData['serviciosGenerados'][0]['codigoResultado']);
            }
            $guia->update([
                'tracking' => $responseData['serviciosGenerados'][0]['noguia'],
                'hijas' => json_encode(array_map(fn ($hija) => $hija['noguia'], $responseData['serviciosGenerados'][0]['hijas'])),
            ]);
            $zpl[] = $responseData['serviciosGenerados'][0]['zpl'];
            foreach ($responseData['serviciosGenerados'][0]['hijas'] as $hija) {
                $zpl[] = $hija['zpl'];
            }
        }

        return implode("\n", $zpl);
    }

    public static function eliminarGuia($tracking)
    {
        $url = 'https://jcl.guatex.gt:443/WSTomaServiciosCodigoGFIMP/WSTomaServiciosCodigoGFIMP';
        $usuario = env('GUATEX_USUARIO');
        $password = env('GUATEX_PASSWORD');
        $codigoCobro = env('GUATEX_CODIGO_COBRO');
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
                <CODIGO_COBRO>'.$codigoCobro.'</CODIGO_COBRO>
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

    public function recolectar($tracking)
    {
        $orden = Guia::where('tracking', $tracking)
            ->first()?->guiable;

        if ($orden && $orden->estado->value == 'preparada') {
            $orden->estado = 'enviada';
            $orden->fecha_enviada = now();
            $orden->save();
            activity($orden->id)->performedOn($orden)->withProperties($orden)->event('ENVIADA')->log('ORDEN '.$orden->id.' ENVIADA POR GUATEX');

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

    public function entregar($tracking)
    {
        $orden = Guia::where('tracking', $tracking)
            ->first()?->guiable;
        if ($orden && ($orden->estado->value == 'enviada' || $orden->estado->value == 'preparada')) {
            $consultaJson = $this->consultarTracking($tracking);
            $consultaArray = json_decode($consultaJson, true);
            $ultimoRegistro = end($consultaArray);
            $orden->recibio = $ultimoRegistro['recibio'];
            $orden->estado_envio = $ultimoRegistro['operacion'];
            $orden->estado = 'finalizada';
            $orden->fecha_finalizada = now();
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
        $orden = Guia::where('tracking', $request->tracking)
            ->first()?->guiable;

        if ($orden && ($orden->estado->value == 'enviada' || $orden->estado->value == 'preparada' || $orden->estado->value == 'finalizada' || $orden->estado->value == 'liquidada')) {
            $pagoExistente = Pago::where('no_documento', $request->no_acreditamiento)
                ->where('banco_id', 5)
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

            $pago = new Pago;
            $pago->user_id = User::first()->id;
            $pago->pagable_id = $orden->id;
            $pago->pagable_type = 'App\Models\Orden';
            $pago->tipo_pago_id = 9;
            $pago->banco_id = 5;
            $pago->no_documento = $request->no_acreditamiento;
            $pago->fecha_transaccion = Carbon::createFromFormat('d-m-y', $request->fecha_transaccion)->format('Y-m-d');
            $pago->monto = $request->monto;
            $pago->cod = $request->monto_cod;
            $pago->total = $request->total_liquidacion;
            $pago->save();

            activity($orden->id)->performedOn($pago)->withProperties($pago)->event('CREACIÓN')->log('PAGO DE ORDEN POR GUATEX'.$orden->id);

            return [
                'message' => 'Orden Abonada con éxito',
                'status' => true,
            ];
        }

        return [
            'message' => 'Orden no encontrada',
            'status' => false,
        ];
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
        usort($jsonArray, function ($a, $b) {
            $dateTimeA = substr($a['tfecha'], 0, 10).' '.$a['thora'];
            $dateTimeB = substr($b['tfecha'], 0, 10).' '.$b['thora'];

            return strtotime($dateTimeA) <=> strtotime($dateTimeB);
        });

        return json_encode($jsonArray, JSON_PRETTY_PRINT);
    }
}
