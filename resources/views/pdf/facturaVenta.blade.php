<!DOCTYPE html>
<html>

<head>
    <title>
        @if ($venta->comp)
            Orden {{ $venta->id }}
        @else
            @if ($venta->factura->fel_tipo == 'FCAM')
                Factura Cambiaria {{ $venta->id }}
            @else
                Factura {{ $venta->id }}
            @endif
        @endif
    </title>
    <meta charset="utf-8">
    <style>
        @page {
            size: 3in auto;
            margin: 0 5px 0 5px;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            font-size: 11.5px;
            padding: 12px;
            font-weight: bold;
        }

        .header,
        .footer {
            text-align: center;
            margin-bottom: 8px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .descripcion {
            width: auto;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
        }

        .table th,
        .table td {
            padding: 2px 0;
            border-bottom: 0.5px solid black;
            text-align: left;
        }

        .table th {
            border-bottom: 1px solid black;
        }

        /* La columna de descripción se adapta al contenido */
        .table .descripcion {
            width: auto;
            white-space: normal;
            word-wrap: break-word;
        }

        /* Las columnas de cantidad, precio y subtotal tienen ancho fijo */
        .table .cantidad,
        .table .precio,
        .table .subtotal {
            text-align: center;
            width: 20%;
            white-space: nowrap;
        }

        footer {
            border-top: 2px solid black;
            padding-top: 10px;
        }

        footer div {
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <header style="text-align: center;">
        <img src="{{ public_path('/images/logo.png') }}" alt="Logo"
            style="max-width: 80%; filter: grayscale(100%) brightness(0);">
        <br><br>
        <div>
            <div>{{ config('services.fel.razon_social') }}</div>
            <div>{{ config('services.fel.nombre_comercial') }}</div>
            <div>{{ config('services.fel.direccion') }}</div>
            <div>{{ config('services.fel.municipio') }}, {{ config('services.fel.departamento') }}</div>
            <div>PBX: {{ config('services.fel.pbx') }}</div>
            <div>Whatsapp: {{ config('services.fel.whatsapp') }}</div>
            <div>NIT: {{ config('services.fel.nit') }}</div>
        </div>
        <br>
        <div>
            @if ($venta->comp)
                <div>VENTA #{{ $venta->id }}</div>
            @else
                @if ($venta->fel_tipo == 'FCAM')
                    <div style="font-size: 10px;">FACTURA CAMBIARIA</div>
                @else
                    <div style="font-size: 10px;">FACTURA</div>
                @endif
                <div style="font-size: 10px;">FEL, Documento Tributario Electronico</div>
                <div style="font-size: 10px;">
                    <div style="font-size: 10px;">AUT: {{ $venta->factura->fel_uuid }}</div>
                    <div style="font-size: 10px;">Serie: {{ $venta->factura->fel_serie }}</div>
                    <div style="font-size: 10px;">DTE: {{ $venta->factura->fel_numero }}</div>
                    <div style="font-size: 10px;">Emisión: {{ $venta->factura->fel_fecha }}</div>
                    <div style="font-size: 10px;">Certificación: {{ $venta->factura->fel_fecha }}</div>
                    <div style="font-size: 10px;">Moneda: GTQ</div>
                </div>
            @endif
            <br>
            <div style="text-align: left;">
                <div>
                    @if ($venta->facturar_cf)
                        Nit Cliente: CF
                    @else
                        @if ($venta->cliente->nit)
                            Nit Cliente: {{ @$venta->cliente->nit }}
                        @else
                            DPI Cliente: {{ @$venta->cliente->dpi }}
                        @endif
                    @endif
                </div>
                <div class="descripcion">
                    @if ($venta->facturar_cf)
                        @if ($venta->cliente->nombre_comercial)
                            Nombre Comercial: {{ @$venta->cliente->nombre_comercial }}
                        @endif
                    @else
                        @if ($venta->cliente->nit)
                            Razon Social: {{ @$venta->cliente->razon_social }}
                        @else
                            Nombre Comercial: {{ @$venta->cliente->nombre_comercial }}
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </header>

    <section>
        <br><br>
        <table class="table">
            <tr>
                <th class="descripcion">DESCRIP</th>
                <th class="cantidad">CANT</th>
                <th class="precio">PRECIO</th>
                <th class="subtotal">SUBT</th>
            </tr>
            @foreach ($venta->detalles as $dt)
                <tr>
                    <td class="descripcion">
                        {{ $dt->producto->id }} - {{ $dt->producto->descripcion }}- {{ $dt->producto->talla }}'',
                        {{ $dt->producto->marca->marca }}
                    </td>
                    <td class="cantidad">{{ $dt->cantidad }}</td>
                    <td class="precio">{{ number_format($dt->precio, 2) }}</td>
                    <td class="subtotal">{{ number_format($dt->cantidad * $dt->precio, 2) }}</td>
                </tr>
            @endforeach
            {{-- @if ($venta->envio > 0)
                <tr>
                    <td>SERVICIO DE ENVÍO</td>
                    <td></td>
                    <td class="precio">{{ number_format($venta->envio, 2) }}</td>
                    <td class="subtotal">{{ number_format($venta->envio, 2) }}</td>
                </tr>
            @endif --}}
            <tr>
                <td></td>
                <td></td>
                <td class="precio">TOTAL</td>
                <td class="subtotal">{{ number_format($venta->total, 2) }}</td>
            </tr>
        </table>
    </section>
    <br>
    <div>
        <div>VENTA #{{ $venta->id }}</div>
        <div>ASESOR: {{ $venta->asesor->name }}</div>
    </div>
    <br>
    @if ($venta->comp)
        <div></div>
    @else
        <section style="text-align: center;">
            <div>
                <div>FRASES</div>
                <div>Sujeto a pagos trimestrales ISR</div>
                <div>Agente de Retención del IVA</div>
            </div>
            <div>
                <div>
                    CERTIFICADOR: INFILE, S.A. NIT: 12521337
                </div>
            </div>
        </section>
    @endif
    <br>
    <br>
    <footer>
        @if (!$venta->comp && $venta->fel_tipo == 'FCAM')
            <div>
                <div style="background: black; color: white; text-align: center; padding: 5px; font-weight: bold;">
                    COMPLEMENTOS
                </div>
                <div style="padding: 5px; text-align: center; font-weight: bold;">
                    ABONOS DE FACTURA CAMBIARIA
                </div>
                <table style="width: 100%; border-collapse: collapse; text-align: center; font-size: 10px;">
                    <thead>
                        <tr style="background: #f0f0f0;">
                            <th style="border: 1px solid black; padding: 3px;">No. Abono</th>
                            <th style="border: 1px solid black; padding: 3px;">Fecha de Vencimiento</th>
                            <th style="border: 1px solid black; padding: 3px;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="border: 1px solid black; padding: 3px;">1</td>
                            <td style="border: 1px solid black; padding: 3px;">
                                {{ Carbon\Carbon::parse($venta->created_at)->addMonths(1)->format('d-m-Y') }}
                            </td>
                            <td style="border: 1px solid black; padding: 3px;">
                                {{ number_format($venta->total) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <br>

            <div style="font-size: 10px;">
                <strong>CONDICIONES GENERALES:</strong>
                <div style="padding-left: 15px; margin-top: 5px;">
                    <li>La mercadería será de nuestra propiedad hasta su cancelación.</li>
                    <li>Esta factura cambiaria no se considera cancelada sin el recibo de caja correspondiente.</li>
                    <li>El comprador acepta el valor de esta factura y se compromete a cancelarlo al vencimiento pactado
                        en las oficinas del vendedor o de tercera persona autorizada. En caso de incumplimiento,
                        renuncia expresamente al fuero de su domicilio y se somete a los tribunales de Guatemala o
                        cualquier otro que el vendedor elija.</li>
                    <li>La firma de cualquier empleado o dependiente del comprador al aceptar esta factura, obligará a
                        éste a cumplir con todas las condiciones estipuladas.</li>
                    <li>El comprador acepta como buenos los intereses y gastos por mora estipulados por el vendedor.
                    </li>
                </div>
            </div>

            <div style="margin-top: 40px; text-align: center;">
                <div
                    style="border-top: 1px solid black; width: 70%; margin: 10px auto; padding-top: 5px; font-weight: bold;">
                    Firma de Aceptación del Comprador
                </div>
            </div>
        @endif
    </footer>

</body>

</html>
