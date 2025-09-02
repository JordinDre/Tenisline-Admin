<!DOCTYPE html>
<html>

<head>
    <title>Venta {{ $venta->id }}</title>
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
        .table .cantidad,
        .table .precio,
        .table .subtotal {
            text-align: center;
            width: 20%;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <header style="text-align: center;">
        <h3>Venta #{{ $venta->id }}</h3>
        <img src="{{ public_path('/images/logo.png') }}" alt="Logo"
            style="max-width: 80%; filter: grayscale(100%) brightness(0);">
        <br><br><b>
            Fecha:</b> {{ date_format($venta->created_at, 'd-m-Y') }}
    </header>

    <section class="info-section">
        <div><strong>Datos de Orden #{{ $venta->id }}</strong></div>
        <div>Tipo de Pago: {{ $venta->pagos->first()->tipoPago->tipo_pago ?? 'Sin Tipo de Pago' }}</div>
        <div>Estado: {{ $venta->estado->value }}</div>
        <div class="descripcion">Observaciones: {{ $venta->observaciones }}</div>
    </section>

    <section class="info-section">
        <div><strong>Datos de Cliente</strong></div>
        <div>Código Cliente: {{ $venta->cliente->id }}</div>
        <div>NIT: {{ $venta->cliente->nit }}</div>
        <div class="descripcion">Razón Social: {{ $venta->cliente->razon_social }}</div>
        <div class="descripcion">Nombre Comercial: {{ $venta->cliente->name }}</div>
        {{-- <div>Contacto: {{ @$venta->cliente->telefono }}</div> --}}
        <div>Teléfono: {{ @$venta->cliente->telefono }}</div>
    </section>

    <section class="info-section">
        <div><strong>Datos Asesor</strong></div>
        <div>Codigo: {{ $venta->asesor->id }}</div>
        <div >Nombre: {{ $venta->asesor->name }}</div>
        <div>Teléfono: {{ $venta->asesor->telefono }}</div>
    </section>

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
                    {{ $dt->producto->codigo . ' - ' . $dt->producto->descripcion . ' - ' . $dt->producto->marca->marca }}
                </td>
                <td class="cantidad">{{ $dt->cantidad }}</td>
                <td class="precio">{{ number_format($dt->precio, 2) }}</td>
                <td class="subtotal">{{ number_format($dt->cantidad * $dt->precio, 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <td></td>
            <td></td>
            <td class="precio">TOTAL</td>
            <td class="subtotal">{{ number_format($venta->total, 2) }}</td>
        </tr>
    </table>
    <br>

    <!-- QRs del Catálogo y Bodega -->
    <div style="margin-top: 20px; position: relative; width: 100%;">
        <!-- QR del Catálogo - Esquina Izquierda -->
        <div style="position: absolute; left: 0; top: 0;">
            <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(80)->generate(route('catalogo'))) }}" 
                 alt="QR Catálogo" 
                 style="display: block;">
            <div style="font-size: 10px; margin-top: 5px; font-weight: bold; text-align: center;">
                CATALOGO
            </div>
        </div>
        
        <!-- QR de la Bodega - Esquina Derecha -->
        @php
            $bodegaName = strtolower($venta->bodega->bodega ?? '');
            $qrImage = '';
            
            if (str_contains($bodegaName, 'chiquimula')) {
                $qrImage = 'qrChiquimula.jpeg';
            } elseif (str_contains($bodegaName, 'esquipulas')) {
                $qrImage = 'qrEsquipulas.jpeg';
            } elseif (str_contains($bodegaName, 'zacapa')) {
                $qrImage = 'qrZacapa.jpeg';
            }
        @endphp
        
        @if($qrImage)
            <div style="position: absolute; right: 0; top: 0;">
                <img src="{{ public_path('/images/' . $qrImage) }}" 
                     alt="QR {{ $venta->bodega->bodega }}" 
                     style="display: block; width: 80px; height: 80px;">
                <div style="font-size: 10px; margin-top: 5px; font-weight: bold; text-align: center;">
                    {{ strtoupper($venta->bodega->bodega) }}
                </div>
            </div>
        @endif
    </div>

</body>

</html>
