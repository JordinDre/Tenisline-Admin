<?php

namespace App\Filament\Widgets;

use App\Http\Controllers\Utils\Functions;
use App\Models\Labor;
use App\Models\VentaDetalle;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class VentasBodega extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?string $heading = 'Ventas Zacapa';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 2;

    public static function canView(): bool
    {

        return auth()->user()->can('widget_VentasGeneral');
    }

    protected function getData(): array
    {
        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;

        // $diasLaborados = Labor::whereYear('date', $year)
        //     ->whereMonth('date', $month)
        //     ->whereDate('date', '<=', now())
        //     ->count();

        // $totalDias = Labor::whereYear('date', $year)
        //     ->whereMonth('date', $month)
        //     ->count();

        $data = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day, fn ($query, $day) => $query->whereDay('ventas.created_at', $day))
            ->where('ventas.bodega_id', 1)
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->get()
            ->groupBy('asesor_id')
            ->map(function ($ordenes) {
                $total = $ordenes->sum('precio');
                // $meta = $ordenes->first()->asesor->metas->last()?->meta ?? 0;
                $costo = $ordenes->sum(fn ($d) => $d->cantidad * (($d->producto->precio_compra ?? 0) + ($d->producto->envase ?? 0))
                );
                $clientes = $ordenes->pluck('venta.cliente_id')->unique()->count();

                return [
                    'asesor' => $ordenes->first()->venta->asesor->name ?? 'Sin Asesor',
                    'total' => $total,
                    'cantidad' => $ordenes->count(),
                    'costo' => $costo,
                    'rentabilidad' => $costo > 0 ? round(($total - $costo) / $costo, 2) : 0,
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
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                    'backgroundColor' => 'rgba(0, 0, 0, 0.9)',
                    'titleColor' => '#ffffff',
                    'bodyColor' => '#ffffff',
                    'borderColor' => '#ffffff',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                    'padding' => 12,
                    'callbacks' => [
                        'title' => 'function(context) {
                            return "👤 " + context[0].label;
                        }',
                        'label' => 'function(context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.parsed.y !== null) {
                                if (context.datasetIndex === 0) {
                                    label += new Intl.NumberFormat("es-GT", {
                                        style: "currency",
                                        currency: "GTQ"
                                    }).format(context.parsed.y);
                                } else if (context.datasetIndex === 1) {
                                    label += new Intl.NumberFormat("es-GT").format(context.parsed.y);
                                } else if (context.datasetIndex === 2) {
                                    label += new Intl.NumberFormat("es-GT").format(context.parsed.y);
                                }
                            }
                            return label;
                        }',
                        'afterBody' => 'function(context) {
                            let asesorIndex = context[0].dataIndex;
                            let asesorData = window.asesorDataBodega ? window.asesorDataBodega[asesorIndex] : null;
                            if (asesorData) {
                                return [
                                    "",
                                    "📊 DETALLES DEL ASESOR:",
                                    "💰 Total: " + new Intl.NumberFormat("es-GT", {
                                        style: "currency",
                                        currency: "GTQ"
                                    }).format(asesorData.total),
                                    "📦 Cantidad: " + new Intl.NumberFormat("es-GT").format(asesorData.cantidad),
                                    "👥 Clientes: " + new Intl.NumberFormat("es-GT").format(asesorData.clientes),
                                    "📈 Rentabilidad: " + asesorData.rentabilidad + "%",
                                    "🎫 Ticket Promedio: " + new Intl.NumberFormat("es-GT", {
                                        style: "currency",
                                        currency: "GTQ"
                                    }).format(asesorData.ticket_promedio)
                                ];
                            }
                            return [];
                        }',
                    ],
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

        $data = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day, fn ($query, $day) => $query->whereDay('ventas.created_at', $day))
            ->where('ventas.bodega_id', 1)
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->get()
            ->groupBy('asesor_id')
            ->map(function ($ordenes) {
                $total = $ordenes->sum('precio');
                $costo = $ordenes->sum(fn ($d) => $d->cantidad * (($d->producto->precio_compra ?? 0) + ($d->producto->envase ?? 0)));
                $clientes = $ordenes->pluck('venta.cliente_id')->unique()->count();

                return [
                    'asesor' => $ordenes->first()->venta->asesor->name ?? 'Sin Asesor',
                    'total' => $total,
                    'cantidad' => $ordenes->count(),
                    'costo' => $costo,
                    'rentabilidad' => $costo > 0 ? round(($total - $costo) / $costo, 2) : 0,
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
