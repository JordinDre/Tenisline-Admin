<!DOCTYPE html>
<html>

<head>
    <title>Reporte Cierre {{ $cierre->id }}</title>
    <meta charset="utf-8">
    <style>
        @page {
            size: 3in auto;
            margin: 0 5px 0px 5px;
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
            border: 1px solid black;
            padding: 5px;
        }

        .info-section {
            margin-bottom: 10px;
            border: 1px solid black;
            padding: 5px;
        }

        .info-section div {
            margin-bottom: 2px;
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
        .table .cantidad { width: 10%; },
        .table .precio { width: 25%; },
        .table .subtotal {
            text-align: center;
            width: 25%;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <header style="text-align: center;">
        <h3>Cierre #{{ $cierre->id }}</h3>
        <img src="{{ public_path('/images/logo.png') }}" alt="Logo"
            style="max-width: 80%; filter: grayscale(100%) brightness(0);">
        <br><br><b>
            Fecha Apertura:</b> {{ ($cierre->apertura) }} - Fecha Cierre:</b> {{ ($cierre->cierre) }}
    </header>

    <section class="info-section">
        <div><strong>Datos de Cierre #{{ $cierre->id }}</strong></div>
        <div>Bodega: {{ $cierre->bodega?->bodega ?? 'N/A' }}</div>
        <div>Usuario: {{ $cierre->user?->name ?? 'N/A' }}</div>
    </section>

    <br><br>
    <table class="table">
        <tr>
            <th class="descripcion">Venta</th>
            <th class="cantidad">Cant</th>
            <th class="precio">Precio</th>
            <th class="subtotal">Subtotal</th>
        </tr>
        @foreach ($cierre->ventas_detalles as $venta)
            <tr>
                <td colspan="4" style="border-top: 1px solid black;"><strong>Venta #{{ $venta->id }}</strong></td>
            </tr>
            @foreach ($venta->detalles as $detalle)
                <tr>
                    <td class="descripcion">{{ $detalle->producto->codigo . ' - ' . $detalle->producto->descripcion . ' - ' . ($detalle->producto->marca?->marca ?? 'Sin marca') }}</td>
                    <td class="cantidad">{{ $detalle->cantidad }}</td>
                    <td class="precio">Q {{ number_format($detalle->precio, 2) }}</td>
                    <td class="subtotal">Q {{ number_format($detalle->cantidad * $detalle->precio, 2) }}</td>
                </tr>
            @endforeach
        @endforeach
    </table>
    <br>

    @if (count($cierre->datos_caja_chica) > 0)
        <br><br>
        <table class="table">
            <tr>
                <th class="descripcion">Detalle</th>
                <th class="precio">Autoriza</th>
                <th class="subtotal">Usuario</th>
                <th class="subtotal">Monto</th>
            </tr>
                @foreach ($cierre->datos_caja_chica as $caja_chica)
                    <tr>
                        <td colspan="4" style="border-top: 1px solid black;">
                            <strong>
                                Caja Chica #{{ $caja_chica->id }}
                            </strong>

                            @if(!$caja_chica->aplicado)
                                <span style="color: orange;">(Pendiente)</span>
                            @elseif($caja_chica->aplicado_en_cierre_id === $cierre->id)
                                <span style="color: green;">(Aplicado en este cierre)</span>
                            @else
                                <span style="color: gray;">(Aplicado en cierre #{{ $caja_chica->aplicado_en_cierre_id }})</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="descripcion">{{ $caja_chica->detalle_gasto }}</td>
                        <td class="descripcion">{{ $caja_chica->autoriza }}</td>
                        <td class="descripcion">{{ $caja_chica->usuario->name }}</td>
                        <td class="subtotal">Q {{ number_format($caja_chica->pagos->sum('monto'), 2) }}</td>
                    </tr>
                @endforeach
            <tr>
                <td colspan="3" style="text-align: right; border-top: 2px solid black;"><strong>Total Caja Chica:</strong></td>
                <td class="subtotal" style="border-top: 2px solid black;"><strong>Q {{ number_format($cierre->total_caja_chica, 2) }}</strong></td>
            </tr>
        </table>
        <br>
    @endif

    <section class="info-section">
        <div><strong>Resumen</strong></div>
        <div>Total en Ventas: Q {{ number_format($cierre->total_ventas, 2) }}</div>
        <div>Total Tenis Vendidos: {{ number_format($cierre->total_tenis, 0) }}</div>
    
        @php $pagos = $cierre->resumen_pagos; @endphp
       
            <div><strong>Resumen de Pagos:</strong></div>
            <ul style="padding-left: 15px; margin: 0;">
                @foreach ($pagos as $pago)
                    <li>{{ $pago }}</li>
                @endforeach
            </ul>
            <ul style="padding-left: 15px; margin: 0;">
                <li>Caja Chica: - Q {{ number_format($cierre->total_caja_chica, 0) }}</li>
            </ul>
            <div>Total General: Q {{ number_format(($cierre->total_ventas - $cierre->total_caja_chica), 2) }}</div>
     
    </section>
</body>

</html>
