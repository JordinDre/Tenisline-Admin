<!DOCTYPE html>
<html>

<head>
    <title>Traslado {{ $traslado->id }}</title>
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
        .table .codigo {
            text-align: center;
            width: 10%;
            white-space: nowrap;
        },
        .table .descripcion {
            text-align: center;
            width: 20%;
            white-space: nowrap;
        },
        .table .marca {
            text-align: center;
            width: 15%;
            white-space: nowrap;
        }
        .table .enviada {
            text-align: center;
            width: 10%;
            white-space: nowrap;
        }
        .table .recibida {
            text-align: center;
            width: 10%;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <header style="text-align: center;">
        <h3>Traslado #{{ $traslado->id }}</h3>
        <img src="{{ public_path('/images/logo.png') }}" alt="Logo"
            style="max-width: 80%; filter: grayscale(100%) brightness(0);">
        <br><br><b>
            Fecha:</b> {{ date_format($traslado->created_at, 'd-m-Y') }}
    </header>

    <section class="info-section">
        <div><strong>Datos del Traslado #{{ $traslado->id }}</strong></div>
        <div>Estado: {{ $traslado->estado->value }}</div>
        <div>Fecha Preparado: {{ $traslado->fecha_preparado }}</div>
        <div>Fecha Salida: {{ $traslado->fecha_salida }}</div>
        <div>Fecha Recibido: {{ $traslado->fecha_recibido }}</div>
        <div>Fecha Confirmado: {{ $traslado->fecha_confirmado }}</div>
        <div>Fecha Anulado: {{ $traslado->fecha_anulado }}</div>
        <div class="descripcion">Observaciones: {{ $traslado->observaciones }}</div>
    </section>

    <section class="info-section">
        <div><strong>Emisor</strong></div>
        <div>Nombre: {{ $traslado->emisor->name ?? 'No disponible'}}</div>
        <div>Teléfono: {{ $traslado->emisor->telefono ?? 'No disponible'}}</div>
    </section>

    <section class="info-section">
        <div><strong>Receptor</strong></div>
        <div>Nombre: {{ $traslado->receptor->name ?? 'No disponible'}}</div>
        <div>Teléfono: {{ $traslado->receptor->telefono ?? 'No disponible'}}</div>
    </section>

    <section class="info-section">
        <div><strong>Piloto</strong></div>
        <div>Nombre: {{ $traslado->piloto->name ?? 'No disponible'}}</div>
        <div>Teléfono: {{ $traslado->piloto->telefono ?? 'No disponible'}}</div>
    </section>

    <br><br>
    <table class="table">
        <tr>
            <th class="codigo">COD</th>
            <th class="descripcion">DESCR</th>
            <th class="marca">MARCA</th>
            <th class="enviada">ENV</th>
            <th class="recibida">REC</th>
        </tr>
        @foreach ($traslado->detalles as $dt)
            <tr>
                <td>{{ $dt->producto->codigo ?? 'N/A' }}</td>
                <td>{{ $dt->producto->descripcion ?? 'N/A' }}</td>
                <td>{{ $dt->producto->marca->marca ?? 'N/A' }}</td>
                <td>{{ $dt->cantidad_enviada ?? 'N/A' }}</td>
                <td>{{ $dt->cantidad_recibida ?? 'N/A' }}</td>
            </tr>
        @endforeach
    </table>
    <br>
</body>

</html>
