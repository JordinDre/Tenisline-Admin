@php
    use Illuminate\Support\Number;
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            margin: 20px;
            padding: 0;
            color: #333;
        }

        h3 {
            font-size: 16pt;
            margin: 0;
            padding-bottom: 10px;
            text-align: center;
            font-weight: normal;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            vertical-align: middle;
            border: 1px solid #ccc;
        }

        th {
            background-color: #f1f1f1;
            font-weight: normal;
        }

        td {
            font-size: 9pt;
        }

        .right {
            text-align: right;
        }

        .left {
            text-align: left;
        }

        .section-title {
            font-size: 10pt;
            font-weight: bold;
            margin-top: 20px;
            padding-bottom: 5px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9pt;
            color: #777;
        }

        .separator {
            margin: 20px 0;
            border-top: 1px solid #ccc;
        }

        .logo {
            display: block;
            margin: 0 auto;
            width: 120px;
            /* Ajusta el tamaño del logo */
            padding-bottom: 10px;
        }
    </style>
</head>

<body>
    <img src="{{ public_path('/images/logo.png') }}" alt="Logo" class="logo">
    <h3>
        Cotización #{{ $orden->id }}
    </h3>

    <table>
        <tr>
            <td><b>Fecha de creación:</b> {{ date_format($orden->created_at, 'd-m-Y') }}</td>
            @if ($orden->prefechado)
                <td class="right"><b>Prefechado:</b> {{ date_format($orden->prefechado, 'd-m-Y') }}</td>
            @endif
        </tr>
    </table>

    <div class="section-title">Datos del Cliente</div>
    <table>
        <tr>
            <th>Código Cliente</th>
            <td>{{ @$orden->cliente->id }}</td>
            <th>NIT / DPI</th>
            <td>
                @if ($orden->facturar_cf)
                    CF / {{ @$orden->cliente->dpi }}
                @else
                    {{ @$orden->cliente->nit }} / {{ @$orden->cliente->dpi }}
                @endif
            </td>
        </tr>
        <tr>
            <th>Razón Social</th>
            <td>{{ @$orden->cliente->razon_social }}</td>
            <th>Nombre Comercial</th>
            <td>{{ @$orden->cliente->name }}</td>
        </tr>
    </table>

    <div class="section-title">Datos de Orden</div>
    <table>
        <tr>
            <th>Dirección de Entrega</th>
            <td colspan="3">{{ @$orden->direccion->direccion }}, {{ @$orden->direccion->referencia }},
                {{ @$orden->direccion->municipio->municipio }},
                {{ @$orden->direccion->municipio->departamento->departamento }}</td>
        </tr>
        <tr>
            <th>Encargado</th>
            <td>{{ @$orden->direccion->encargado }}</td>
            <th>Tel. Encargado / Tel. Cliente</th>
            <td>{{ @$orden->direccion->encargado_contacto . ' / ' . @$orden->cliente->telefono }}</td>
        </tr>
        <tr>
            <th>Tipo de Pago</th>
            <td>{{ @$orden->tipo_pago->tipo_pago }}</td>
            <th>Estado Orden</th>
            <td>{{ @$orden->estado->value }}</td>
        </tr>
    </table>

    <div class="section-title">Datos del Asesor</div>
    <table>
        <tr>
            <th>Nombre</th>
            <td>{{ @$orden->asesor->name }}</td>
            <th>Teléfono</th>
            <td>{{ @$orden->asesor->telefono }}</td>
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
                <th>Cantidad</th>
                <th>Boni</th>
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
                    <td>{{ $detalle->cantidad }}</td>
                    <td class="right">{{ Number::currency($detalle->precio, 'GTQ') }}</td>
                    <td class="right">{{ Number::currency($detalle->cantidad * $detalle->precio, 'GTQ') }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="6" class="right"><b>Sub Total:</b></td>
                <td colspan="2" class="right">{{ Number::currency($orden->subtotal, 'GTQ') }}</td>
            </tr>
            @if ($orden->envio > 0)
                <tr>
                    <td colspan="6" class="right"><b>Envío:</b></td>
                    <td colspan="2" class="right">{{ Number::currency($orden->envio, 'GTQ') }}</td>
                </tr>
            @endif
            <tr>
                <td colspan="6" class="right"><b>Total:</b></td>
                <td colspan="2" class="right">{{ Number::currency($orden->total, 'GTQ') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Gracias por su preferencia.</p>
        <p>Esta cotización es válida por los próximos 15 días.</p>
    </div>

</body>

</html>
