<!DOCTYPE html>
<html>

<head>
    <title>Nota de Crédito</title>
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
                <div>{{ config('services.fel.razon_social') }}</div>
                <div>{{ config('services.fel.nombre_comercial') }}</div>
                <div>{{ config('services.fel.direccion') }}</div>
                <div>{{ config('services.fel.municipio') }}, {{ config('services.fel.departamento')}}</div>
                <div>PBX: {{ config('services.fel.pbx') }}</div>
                <div>Whatsapp: {{ config('services.fel.whatsapp') }}</div>
                <div>NIT: {{ config('services.fel.nit') }}</div>

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
            <div style="font-weight: bold; font-size:25px;">NOTA DE CRÉDITO ORDEN #{{ $orden->id }}</div>
            <div class="salto"></div>
            <div>FEL, Documento Tributario Electronico</div>
            <div class="salto">
                <<div>No. Autorización: {{ $orden->devolucion->fel_uuid }}
            </div>
            <div>No. Serie: {{ $orden->devolucion->fel_serie }} No. DTE: {{ $orden->devolucion->fel_numero }}</div>
            <div>Fecha de Certificación: {{ $orden->devolucion->fel_fecha }}</div>
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
    <br><br>
    <div style="font-weight: bold; font-size:16px;">Monto Documento Origen:
        {{ Number::currency($orden->total, 'GTQ') }}</div>

    @php
        $total = 0;
    @endphp
    <section class="salto">
        <table>
            <tr>
                <th>Cantidad Devuelta</th>
                <th>Código</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Sub-Total</th>
            </tr>
            @foreach ($orden->detalles as $key => $dt)
                @if ($dt->devuelto > 0)
                    <tr>
                        <td>{{ $dt->devuelto }}</td>
                        <td>{{ $dt->producto->codigo }}</td>
                        <td style="text-align: left;">
                            {{ $dt->producto->descripcion }},
                            {{ $dt->producto->presentacion->presentacion }},
                            {{ $dt->producto->marca->marca }}
                        </td>
                        <td style="text-align: right;">{{ Number::currency($dt->precio, 'GTQ') }}</td>
                        <td style="text-align: right;">{{ Number::currency($dt->devuelto * $dt->precio, 'GTQ') }}
                    </tr>
                    @php
                        $total += $dt->devuelto * $dt->precio;
                    @endphp
                @endif
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
            <div style="font-weight: bold; font-size:20px;">ORDEN #{{ $orden->id }}</div>
            <div class="salto">
                CERTIFICADOR: INFILE, S.A. NIT: 12521337
            </div>
        </div>
    </section>
</body>

</html>
