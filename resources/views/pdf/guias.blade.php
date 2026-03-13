@php
    use Illuminate\Support\Str;
    use Milon\Barcode\Facades\DNS1DFacade as DNS1D;
    use SimpleSoftwareIO\QrCode\Facades\QrCode;
@endphp
<!DOCTYPE html>
<html>

<head>
    <title>Guía Venta #{{ $venta->id }}</title>
    <style>
        @page {
            margin: 0.2cm;
            margin-top: 1.0cm;
            font-family: Arial, sans-serif;
        }

        section {
            font-size: 11px;
            line-height: 1.5;
        }

        .table-container {
            width: 100%;
            display: table;
            border-collapse: collapse;
        }

        .table-row {
            display: table-row;
        }

        .table-cell {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        .vertical-text {
            text-align: center;
            width: 1cm;
            height: 50%;
            position: absolute;
            right: 0;
            bottom: 0;
            font-size: 23px;
            background-color: black;
            color: white;
            padding-top: 5px;
        }

        .vertical-letter {
            display: block;
            transform: rotate(-90deg);
            line-height: 1.2;
            font-weight: bold
        }
    </style>
</head>

<body>
    @foreach ($venta->guias as $guia)
        @php
            $direccion = $venta->cliente->direcciones[0] ?? null;
            $piezas = $guia->cantidad;
            $siglas = match ($venta->bodega_id) {
                1 => 'ZAC',
                6 => 'CHQ',
                8 => 'ESQ',
                default => 'CAP',
            };
            $codigoCobro = match ($venta->bodega_id) {
                1 => config('services.guatex.codigo_cobro_zacapa'),
                6 => config('services.guatex.codigo_cobro_chiquimula'),
                8 => config('services.guatex.codigo_cobro_esquipulas'),
                default => config('services.guatex.codigo_cobro_zacapa'),
            };
            $codigoCobroCOD = match ($venta->bodega_id) {
                1 => config('services.guatex.codigo_cobro_cod_zacapa'),
                6 => config('services.guatex.codigo_cobro_cod_chiquimula'),
                8 => config('services.guatex.codigo_cobro_cod_esquipulas'),
                default => config('services.guatex.codigo_cobro_cod_zacapa'),
            };
            $felConfigKey = match ($venta->bodega_id) {
                1 => 'fel',
                6 => 'fel2',
                8 => 'fel3',
                default => 'fel',
            };
            $felConfig = config("services.{$felConfigKey}");
            $direccionRemitente = $felConfig['direccion'] ?? 'Residenciales El Sol, Barrio La Reforma Zona 2, Zacapa, Zacapa';
            $nombreRemitente = $felConfig['nombre_comercial'] ?? 'TENISLINE';
            $telefonoRemitente = $felConfig['whatsapp'] ?? ($venta->asesor['telefono'] ?? '');
            $telefonoRemitente = str_replace('+502', '', $telefonoRemitente);
            $telefonoRemitente = ltrim($telefonoRemitente, '502'); // Remove if starts with 502 without +
            $telefonoRemitente = trim($telefonoRemitente);
        @endphp

        {{-- Main tracking barcode --}}
        <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($guia->tracking, 'C128', 1.92, 90) }}"
            alt="Código de Barras">
        <div style="font-size: 13px; letter-spacing: 1.5px;">
            {{ $siglas }} - {{ $guia->tracking }}
        </div>

        @if ($venta->tipo_pago_id == 3)
            <div class="vertical-text">
                <div class="vertical-letter">O</div>
                <div class="vertical-letter">T</div>
                <div class="vertical-letter">R</div>
                <div class="vertical-letter">E</div>
                <div class="vertical-letter">I</div>
                <div class="vertical-letter">B</div>
                <div class="vertical-letter">A</div>
                <div class="vertical-letter">-</div>
                <div class="vertical-letter">D</div>
                <div class="vertical-letter">O</div>
                <div class="vertical-letter">C</div>
            </div>
        @endif

        <section style="margin-top: 7px;">
            <div><strong>Remitente:</strong> {{ $nombreRemitente }}</div>
            <div><strong>Dirección:</strong> {{ $direccionRemitente }}</div>
            <div><strong>Teléfono:</strong> {{ $telefonoRemitente }}</div>
        </section>

        <div style="font-size: 26px; margin-top: 20px;">{{ $guia->tracking }}</div>

        <section style="margin-top: 7px;">
            <div><strong>Destinatario:</strong> {{ Str::substr(($venta->cliente['name'] . ($venta->cliente['razon_social'] ? ' - ' . $venta->cliente['razon_social'] : '')), 0, 110) }}</div>
            <div><strong>Dirección:</strong>
                @if($direccion)
                    {{ Str::substr($direccion['direccion'] . ', ' . ($direccion['zona'] ? 'ZONA ' . $direccion['zona'] . ', ' : '') . $direccion['referencia'] . ', ' . $direccion['municipio']['municipio'] . ', ' . $direccion['municipio']['departamento']['departamento'], 0, 110) }}
                @else
                    N/A
                @endif
            </div>
            <div>
                @php
                    $telDest = preg_replace('/[^0-9]/', '', ($direccion['encargado_contacto'] ?: $venta->cliente['telefono']));
                    if (str_starts_with($telDest, '502')) {
                        $telDest = substr($telDest, 3);
                    }
                @endphp
                <strong>Teléfono:</strong> {{ $telDest }}
            </div>
        </section>

        <div style="font-weight: bold; font-size: 80px; line-height: 0.8; margin-top: 35px; margin-bottom: 20px;">{{ $venta->punto_destino_guatex }}</div>

        <div style="font-size: 10px;">Desc. Envío: {{ $piezas }}</div>

        <div style="margin-top: 10px; font-size: 10px;">
            <div>No. Piezas: {{ $piezas }} Peso: 10.00 Forma de pago: {{ $venta->tipo_pago->tipo_pago ?? 'CONTADO' }}</div>
            <div>Codigo de cobro: {{ $venta->tipo_pago_id == 3 ? $codigoCobroCOD : $codigoCobro }} Fecha: {{ date_format($venta->created_at, 'd/m/Y') }}</div>
        </div>

        <div style="background-color: black; color: white; padding: 2px 5px; font-size: 8px; font-weight: bold; margin-top: 10px; display: inline-block;">
            Tracking | Condiciones Generales de Servicio
        </div>
        <div class="table-container" style="margin-top: 5px;">
            <div class="table-row">
                <div class="table-cell" style="text-align: left;">
                    <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(90)->generate('https://servicios.guatex.gt/Guatex/rastreoTracking?tipo=G&dato=' . $guia->tracking)) }}"
                        alt="Código QR" style="display: block; margin: 0 auto;">
                </div>
                <div class="table-cell" style="text-align: left;">
                    <div style="font-weight: bold; font-size: 75px;">1/{{ $piezas }}</div>
                </div>
            </div>
        </div>

        @foreach (($guia->hijas ?? []) as $key => $hija)
            <div style="page-break-before: always;"></div>
            <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($hija, 'C128', 1.92, 90) }}"
                alt="Código de Barras">
            <div style="font-size: 12px; letter-spacing: 2.5px;">
                {{ $siglas }} - {{ $hija }} - {{ $guia->tracking }}
            </div>
            
            @if ($venta->tipo_pago_id == 3)
                <div class="vertical-text">
                    <div class="vertical-letter">O</div>
                    <div class="vertical-letter">T</div>
                    <div class="vertical-letter">R</div>
                    <div class="vertical-letter">E</div>
                    <div class="vertical-letter">I</div>
                    <div class="vertical-letter">B</div>
                    <div class="vertical-letter">A</div>
                    <div class="vertical-letter">-</div>
                    <div class="vertical-letter">D</div>
                    <div class="vertical-letter">O</div>
                    <div class="vertical-letter">C</div>
                </div>
            @endif

            <section style="margin-top: 7px;">
                <div><strong>Remitente:</strong> {{ $nombreRemitente }}</div>
                <div><strong>Dirección:</strong> {{ $direccionRemitente }}</div>
                <div><strong>Teléfono:</strong> {{ $telefonoRemitente }}</div>
            </section>

            <div style="font-size: 26px; margin-top: 25px;">{{ $hija }}</div>

            <section style="margin-top: 7px;">
                <div><strong>Destinatario:</strong> {{ Str::substr(($venta->cliente['name'] . ($venta->cliente['razon_social'] ? ' - ' . $venta->cliente['razon_social'] : '')), 0, 110) }}</div>
                <div><strong>Dirección:</strong>
                    @if($direccion)
                        {{ Str::substr($direccion['direccion'] . ', ' . ($direccion['zona'] ? 'ZONA ' . $direccion['zona'] . ', ' : '') . $direccion['referencia'] . ', ' . $direccion['municipio']['municipio'] . ', ' . $direccion['municipio']['departamento']['departamento'], 0, 110) }}
                    @else
                        N/A
                    @endif
                </div>
                <div>
                    @php
                        $telDestHija = preg_replace('/[^0-9]/', '', ($direccion['encargado_contacto'] ?: $venta->cliente['telefono']));
                        if (str_starts_with($telDestHija, '502')) {
                            $telDestHija = substr($telDestHija, 3);
                        }
                    @endphp
                    <strong>Teléfono:</strong> {{ $telDestHija }}
                </div>
            </section>

            <div style="font-weight: bold; font-size: 80px; line-height: 0.8; margin-top: 40px; margin-bottom: 25px;">{{ $venta->punto_destino_guatex }}</div>

            <div style="font-size: 10px;">Desc. Envío: {{ $piezas }}</div>

            <div style="margin-top: 10px; font-size: 10px;">
                <div>No. Piezas: {{ $piezas }} Peso: 10.00 Forma de pago: {{ $venta->tipo_pago->tipo_pago ?? 'CONTADO' }}</div>
                <div>Codigo de cobro: {{ $venta->tipo_pago_id == 3 ? $codigoCobroCOD : $codigoCobro }} Fecha: {{ date_format($venta->created_at, 'd/m/Y') }}</div>
            </div>

            <div style="background-color: black; color: white; padding: 2px 5px; font-size: 8px; font-weight: bold; margin-top: 10px; display: inline-block;">
                Tracking | Condiciones Generales de Servicio
            </div>
            <div class="table-container" style="margin-top: 5px;">
                <div class="table-row">
                    <div class="table-cell" style="text-align: left;">
                        <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(90)->generate('https://servicios.guatex.gt/Guatex/rastreoTracking?tipo=G&dato=' . $guia->tracking)) }}"
                            alt="Código QR" style="display: block; margin: 0 auto;">
                    </div>
                    <div class="table-cell" style="text-align: left;">
                        <div style="font-weight: bold; font-size: 75px;">{{ $key + 2 }}/{{ $piezas }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    @endforeach
</body>

</html>
