<?php $total = 0; ?>
<!DOCTYPE html>
<html>

<head>
    <title>
        @if ($orden->factura->fel_tipo == 'FCAM')
            Factura Cambiaria Orden #{{ $orden->id }}
        @else
            Factura Orden #{{ $orden->id }}
        @endif
    </title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta http-equiv="content-type" content="text-html; charset=utf-8">
    <style>
        @page {
            margin: 0.8cm 0.6cm;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .icono {
            margin-right: 5px;
            height: 13px;
        }

        .salto {
            margin-top: 14px
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 6px;
            border: 0.5px solid black;
        }

        th {
            text-align: center;
        }

        td {
            text-align: center;
        }

        tr:last-child td {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <header style="display: table; width: 100%;">
        <div style="display: table-cell; width: 50%; ">
            <img src="{{ public_path('/images/logo.png') }}" alt="Logo" style="max-width: 50%;">

            <div class="salto">
                <div>{{ env('RAZON_SOCIAL') }}</div>
                <div>{{ env('NOMBRE_COMERCIAL') }}</div>
                <div>{{ env('DIRECCION') }}</div>
                <div>{{ env('MUNICIPIO') }}, {{ env('DEPARTAMENTO') }}</div>
                <div>PBX: {{ env('PBX') }}</div>
                <div>Whatsapp: {{ env('WHATSAPP') }}</div>
                <div>NIT: {{ env('NIT') }}</div>

                <div class="salto">
                    <div>
                        @if ($orden->facturar_cf)
                            Nit Cliente: CF
                        @else
                            Nit Cliente: {{ @$orden->cliente->nit }}
                        @endif
                    </div>
                    <div>
                        @if ($orden->facturar_cf)
                            Nombre Comercial: {{ @$orden->cliente->name }}
                        @else
                            Razon Social: {{ @$orden->cliente->razon_social }}
                            <br />
                            Nombre Comercial: {{ @$orden->cliente->name }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div style="display: table-cell; width: 50%; text-align: right;">
            @if ($orden->fel_tipo == 'FCAM')
                <div style="font-weight: bold; font-size:25px;">FACTURA CAMBIARIA ORDEN #{{ $orden->id }}</div>
            @else
                <div style="font-weight: bold; font-size:25px;">FACTURA ORDEN #{{ $orden->id }}</div>
            @endif
            <div class="salto"></div>
            <div>FEL, Documento Tributario Electronico</div>
            <div class="salto">
                <div>No. Autorización: {{ $orden->factura->fel_uuid }}</div>
                <div>No. Serie: {{ $orden->factura->fel_serie }} No. DTE: {{ $orden->factura->fel_numero }}</div>
                <div>Fecha de Certificación: {{ $orden->factura->fel_fecha }}</div>
                <div>Moneda: GTQ</div>
            </div>
            <div class="salto"></div>
            <div class="salto">
                <div>ASESOR</div>
                <div>Nombre: {{ $orden->asesor->name }}</div>
                <div>Teléfono: {{ $orden->asesor->telefono }}</div>
            </div>
        </div>
    </header>

    <section class="salto">
        <table>
            <tr>
                <th>Cantidad</th>
                <th>Código</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Sub-Total</th>
            </tr>
            @foreach ($orden->detalles as $key => $dt)
                <tr>
                    <td>{{ $dt->cantidad }}</td>
                    <td>{{ $dt->producto->codigo }}</td>
                    <td style="text-align: left;">
                        {{ $dt->producto->descripcion }},
                        {{ $dt->producto->presentacion->presentacion }},
                        {{ $dt->producto->marca->marca }}
                    </td>
                    <td style="text-align: right;">{{ Number::currency($dt->precio, 'GTQ') }}</td>
                    <td style="text-align: right;">{{ Number::currency($dt->cantidad * $dt->precio, 'GTQ') }}
                    </td>
                </tr>
            @endforeach
            @if ($orden->envio > 0)
                <tr>
                    <td></td>
                    <td></td>
                    <td style="text-align: left;">SERVICIO DE ENVÍO</td>
                    <td style="text-align: right;">{{ Number::currency($orden->envio, 'GTQ') }}</td>
                    <td style="text-align: right;">{{ Number::currency($orden->envio, 'GTQ') }}</td>
                </tr>
            @endif
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td style="text-align: right;">TOTAL</td>
                <td style="text-align: right;">{{ Number::currency($orden->total, 'GTQ') }}</td>
            </tr>
        </table>
    </section>

    <section style="display: table; width: 100%; margin: 30px 0px;">
        <div style="display: table-cell; width: 50%; ">
            <div>FRASES</div>
            <div>Sujeto a pagos trimestrales ISR</div>
            <div>Agente de Retención del IVA</div>

        </div>
        <div style="display: table-cell; width: 50%; text-align: right;">
            <div class="salto">
                CERTIFICADOR: INFILE, S.A. NIT: 12521337
            </div>
        </div>
    </section>

    <footer class="salto">
        @if ($orden->fel_tipo == 'FCAM')
            <div style="border: 2px solid black; padding-bottom: -40px;">
                <div style="background:black; color:white; text-align:center; padding:3px; font-weight:700 !important">
                    COMPLEMENTOS</div>
                <div style="padding:3px" class="salto">ABONOS DE FACTURA CAMBIARIA</div>
                <div style="display: table; width: 100%;" class="salto">
                    <div style="display: table-cell; width: 50%;">
                        <table>
                            <tbody>
                                <tr>
                                    <th>Número de abono</th>
                                    <th>Fecha de vencimiento</th>
                                    <th>Monto de abono</th>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td>{{ $orden->fecha_vencimiento }}
                                    </td>
                                    <td>{{ Number::currency($orden->total, 'GTQ') }}</td>
                                </tr>
                            </tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <p style="text-align: justify; margin-top:18px">
                CONDICIONES GENERALES:
            <div>1. La mercadería será de nuestra propiedad hasta su cancelación</div>
            <div>2. Esta factura cambiaria no se considera cancelada sin el recibo de caja correspondiente.</div>
            <div>3. El comprador acepta el valor de esta factura y se compromete a cancelarlo al vencimiento pactado
                en
                las oficinas del vendedor o de tercera persona autorizada y en caso de incumplimiento el comprador
                renuncia expresamente al fuero de su domicilio y se somete a los tribunales de Guatemala o cualquier
                otro que el vendedor elija.</div>
            <div>4. La firma de cualquier empleado o dependiente del comprador al aceptar esta factura, obligará a
                éste
                a cumplir con todas las condiciones estipuladas en la misma.</div>
            <div>5. El comprador acepta como buenos los intereses y gastos por mora estipulados por el vendedor.
            </div>
            </p>
            <p style="margin-top: 60px; border-top: 1px solid black; display: inline-block;">Firma de Aceptación del
                Comprador</p>
        @endif
    </footer>
</body>

</html>
