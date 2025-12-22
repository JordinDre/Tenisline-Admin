<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
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
        $ofertadosQuery = Producto::query()
            ->whereNotNull('precio_oferta')
            ->where('precio_oferta', '>', 0);

        if ($genero !== '') {
            $ofertadosQuery->where('genero', $genero);
        }

        // Verificar que la suma de existencias sea >= 1
        if ($bodegaFilter !== '') {
            if (is_numeric($bodegaFilter)) {
                $ofertadosQuery->whereRaw('(SELECT SUM(existencia) FROM inventarios WHERE inventarios.producto_id = productos.id AND inventarios.bodega_id = ?) >= 1', [(int) $bodegaFilter]);
            } else {
                $ofertadosQuery->whereRaw('(SELECT SUM(inventarios.existencia) FROM inventarios INNER JOIN bodegas ON inventarios.bodega_id = bodegas.id WHERE inventarios.producto_id = productos.id AND bodegas.bodega = ?) >= 1', [$bodegaFilter]);
            }
        } else {
            $ofertadosQuery->whereRaw('(SELECT SUM(existencia) FROM inventarios WHERE inventarios.producto_id = productos.id) >= 1');
        }

        $costoOfertados = (float) $ofertadosQuery->sum('precio_costo');
        $cantidadOfertados = (int) $ofertadosQuery->count();

        // Costos por marchamo: productos con marchamo y al menos 1 en inventario, agrupados por marchamo
        $marchamoQuery = Producto::query()
            ->whereNotNull('marchamo');

        if ($genero !== '') {
            $marchamoQuery->where('genero', $genero);
        }

        // Verificar que la suma de existencias sea >= 1
        if ($bodegaFilter !== '') {
            if (is_numeric($bodegaFilter)) {
                $marchamoQuery->whereRaw('(SELECT SUM(existencia) FROM inventarios WHERE inventarios.producto_id = productos.id AND inventarios.bodega_id = ?) >= 1', [(int) $bodegaFilter]);
            } else {
                $marchamoQuery->whereRaw('(SELECT SUM(inventarios.existencia) FROM inventarios INNER JOIN bodegas ON inventarios.bodega_id = bodegas.id WHERE inventarios.producto_id = productos.id AND bodegas.bodega = ?) >= 1', [$bodegaFilter]);
            }
        } else {
            $marchamoQuery->whereRaw('(SELECT SUM(existencia) FROM inventarios WHERE inventarios.producto_id = productos.id) >= 1');
        }

        $costosPorMarchamo = $marchamoQuery
            ->selectRaw('marchamo, SUM(COALESCE(precio_costo, 0)) as total, COUNT(*) as cantidad')
            ->groupBy('marchamo')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->marchamo => [
                    'costo' => (float) $item->total,
                    'cantidad' => (int) $item->cantidad,
                ]];
            })
            ->toArray();

        // Obtener solo los marchamos que tienen productos con existencia disponible
        // Usamos las claves de $costosPorMarchamo ya que solo contiene marchamos con existencia >= 1
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
        ];

        // Preparar datos de marchamos con sus costos, cantidades y colores
        $marchamosData = [];
        $totalCostosPorMarchamo = 0;
        foreach ($marchamosDisponibles as $marchamo) {
            $data = $costosPorMarchamo[$marchamo] ?? ['costo' => 0, 'cantidad' => 0];
            $totalCostosPorMarchamo += $data['costo'];
            $marchamosData[] = [
                'nombre' => $marchamo,
                'costo' => $data['costo'],
                'cantidad' => $data['cantidad'],
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
            'marchamosData' => $marchamosData,
            'totalCostosPorMarchamo' => $totalCostosPorMarchamo,
        ];
    }
}
