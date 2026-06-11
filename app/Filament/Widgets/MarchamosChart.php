<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class MarchamosChart extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.marchamos-chart-view';

    protected static ?string $heading = 'Costos por Marchamo';

    protected function getViewData(): array
    {
        $bodegaFilter = $this->filters['bodega'] ?? '';
        $genero = $this->filters['genero'] ?? '';

        // Costos de ofertados: productos con precio_oferta y al menos 1 en inventario
        $ofertadosQuery = \App\Models\Inventario::query()
            ->join('productos', 'productos.id', '=', 'inventarios.producto_id')
            ->join('bodegas', 'bodegas.id', '=', 'inventarios.bodega_id')
            ->whereNotNull('productos.precio_oferta')
            ->where('productos.precio_oferta', '>', 0)
            ->where('inventarios.existencia', '>', 0)
            ->whereNull('productos.deleted_at');

        if ($genero !== '') {
            $ofertadosQuery->where('productos.genero', $genero);
        }

        if ($bodegaFilter !== '') {
            if (is_numeric($bodegaFilter)) {
                $ofertadosQuery->where('inventarios.bodega_id', (int) $bodegaFilter);
            } else {
                $ofertadosQuery->where('bodegas.bodega', $bodegaFilter);
            }
        }

        $ofertadosRaw = $ofertadosQuery
            ->selectRaw('bodegas.bodega, SUM(COALESCE(productos.precio_costo, 0)) as total, COUNT(inventarios.id) as cantidad')
            ->groupBy('bodegas.bodega')
            ->get();

        $costoOfertados = 0;
        $cantidadOfertados = 0;
        $bodegasOfertados = [];

        foreach ($ofertadosRaw as $item) {
            $costoOfertados += (float) $item->total;
            $cantidadOfertados += (int) $item->cantidad;
            $bodegasOfertados[] = [
                'nombre' => $item->bodega,
                'costo' => (float) $item->total,
                'cantidad' => (int) $item->cantidad,
            ];
        }

        // Costos por marchamo: productos con marchamo y al menos 1 en inventario, agrupados por marchamo y bodega
        $marchamoQuery = \App\Models\Inventario::query()
            ->join('productos', 'productos.id', '=', 'inventarios.producto_id')
            ->join('bodegas', 'bodegas.id', '=', 'inventarios.bodega_id')
            ->whereNotNull('productos.marchamo')
            ->where('inventarios.existencia', '>', 0)
            ->whereNull('productos.deleted_at');

        if ($genero !== '') {
            $marchamoQuery->where('productos.genero', $genero);
        }

        if ($bodegaFilter !== '') {
            if (is_numeric($bodegaFilter)) {
                $marchamoQuery->where('inventarios.bodega_id', (int) $bodegaFilter);
            } else {
                $marchamoQuery->where('bodegas.bodega', $bodegaFilter);
            }
        }

        $costosPorMarchamoRaw = $marchamoQuery
            ->selectRaw('productos.marchamo, bodegas.bodega, SUM(COALESCE(productos.precio_costo, 0)) as total, COUNT(inventarios.id) as cantidad')
            ->groupBy('productos.marchamo', 'bodegas.bodega')
            ->get();

        $costosPorMarchamo = [];
        foreach ($costosPorMarchamoRaw as $item) {
            $marchamo = $item->marchamo;
            $bodega = $item->bodega;
            
            if (!isset($costosPorMarchamo[$marchamo])) {
                $costosPorMarchamo[$marchamo] = [
                    'costo' => 0,
                    'cantidad' => 0,
                    'bodegas' => []
                ];
            }
            
            $costosPorMarchamo[$marchamo]['costo'] += (float) $item->total;
            $costosPorMarchamo[$marchamo]['cantidad'] += (int) $item->cantidad;
            $costosPorMarchamo[$marchamo]['bodegas'][] = [
                'nombre' => $bodega,
                'costo' => (float) $item->total,
                'cantidad' => (int) $item->cantidad,
            ];
        }

        $marchamosDisponibles = collect($costosPorMarchamo)
            ->keys()
            ->sort()
            ->values()
            ->toArray();

        // Mapeo de colores para cada marchamo
        $coloresMarchamo = [
            'rojo' => [
                'bg' => 'bg-red-50 dark:bg-red-900/20',
                'text' => 'text-red-600 dark:text-red-400',
                'textBold' => 'text-red-900 dark:text-red-100',
            ],
            'naranja' => [
                'bg' => 'bg-orange-50 dark:bg-orange-900/20',
                'text' => 'text-orange-600 dark:text-orange-400',
                'textBold' => 'text-orange-900 dark:text-orange-100',
            ],
            'celeste' => [
                'bg' => 'bg-blue-50 dark:bg-blue-900/20',
                'text' => 'text-blue-600 dark:text-blue-400',
                'textBold' => 'text-blue-900 dark:text-blue-100',
            ],
            'amarillo' => [
                'bg' => 'bg-yellow-50 dark:bg-yellow-900/20',
                'text' => 'text-yellow-600 dark:text-yellow-400',
                'textBold' => 'text-yellow-900 dark:text-yellow-100',
            ],
            'verde' => [
                'bg' => 'bg-green-50 dark:bg-green-900/20',
                'text' => 'text-green-600 dark:text-green-400',
                'textBold' => 'text-green-900 dark:text-green-100',
            ],
        ];

        // Preparar datos de marchamos con sus costos, cantidades y colores
        $marchamosData = [];
        $totalCostosPorMarchamo = 0;
        foreach ($marchamosDisponibles as $marchamo) {
            $data = $costosPorMarchamo[$marchamo];
            $totalCostosPorMarchamo += $data['costo'];
            $marchamosData[] = [
                'nombre' => $marchamo,
                'costo' => $data['costo'],
                'cantidad' => $data['cantidad'],
                'bodegas' => $data['bodegas'],
                'colores' => $coloresMarchamo[$marchamo] ?? [
                    'bg' => 'bg-gray-50 dark:bg-gray-900/20',
                    'text' => 'text-gray-600 dark:text-gray-400',
                    'textBold' => 'text-gray-900 dark:text-gray-100',
                ],
            ];
        }

        return [
            'costoOfertados' => $costoOfertados,
            'cantidadOfertados' => $cantidadOfertados,
            'bodegasOfertados' => $bodegasOfertados,
            'marchamosData' => $marchamosData,
            'totalCostosPorMarchamo' => $totalCostosPorMarchamo,
        ];
    }
}
