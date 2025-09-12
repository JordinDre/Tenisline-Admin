<?php

namespace App\Filament\Widgets;

use App\Http\Controllers\Utils\Functions;
use App\Models\Labor;
use App\Models\VentaDetalle;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;

class VentasBodega extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?string $heading = 'Ventas por Empleado';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = [
        'sm' => 'full',
        'md' => 'full',
        'lg' => 1,
        'xl' => 1,
    ];

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->can('widget_VentasGeneral');
    }

    protected function getData(): array
    {
        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;
        $bodegaFilter = $this->filters['bodega'] ?? '';
        $generoFilter = $this->filters['genero'] ?? '';

        // $diasLaborados = Labor::whereYear('date', $year)
        //     ->whereMonth('date', $month)
        //     ->whereDate('date', '<=', now())
        //     ->count();

        // $totalDias = Labor::whereYear('date', $year)
        //     ->whereMonth('date', $month)
        //     ->count();

        $data = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->join('bodegas', 'ventas.bodega_id', '=', 'bodegas.id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day, fn ($query, $day) => $query->whereDay('ventas.created_at', $day))
            ->when($bodegaFilter, fn ($query, $bodega) => $query->where('bodegas.bodega', $bodega))
            ->when($generoFilter, fn ($query, $genero) => $query->where('productos.genero', $genero))
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->get()
            ->groupBy('asesor_id')
            ->map(function ($ordenes) {
                $total = $ordenes->sum('precio');
                // $meta = $ordenes->first()->asesor->metas->last()?->meta ?? 0;
                $costo = $ordenes->sum(fn ($d) => $d->cantidad * ($d->producto->precio_costo ?? 0));
                $clientes = $ordenes->pluck('venta.cliente_id')->unique()->count();

                $rentabilidad = $costo > 0 ? round(($total - $costo) / $total, 4) : 0;

                return [
                    'asesor' => $ordenes->first()->venta->asesor->name ?? 'Sin Asesor',
                    'total' => $total,
                    'cantidad' => $ordenes->count(),
                    'costo' => $costo,
                    'rentabilidad' => $rentabilidad,
                    // 'meta' => $meta,
                    // 'alcance' => $meta > 0 ? round(($total * 100) / $meta, 2) : 0,
                    'clientes' => $clientes,
                    'ticket_promedio' => $clientes > 0 ? round($total / $clientes, 2) : 0,
                ];
            });

        return [
            'labels' => $data->pluck('asesor')->toArray(),
            'datasets' => [
                // [
                //     'label' => 'Meta '.Functions::money($data->sum('meta')),
                //     'data' => $data->pluck('meta')->toArray(),
                //     'backgroundColor' => '#10B981', // esmerald-500
                //     'borderColor' => '#34D399', // esmerald-400
                // ],
                [
                    'label' => 'Total '.Functions::money($data->sum('total')),
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#38BDF8', // zinc-400
                    'borderColor' => '#0EA5E9', // zinc-500
                ],
                [
                    'label' => 'Cantidad '.number_format($data->sum('cantidad')),
                    'data' => $data->pluck('cantidad')->toArray(),
                    'backgroundColor' => '#F97316', // orange-500
                    'borderColor' => '#EA580C', // orange-600
                ],
                [
                    'label' => 'Clientes '.number_format($data->sum('clientes')),
                    'data' => $data->pluck('clientes')->toArray(),
                    'backgroundColor' => '#EF4444', // red-500
                    'borderColor' => '#DC2626', // red-600
                ],
                [
                    'label' => 'Rendimiento (%) '.number_format($data->avg('rentabilidad') * 100, 2),
                    'data' => $data->pluck('rentabilidad')->map(fn ($r) => $r * 100)->toArray(),
                    'backgroundColor' => '#10B981', // emerald-500
                    'borderColor' => '#059669', // emerald-600
                ],
                // [
                //     'label' => 'Rentabilidad (%) '.number_format($data->sum('rentabilidad'), 2),
                //     'data' => $data->pluck('rentabilidad')->toArray(),
                //     'backgroundColor' => '#6366F1', // indigo-500
                //     'borderColor' => '#4F46E5', // indigo-600
                // ],
                // [
                //     'label' => 'Alcance (%) '.number_format($data->sum('alcance'), 2),
                //     'data' => $data->pluck('alcance')->toArray(),
                //     'backgroundColor' => '#EC4899', // pink-500
                //     'borderColor' => '#DB2777', // pink-600
                // ],
                // [
                //     'label' => 'Proyección '.number_format($data->sum('proyeccion'), 2),
                //     'data' => $data->pluck('proyeccion')->toArray(),
                //     'backgroundColor' => '#FACC15', // yellow-400
                //     'borderColor' => '#EAB308', // yellow-500
                // ],
                // [
                //     'label' => 'Ticket Promedio '.Functions::money($data->sum('ticket_promedio')),
                //     'data' => $data->pluck('ticket_promedio')->toArray(),
                //     'backgroundColor' => '#78716C', // zinc-500 (marrón)
                //     'borderColor' => '#57534E', // zinc-600 (marrón oscuro)
                // ],
                // [
                //     'label' => 'Cuota Diaria '.Functions::money($data->sum('cuota_diaria')),
                //     'data' => $data->pluck('cuota_diaria')->toArray(),
                //     'backgroundColor' => '#6B7280', // zinc-500
                //     'borderColor' => '#4B5563', // zinc-600
                // ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'x',
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                ],
            ],
            'animation' => [
                'duration' => 1000,
                'easing' => 'easeOutQuart',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getExtraAttributes(): array
    {
        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;
        $bodegaFilter = $this->filters['bodega'] ?? '';
        $generoFilter = $this->filters['genero'] ?? '';

        $data = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->join('bodegas', 'ventas.bodega_id', '=', 'bodegas.id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day, fn ($query, $day) => $query->whereDay('ventas.created_at', $day))
            ->when($bodegaFilter, fn ($query, $bodega) => $query->where('bodegas.bodega', $bodega))
            ->when($generoFilter, fn ($query, $genero) => $query->where('productos.genero', $genero))
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->get()
            ->groupBy('asesor_id')
            ->map(function ($ordenes) {
                $total = $ordenes->sum('precio');
                $costo = $ordenes->sum(fn ($d) => $d->cantidad * ($d->producto->precio_costo ?? 0));
                $clientes = $ordenes->pluck('venta.cliente_id')->unique()->count();

                return [
                    'asesor' => $ordenes->first()->venta->asesor->name ?? 'Sin Asesor',
                    'total' => $total,
                    'cantidad' => $ordenes->count(),
                    'costo' => $costo,
                    'rentabilidad' => $costo > 0 ? round(($total - $costo) / $total, 2) : 0,
                    'clientes' => $clientes,
                    'ticket_promedio' => $clientes > 0 ? round($total / $clientes, 2) : 0,
                ];
            });

        return [
            'x-data' => '{}',
            'x-init' => 'window.asesorDataBodega = '.json_encode($data->values()).';',
        ];
    }
}
