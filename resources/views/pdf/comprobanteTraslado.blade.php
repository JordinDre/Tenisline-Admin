@php
    use Illuminate\Support\Number;
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page {
            margin: 20mm;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 12px;
        }

        .container {
            width: 100%;
            margin: 0 auto;
            padding: 15px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .header img {
            width: 100px;
            height: auto;
        }

        .header h3 {
            font-size: 14px;
            color: #333;
            margin: 0;
            text-align: right;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-top: 20px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ccc;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }

        th {
            background-color: #f9f9f9;
            color: #333;
        }

        .right {
            text-align: right;
        }

        .highlight {
            background-color: #f9f9f9;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #777;
        }

        .footer p {
            margin: 0;
        }

        .generated-time {
            font-size: 10px;
            font-style: italic;
            color: #777;
        }

        .small-text {
            font-size: 9px;
            color: #555;
        }

        .borderless {
            border: none;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <img src="{{ public_path('/images/logo.png') }}" alt="Logo">
            <h3>Traslado #{{ $traslado->id }}</h3>
        </div>

        <table>
            <tr>
                <td class="highlight"><b>Fecha de Creación:</b>
                    {{ $traslado->created_at instanceof \Carbon\Carbon ? $traslado->created_at->format('d-m-Y') : 'Fecha no disponible' }}
                </td>
            </tr>
            <tr>
                <td><b>Estado:</b> {{ $traslado->estado->value }}</td>
            </tr>
        </table>

        @if ($traslado->fecha_preparado)
            <table>
                <tr>
                    <td><b>Fecha Preparado:</b> {{ $traslado->fecha_preparado }}</td>
                </tr>
            </table>
        @endif

        @if ($traslado->fecha_salida)
            <table>
                <tr>
                    <td><b>Fecha Salida:</b> {{ $traslado->fecha_salida }}</td>
                </tr>
            </table>
        @endif

        @if ($traslado->fecha_recibido)
            <table>
                <tr>
                    <td><b>Fecha Recibido:</b> {{ $traslado->fecha_recibido }}</td>
                </tr>
            </table>
        @endif

        @if ($traslado->fecha_confirmado)
            <table>
                <tr>
                    <td><b>Fecha Confirmado:</b> {{ $traslado->fecha_confirmado }}</td>
                </tr>
            </table>
        @endif

        @if ($traslado->fecha_anulado)
            <table>
                <tr>
                    <td><b>Fecha Anulado:</b> {{ $traslado->fecha_anulado }}</td>
                </tr>
            </table>
        @endif

        <div class="section-title">Emisor</div>
        <table class="borderless">
            <tr>
                <th>Nombre</th>
                <td>{{ $traslado->emisor->name ?? 'No disponible' }}</td>
                <th>Teléfono</th>
                <td>{{ $traslado->emisor->telefono ?? 'No disponible' }}</td>
            </tr>
        </table>

        <div class="section-title">Receptor</div>
        <table class="borderless">
            <tr>
                <th>Nombre</th>
                <td>{{ $traslado->receptor->name ?? 'No disponible' }}</td>
                <th>Teléfono</th>
                <td>{{ $traslado->receptor->telefono ?? 'No disponible' }}</td>
            </tr>
        </table>

        <div class="section-title">Piloto</div>
        <table class="borderless">
            <tr>
                <th>Nombre</th>
                <td>{{ $traslado->piloto->name ?? 'No disponible' }}</td>
                <th>Teléfono</th>
                <td>{{ $traslado->piloto->telefono ?? 'No disponible' }}</td>
            </tr>
        </table>

        <div class="section-title">Productos</div>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Presentación</th>
                    <th>Marca</th>
                    <th>Enviado</th>
                    <th>Recibido</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($traslado->detalles as $detalle)
                    <tr>
                        <td>{{ $detalle->producto->codigo ?? 'N/A' }}</td>
                        <td>{{ $detalle->producto->descripcion ?? 'N/A' }}</td>
                        <td>{{ $detalle->producto->presentacion->presentacion ?? 'N/A' }}</td>
                        <td>{{ $detalle->producto->marca->marca ?? 'N/A' }}</td>
                        <td>{{ $detalle->cantidad_enviada ?? 'N/A' }}</td>
                        <td>{{ $detalle->cantidad_recibida ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            <p class="generated-time">Fecha y hora de generación: {{ now()->format('d-m-Y H:i:s') }}</p>
        </div>
    </div>

</body>

</html>
