<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: #2c3e50;
            margin: 0;
            padding: 15px;
            background-color: #f0f8ff;
        }

        /* PORTADA */
        .cover-page {
            background-color: #ffffff;
            text-align: center;
            color: #333333;
            padding: 50px 20px;
            position: relative;
        }

        .cover-page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 80%, #ffebee 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, #e8f5e8 0%, transparent 50%),
                        radial-gradient(circle at 40% 40%, #fff3e0 0%, transparent 50%);
        }

        .cover-content {
            color: #333333;
            position: relative;
            z-index: 2;
        }

        .cover-logo {
            margin-bottom: 30px;
        }

        .logo-img {
            max-width: 200px;
            height: auto;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .cover-title {
            font-size: 48pt;
            font-weight: bold;
            margin: 0;
            letter-spacing: 4px;
            color: #e91e63;
        }

        .cover-subtitle {
            font-size: 24pt;
            font-weight: normal;
            margin: 10px 0;
            letter-spacing: 2px;
            color: #4caf50;
        }

        .cover-line {
            width: 200px;
            height: 6px;
            background-color: #ff9800;
            margin: 30px auto;
            border-radius: 3px;
        }

        .cover-date {
            font-size: 18pt;
            font-weight: normal;
            margin: 20px 0;
            color: #2196f3;
        }

        .cover-footer {
            margin-top: 40px;
        }

        .cover-footer p {
            font-size: 12pt;
            margin: 5px 0;
            color: #9c27b0;
        }

        .page {
            width: 100%;
            height: 100vh;
            page-break-after: always;
            background-color: #ffffff;
            position: relative;
            border-radius: 20px;
            overflow: hidden;
        }

        .page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 10% 10%, #ffebee 0%, transparent 30%),
                        radial-gradient(circle at 90% 90%, #e8f5e8 0%, transparent 30%),
                        radial-gradient(circle at 50% 50%, #fff3e0 0%, transparent 20%);
        }

        .page:last-child {
            page-break-after: avoid;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            background-color: #e91e63;
            color: white;
            padding: 25px;
            position: relative;
            z-index: 2;
            border-radius: 0 0 25px 25px;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 0%, rgba(255,255,255,0.2) 0%, transparent 70%);
            border-radius: 0 0 25px 25px;
        }

        .header h1 {
            font-size: 28pt;
            font-weight: bold;
            margin: 0;
            letter-spacing: 3px;
            color: #ffffff;
            position: relative;
            z-index: 2;
        }

        .header p {
            font-size: 14pt;
            margin: 10px 0 0 0;
            color: #ffffff;
            position: relative;
            z-index: 2;
        }

        .products-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .product-card {
            border: 2px solid #ff9800;
            padding: 15px;
            background-color: #ffffff;
            width: 100%;
            height: 140px;
            margin-bottom: 10px;
            border-radius: 20px;
            position: relative;
            z-index: 2;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background-color: #4caf50;
            border-radius: 25px 25px 0 0;
        }

        .product-card::after {
            content: '';
            position: absolute;
            top: 15px;
            right: 15px;
            width: 25px;
            height: 25px;
            background-color: #e91e63;
            border-radius: 50%;
        }

        .product-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }

        .product-image-cell {
            width: 120px;
            height: 100px;
            vertical-align: top;
            padding-right: 10px;
        }

        .product-image {
            width: 100px;
            height: 80px;
            border: 3px solid #4caf50;
            border-radius: 15px;
            box-shadow: 0 3px 10px rgba(76, 175, 80, 0.3);
        }

        .product-info-cell {
            vertical-align: top;
            width: 50%;
            padding-right: 15px;
        }

        .product-info {
            width: 100%;
            height: 130px;
        }

        .product-price-cell {
            vertical-align: top;
            width: 200px;
            text-align: right;
        }

        .product-price-info {
            width: 100%;
            height: 130px;
        }

        .product-title {
            font-size: 11pt;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
            line-height: 1.3;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .product-details {
            font-size: 8pt;
            line-height: 1.4;
            margin-bottom: 10px;
            color: #5a6c7d;
        }

        .product-details div {
            margin-bottom: 3px;
            padding: 2px 0;
        }

        .product-details strong {
            color: #34495e;
            font-weight: 600;
        }

        .product-price {
            font-size: 14pt;
            font-weight: bold;
            color: white;
            margin: 10px 0;
            padding: 8px 12px;
            background-color: #ff9800;
            display: block;
            text-align: center;
            border-radius: 20px;
            border: 2px solid #ff5722;
            box-shadow: 0 3px 10px rgba(255, 152, 0, 0.4);
        }

        .product-warehouses {
            font-size: 7pt;
            margin-top: 12px;
        }

        .warehouse-item {
            margin-bottom: 4px;
            padding: 6px 10px;
            background-color: #e8f5e8;
            text-align: left;
            border: 2px solid #4caf50;
            border-radius: 15px;
            position: relative;
            box-shadow: 0 2px 6px rgba(76, 175, 80, 0.2);
        }

        .warehouse-item::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            background-color: #e91e63;
            border-radius: 50%;
        }

        .warehouse-name {
            font-weight: bold;
            display: block;
            color: #2e7d32;
            font-size: 7pt;
            margin-left: 15px;
        }

        .warehouse-stock {
            color: #1b5e20;
            font-size: 6pt;
            font-weight: bold;
            margin-left: 15px;
        }

        .footer {
            text-align: center;
            font-size: 9pt;
            color: white;
            margin-top: 25px;
            padding: 20px;
            background-color: #9c27b0;
            font-weight: bold;
            border-radius: 25px;
            position: relative;
            z-index: 2;
            box-shadow: 0 4px 15px rgba(156, 39, 176, 0.3);
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 6px;
            background-color: #ff9800;
            border-radius: 3px;
        }

        @media print {
            .page {
                height: 100vh;
            }
        }
    </style>
</head>
<body>
    <!-- PORTADA -->
    <div class="page cover-page">
        <div class="cover-content">
            <div class="cover-logo">
                <img src="{{ public_path('/images/logo.png') }}" alt="Logo Tenisline" class="logo-img">
            </div>
            <h1 class="cover-title">CATÁLOGO</h1>
            <h2 class="cover-subtitle">DE PRODUCTOS</h2>
            <div class="cover-line"></div>
            <p class="cover-date">{{ date('d/m/Y') }}</p>
            <div class="cover-footer">
                <p>Colección {{ date('Y') }}</p>
                <p>Calzado Deportivo Premium</p>
            </div>
        </div>
    </div>

    @foreach($productos->chunk(4) as $pageProducts)
        <div class="page">
            <div class="header">
                <h1>Catálogo de Productos</h1>
                <p>Tenisline - {{ date('d/m/Y') }}</p>
            </div>

            <table class="products-grid">
                @foreach($pageProducts as $producto)
                    <tr>
                        <td style="width: 100%; vertical-align: top; padding: 5px;">
                            <div class="product-card">
                                <table class="product-table">
                                    <tr>
                        <td class="product-image-cell">
                            @if($producto['imagen'])
                                <img src="{{ $producto['imagen'] }}" alt="{{ $producto['descripcion'] }}" class="product-image">
                            @else
                                <img src="{{ public_path('/images/logo.png') }}" alt="Logo Tenisline" class="product-image">
                            @endif
                        </td>
                                        <td class="product-info-cell">
                                            <div class="product-info">
                                                <div class="product-title">{{ $producto['descripcion'] }}</div>
                                                
                                                <div class="product-details">
                                                    <div><strong>Código:</strong> {{ $producto['codigo'] }}</div>
                                                    <div><strong>Marca:</strong> {{ $producto['marca'] }}</div>
                                                    <div><strong>Talla:</strong> US {{ $producto['talla'] }} ({{ $producto['genero'] }})</div>
                                                    <div><strong>Color:</strong> {{ $producto['color'] }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="product-price-cell">
                                            <div class="product-price-info">
                                                @if($producto['precio'])
                                                    <div class="product-price">Q{{ number_format($producto['precio'], 2) }}</div>
                                                @endif

                                                @if($producto['bodegas'] && count($producto['bodegas']) > 0)
                                                    @php
                                                        $bodegasConExistencia = array_filter($producto['bodegas'], function($bodega) {
                                                            return $bodega['existencia'] > 0;
                                                        });
                                                    @endphp
                                                    @if(count($bodegasConExistencia) > 0)
                                                        <div class="product-warehouses">
                                                            <div style="font-weight: bold; margin-bottom: 5px; font-size: 7pt;">Disponible en:</div>
                                                            @foreach($bodegasConExistencia as $bodega)
                                                                <div class="warehouse-item">
                                                                    <span class="warehouse-name">{{ $bodega['bodega'] }}</span>
                                                                    <span class="warehouse-stock">{{ $bodega['existencia'] }} u</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>

        </div>
    @endforeach
</body>
</html>
