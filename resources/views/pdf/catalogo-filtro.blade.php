<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Productos con Filtros</title>
    <style>
        @page {
            size: letter portrait; /* 👈 puedes cambiar a A4 o lo que uses */
            margin: 15px;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            font-size: 11.5px;
            padding: 12px;
        }

        header {
            text-align: center;
            margin-bottom: 10px;
        }

        header h2 {
            margin: 5px 0;
        }

        header img {
            max-width: 120px;
            margin-bottom: 5px;
            filter: grayscale(100%) brightness(0);
        }

        .info-section {
            margin-bottom: 10px;
            border: 1px solid black;
            padding: 5px;
            font-size: 11px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border: 1px solid black;
            padding: 4px;
            font-size: 11px;
        }

        .table th {
            background: #f4f4f4;
            font-weight: bold;
            text-align: center;
        }

        .descripcion {
            width: 35%;
        }

        .marca { width: 20%; text-align: center; }
        .talla { width: 10%; text-align: center; }
        .genero { width: 15%; text-align: center; }
        .precio { width: 20%; text-align: right; }
    </style>
</head>
<body>
    <header>
        <h2>Catálogo de Productos con Filtros</h2>
        <img src="{{ public_path('/images/logo.png') }}" alt="Logo">
        <div style="font-size: 12px;">Fecha: {{ now()->format('d-m-Y') }}</div>
    </header>

    <section class="info-section">
        <strong>Filtros aplicados:</strong><br>
        @if(request('search')) Búsqueda: {{ request('search') }} <br>@endif
        @if(request('marca')) Marca: {{ request('marca') }} <br>@endif
        @if(request('genero')) Género: {{ request('genero') }} <br>@endif
        @if(request('tallas')) Tallas: {{ implode(', ', (array) request('tallas')) }} <br>@endif
        @if(request('bodega')) Bodega ID: {{ request('bodega') }} <br>@endif
        @if(request('marchamo')) Marchamo: {{ ucfirst(request('marchamo')) }} <br>@endif
    </section>

    <table class="table">
        <thead>
            <tr>
                <th class="descripcion">Descripción</th>
                <th class="marca">Marca</th>
                <th class="talla">Talla</th>
                <th class="genero">Género</th>
                <th class="precio">Precio</th>
            </tr>
        </thead>
        <tbody>
        @forelse($productos as $producto)
            <tr>
                <td class="descripcion">{{ $producto->descripcion }}</td>
                <td class="marca">{{ $producto->marca->marca ?? '---' }}</td>
                <td class="talla">{{ $producto->talla }}</td>
                <td class="genero">{{ $producto->genero }}</td>
                <td class="precio">Q {{ number_format($producto->precio_venta, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align: center; font-weight: bold;">No se encontraron productos</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
