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
                    {{ $dt->producto->id }} - {{ $dt->producto->descripcion }} - {{ $dt->producto->talla }}'', {{ $dt->producto->marca->marca }}
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
</body>

</html>
