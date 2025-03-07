@php
    use Illuminate\Support\Number;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo #{{ $orden->id }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8pt;
            color: #000;
            line-height: 1.2;
            margin: 0;
        }

        h3 {
            font-size: 10pt;
            margin-bottom: 5px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            padding: 5px;
            text-align: left;
        }

        th {
            font-size: 8pt;
            border-bottom: 1px dashed #000;
        }

        td {
            font-size: 8pt;
        }

        .right {
            text-align: right;
        }

        .left {
            text-align: left;
        }

        .section-title {
            font-weight: bold;
            padding: 5px;
            border-top: 2px solid #000;
            border-bottom: 1px solid #000;
            margin-top: 10px;
            margin-bottom: 5px;
            background-color: #f0f0f0;
            /* Color de fondo para mejor visualización */
        }

        .section-content {
            padding: 5px;
            border: 1px solid #000;
            /* Borde alrededor del contenido de la sección */
            margin-bottom: 10px;
            background-color: #ffffff;
            /* Color de fondo blanco */
        }

        .total-row {
            border-top: 2px solid #000;
            font-weight: bold;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .logo {
            max-width: 100px;
            margin: 0 auto;
        }

        .separator {
            margin: 10px 0;
            border-top: 1px dashed #000;
        }

        .highlight {
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
    </style>
</head>

<body>

    <div class="header">
        <img src="{{ public_path('/images/logo.png') }}" alt="Logo" class="logo">
        <h3>Recibo de Orden #{{ $orden->id }}</h3>
    </div>

    <div class="section-title">Fecha: {{ date_format($orden->created_at, 'd-m-Y') }}</div>

    <div class="section-title">Datos del Cliente</div>
    <div class="section-content">
        <table>
            <tr>
                <td><b>Código Cliente:</b> {{ @$orden->cliente->id }}</td>
                <td><b>NIT / DPI:</b>
                    @if ($orden->facturar_cf)
                        CF / {{ @$orden->cliente->dpi }}
                    @else
                        {{ @$orden->cliente->nit }} / {{ @$orden->cliente->dpi }}
                    @endif
                </td>
            </tr>
            <tr>
                <td><b>Razón Social:</b> {{ @$orden->cliente->razon_social }}</td>
                <td><b>Nombre Comercial:</b> {{ @$orden->cliente->name }}</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Datos de Orden</div>
    <div class="section-content">
        <table>
            <tr>
                <td><b>No. Autorización:</b> {{ @$orden->factura->fel_uuid }}</td>
                <td><b>No. Serie:</b> {{ @$orden->factura->fel_serie }}</td>
            </tr>
            <tr>
                <td><b>No. DTE:</b> {{ @$orden->factura->fel_numero }}</td>
                <td><b>Fecha Factura:</b> {{ @$orden->factura->fel_fecha }}</td>
            </tr>
            <tr>
                <td><b>Encargado:</b> {{ @$orden->cliente->encargado }}</td>
                <td><b>Tel. Encargado / Tel. Cliente:</b>
                    {{ @$orden->direccion->encargado_contacto . ' / ' . @$orden->cliente->telefono }}</td>
            </tr>
            <tr>
                <td><b>Tipo de Pago:</b> {{ @$orden->tipo_pago->tipo_pago }}</td>
                <td><b>Paquetes/CC:</b></td>
            </tr>
            <tr>
                <td><b>Tracking:</b> {{ @$orden->tipo_pago->tipo_pago }}</td>
                <td><b>Tracking CC:</b> {{ @$orden->tipo_pago->tipo_pago }}</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Datos del Asesor</div>
    <div class="section-content">
        <table>
            <tr>
                <td><b>Nombre Asesor:</b> {{ @$orden->asesor->name }}</td>
                <td><b>Teléfono:</b> {{ @$orden->asesor->telefono }}</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Productos</div>
    <div class="section-content">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Presentación</th>
                    <th>Marca</th>
                    <th class="right">Cantidad</th>
                    <th class="right">Boni</th>
                    <th class="right">Precio</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orden->detalles as $detalle)
                    <tr>
                        <td>{{ $detalle->producto->codigo }}</td>
                        <td>{{ $detalle->producto->descripcion }}</td>
                        <td>{{ $detalle->producto->presentacion->presentacion }}</td>
                        <td>{{ $detalle->producto->marca->marca }}</td>
                        <td class="right">{{ $detalle->cantidad }}</td>
                        <td class="right">{{ Number::currency($detalle->precio, 'GTQ') }}</td>
                        <td class="right">{{ Number::currency($detalle->cantidad * $detalle->precio, 'GTQ') }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="5" class="right"><b>Sub Total:</b></td>
                    <td colspan="3" class="right">{{ Number::currency($orden->subtotal, 'GTQ') }}</td>
                </tr>
                @if ($orden->envio > 0)
                    <tr>
                        <td colspan="5" class="right"><b>Envío:</b></td>
                        <td colspan="3" class="right">{{ Number::currency($orden->envio, 'GTQ') }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td colspan="5" class="right"><b>Total:</b></td>
                    <td colspan="3" class="right">{{ Number::currency($orden->total, 'GTQ') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

</body>

</html>
