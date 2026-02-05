@php
    use Illuminate\Support\Str;
    use SimpleSoftwareIO\QrCode\Facades\QrCode;
    /* dd($venta); */
@endphp
<!DOCTYPE html>
<html>

<head>
    <title>Guía Venta #{{ $venta->id }}</title>
    <style>
        @page {
            margin: 0.2cm;
            margin-top: 0.4cm;
            font-family: Arial, sans-serif;
        }

        section {
            font-size: 11px;
            line-height: 1.2;
            /* Cambia el tamaño de fuente para el contenido dentro de la sección */
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

        /* Estilo para letras verticales */
        .vertical-letter {
            display: block;
            transform: rotate(-90deg);
            /* Rotar cada letra 90 grados */
            line-height: 1.2;
            font-weight: bold
        }
    </style>
</head>

<body>
    @foreach ($venta->guias as $guia)
        @if ($guia->cantidad > 0)
            <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($guia->tracking, 'C128', 1.92, 90) }}"
                alt="Código de Barras">
            <div style="font-size: 13px; letter-spacing: 1.5px;">
                CAP - {{ $guia->tracking }}
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
                <div>Remitente: HARMISH</div>
                <div>Dirección: {{ $venta->bodega->bodega }}</div>
                <div>Asesor: {{ $venta->asesor['name'] }}</div>
                <div>Teléfono: {{ $venta->asesor['telefono'] }}</div>
            </section>

            <div style="font-size: 26px; margin-top: 8px;">{{ $guia->tracking }}</div>

            <section style="margin-top: 7px;">
                <div>
                    {{ Str::substr('Destinatario: ' . $venta->cliente->id . ' - ' . $venta->cliente->name . ' - ' . $venta->cliente->razon_social, 0, 110) }}
                </div>
                <div>Dirección:
                    {{ Str::substr($venta->cliente->direcciones[0]->direccion . ', ' . ($venta->cliente->direcciones[0]->zona ? 'ZONA ' . $venta->cliente->direcciones[0]->zona . ', ' : '') . $venta->cliente->direcciones[0]->referencia . ', ' . $venta->cliente->direcciones[0]->municipio->municipio . ', ' . $venta->cliente->direcciones[0]->municipio->departamento->departamento, 0, 110) }}
                </div>
                <div>
                    Teléfono: {{ $venta->cliente->telefono }}
                </div>
                <div>
                    @isset($venta->direccion->encargado)
                        Contacto: {{ $venta->direccion->encargado }} - {{ $venta->direccion->encargado_contacto }}
                    @endisset
                </div>
            </section>
            <div style="font-weight: bold; font-size: 100px; ">{{ $venta->punto_destino_guatex }}
            </div>
            <div style="font-weight: bold; font-size: 10px;">
                Desc. Envío: venta NO.{{ $venta->id }} - {{ $guia->tipo }}
            </div>
            <div style="margin-top: 10px;"></div>
            <div style="font-weight: bold; font-size: 10px;">No. Piezas: {{ $guia->cantidad }} </div>
            <div style="font-weight: bold; font-size: 10px;">Peso: 10</div>
            <div style="font-weight: bold; font-size: 10px;">Forma de Pago: {{ $venta->tipo_pago }} </div>
            <div style="font-weight: bold; font-size: 10px;">Fecha: {{ date_format($venta->created_at, 'd/m/Y') }}
            </div>
            <div class="table-container" style="margin-top: 0.5cm;">
                <div class="table-row">
                    <div class="table-cell" style="text-align: left;">
                        <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(90)->generate($guia->tracking . '|' . $venta->punto_destino_guatex)) }}"
                            alt="Código QR" style="display: block; margin: 0 auto;">
                    </div>
                    <div class="table-cell" style="text-align: left;">
                        <div style="font-weight: bold; font-size: 75px;">1/{{ $guia->cantidad }}
                        </div>
                    </div>
                </div>
            </div>
            @foreach (json_decode($guia->hijas, true) ?? [] as $key => $hija)
                <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($hija, 'C128', 1.92, 90) }}"
                    alt="Código de Barras">
                <div style="font-size: 13px; letter-spacing: 1.5px;">
                    CAP - {{ $hija }} - {{ $guia->tracking }}
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
                    <div>Remitente: HARMISH</div>
                    <div>Dirección: {{ $venta->bodega->bodega }}</div>
                    <div>Asesor: {{ $venta->asesor['name'] }}</div>
                    <div>Teléfono: {{ $venta->asesor['telefono'] }}</div>
                </section>

                <div style="font-size: 26px; margin-top: 8px;">{{ $hija }}</div>

                <section style="margin-top: 7px;">
                    <div>
                        {{ Str::substr('Destinatario: ' . $venta->cliente->id . ' - ' . $venta->cliente->name . ' - ' . $venta->cliente->razon_social, 0, 110) }}
                    </div>
                    <div>Dirección:
                        {{ Str::substr($venta->direccion->direccion . ', ' . ($venta->direccion->zona ? 'ZONA ' . $venta->direccion->zona . ', ' : '') . $venta->direccion->referencia . ', ' . $venta->direccion->municipio->municipio . ', ' . $venta->direccion->municipio->departamento->departamento, 0, 110) }}
                    </div>
                    <div>
                        Teléfono: {{ $venta->cliente->telefono }}
                    </div>
                    <div>
                        @isset($venta->direccion->encargado)
                            Contacto: {{ $venta->direccion->encargado }} - {{ $venta->direccion->encargado_contacto }}
                        @endisset
                    </div>
                </section>
                <div style="font-weight: bold; font-size: 100px; ">{{ $venta->punto_destino_guatex }}
                </div>
                <div style="font-weight: bold; font-size: 10px;">
                    Desc. Envío: venta NO.{{ $venta->id }} - {{ $guia->tipo }}
                </div>
                <div style="margin-top: 10px;"></div>
                <div style="font-weight: bold; font-size: 10px;">No. Piezas: {{ $guia->cantidad }} </div>
                <div style="font-weight: bold; font-size: 10px;">Peso: 10</div>
                <div style="font-weight: bold; font-size: 10px;">Forma de Pago: {{ $venta->tipo_pago }}
                </div>
                <div style="font-weight: bold; font-size: 10px;">Fecha: {{ date_format($venta->created_at, 'd/m/Y') }}
                </div>
                <div class="table-container" style="margin-top: 0.5cm;">
                    <div class="table-row">
                        <div class="table-cell" style="text-align: left;">
                            <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(90)->generate($hija . '|' . $venta->punto_destino_guatex)) }}"
                                alt="Código QR" style="display: block; margin: 0 auto;">
                        </div>
                        <div class="table-cell" style="text-align: left;">
                            <div style="font-weight: bold; font-size: 75px;">{{ $key + 2 }}/{{ $guia->cantidad }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    @endforeach
</body>

</html>
