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
                <div>{{ env('RAZON_SOCIAL') }}</div>
                <div>{{ env('NOMBRE_COMERCIAL') }}</div>
                <div>{{ env('DIRECCION') }}</div>
                <div>{{ env('MUNICIPIO') }}, {{ env('DEPARTAMENTO') }}</div>
                <div>PBX: {{ env('PBX') }}</div>
                <div>Whatsapp: {{ env('WHATSAPP') }}</div>
                <div>NIT: {{ env('NIT') }}</div>

                <div class="salto">
                    <div>
                        @if ($venta->facturar_cf)
                            Nit Cliente: CF
                        @else
                            Nit Cliente: {{ @$venta->cliente->nit }}
                        @endif
                    </div>
                    <div>
                        @if ($venta->facturar_cf)
                            Nombre Comercial: {{ @$venta->cliente->name }}
                        @else
                            Razon Social: {{ @$venta->cliente->razon_social }}
                            <br />
                            Nombre Comercial: {{ @$venta->cliente->name }}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div style="display: table-cell; width: 50%; text-align: right;">
            <div style="font-weight: bold; font-size:25px;">NOTA DE CRÉDITO VENTA #{{ $venta->id }}</div>
            <div class="salto"></div>
            <div>FEL, Documento Tributario Electronico</div>
            <div class="salto">
                <<div>No. Autorización: {{ $venta->devolucion->fel_uuid }}
            </div>
            <div>No. Serie: {{ $venta->devolucion->fel_serie }} No. DTE: {{ $venta->devolucion->fel_numero }}</div>
            <div>Fecha de Certificación: {{ $venta->devolucion->fel_fecha }}</div>
            <div>Moneda: GTQ</div>
        </div>
        <div class="salto"></div>
        <div class="salto">
            <div>ASESOR</div>
            <div>Nombre: {{ $venta->asesor->name }}</div>
            <div>Teléfono: {{ $venta->asesor->telefono }}</div>
        </div>
        </div>
    </header>
    <br><br>
    <div style="font-weight: bold; font-size:16px;">Monto Documento Origen:
        {{ Number::currency($venta->total, 'GTQ') }}</div>

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
            @foreach ($venta->detalles as $key => $dt)
                @if ($dt->devuelto > 0)
                    <tr>
                        <td>{{ $dt->devuelto }}</td>
                        <td>{{ $dt->producto->codigo }}</td>
                        <td style="text-align: left;">
                            {{ $dt->producto->descripcion }},
                            {{-- {{ $dt->producto->presentacion->presentacion }}, --}}
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
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td style="text-align: right;">TOTAL</td>
                <td style="text-align: right;">{{ Number::currency($venta->total, 'GTQ') }}</td>
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
            <div style="font-weight: bold; font-size:20px;">VENTA #{{ $venta->id }}</div>
            <div class="salto">
                CERTIFICADOR: INFILE, S.A. NIT: 12521337
            </div>
        </div>
    </section>
</body>

</html>
