@php
    use Illuminate\Support\Number;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 7pt;
            color: #333;
        }

        h3 {
            font-size: 12pt;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            padding: 5px 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #157CC3;
            /* Color solicitado */
            color: white;
            font-size: 8pt;
        }

        td {
            font-size: 7pt;
        }

        .right {
            text-align: right;
        }

        .left {
            text-align: left;
        }

        .section-title {
            font-weight: bold;
            background-color: #f9f9f9;
            padding: 5px;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #157CC3;
        }

        .highlight {
            border-top: 2px solid #157CC3;
            border-bottom: 2px solid #157CC3;
            font-weight: bold;
        }

        .header {
            font-size: 10px;
        }

        .data-table th {
            font-size: 8pt;
        }

        .details-table th {
            font-size: 8pt;
            background-color: #157CC3;
        }

        .details-table td {
            font-size: 7pt;
        }

        .separator {
            margin: 15px 0;
            border-top: 1px solid #D5D8DC;
        }

        .logo {
            max-width: 150px;
        }

        .time-cell {
            border-top: 1px solid #157CC3;
            border-bottom: 1px solid #157CC3;
            padding: 5px;
        }
    </style>
</head>

<body>

    <div class="header">
        <table width="100%">
            <tr>
                <td>
                    <img src="{{ public_path('/images/logo.png') }}" alt="Logo" class="logo">
                </td>
                <td>
                    <h3 style="text-align: right">
                        Venta #{{ $venta->id }}
                    </h3>
                </td>
            </tr>
        </table>
    </div>

    <table>
        <tr class="highlight">
            <td><b>Fecha de creación:</b> {{ date_format($venta->created_at, 'd-m-Y') }}</td>
        </tr>
    </table>

    <div class="section-title">Datos del Cliente</div>
    <table class="data-table">
        <tr>
            <th>Código Cliente</th>
            <td>{{ @$venta->cliente->id }}</td>
            <th>NIT / DPI</th>
            <td>
                @if ($venta->facturar_cf)
                    CF / {{ @$venta->cliente->dpi }}
                @else
                    {{ @$venta->cliente->nit }} / {{ @$venta->cliente->dpi }}
                @endif

            </td>
        </tr>
        <tr>
            <th>Razón Social</th>
            <td>{{ @$venta->cliente->razon_social }}</td>
            <th>Nombre Comercial</th>
            <td>{{ @$venta->cliente->name }}</td>
        </tr>
    </table>

    <div class="section-title">Datos del Asesor</div>
    <table class="data-table">
        <tr>
            <th>Nombre</th>
            <td>{{ @$venta->asesor->name }}</td>
            <th>Teléfono</th>
            <td>{{ @$venta->asesor->telefono }}</td>
        </tr>
    </table>

    <div class="section-title">Productos</div>
    <table class="details-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th>Presentación</th>
                <th>Marca</th>
                <th>Cantidad</th>
                <th>Boni</th>
                <th class="right">Precio</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($venta->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->producto->codigo }}</td>
                    <td>{{ $detalle->producto->descripcion }}</td>
                    <td>{{ $detalle->producto->presentacion->presentacion }}</td>
                    <td>{{ $detalle->producto->marca->marca }}</td>
                    <td>{{ $detalle->cantidad }}</td>
                    <td class="right">{{ Number::currency($detalle->precio, 'GTQ') }}</td>
                    <td class="right">{{ Number::currency($detalle->cantidad * $detalle->precio, 'GTQ') }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="6" class="right"><b>Sub Total:</b></td>
                <td colspan="2" class="right">{{ Number::currency($venta->subtotal, 'GTQ') }}</td>
            </tr>
            <tr>
                <td colspan="6" class="right"><b>Total:</b></td>
                <td colspan="2" class="right">{{ Number::currency($venta->total, 'GTQ') }}</td>
            </tr>
        </tbody>
    </table>

</body>

</html>
