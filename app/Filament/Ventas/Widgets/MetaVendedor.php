<?php

namespace App\Filament\Ventas\Widgets;

use App\Models\Meta;
use App\Models\Labor;
use App\Models\VentaDetalle;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Controllers\Utils\Functions;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class MetaVendedor extends ChartWidget
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
        $year = (int) ($this->filters['year'] ?? now()->year);
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;
        $bodegaFilter = $this->filters['bodega'] ?? '';
        $generoFilter = $this->filters['genero'] ?? '';

        // --- Normalizar el mes a entero 1..12 (acepta nombres en español y abreviaturas)
        // if (is_numeric($monthFilter)) {
        //     $month = (int) $monthFilter;
        // } else {
        //     $meses = [
        //         'enero' => 1, 'ene' => 1,
        //         'febrero' => 2, 'feb' => 2,
        //         'marzo' => 3, 'mar' => 3,
        //         'abril' => 4, 'abr' => 4,
        //         'mayo' => 5,
        //         'junio' => 6, 'jun' => 6,
        //         'julio' => 7, 'jul' => 7,
        //         'agosto' => 8, 'ago' => 8,
        //         'septiembre' => 9, 'sep' => 9, 'sept' => 9,
        //         'octubre' => 10, 'oct' => 10,
        //         'noviembre' => 11, 'nov' => 11,
        //         'diciembre' => 12, 'dic' => 12,
        //     ];
        //     $key = strtolower(trim((string) $monthFilter));
        //     $month = $meses[$key] ?? now()->month;
        // }
        // $month = max(1, min(12, (int) $month));

        // --- Días del mes y días transcurridos (más seguro con Carbon::create)
        // $carbonMonth = Carbon::create($year, $month, 1);
        // $totalDiasMes = $carbonMonth->daysInMonth;

        $diasTranscurridos = $day ? (int)$day : now()->day;
        $totalDiasMes = now()->setYear((int)$year)->setMonth((int)$month)->daysInMonth;

        // if ($dayFilter) {
        //     $diasTranscurridos = (int) $dayFilter;
        // } else {
        //     $hoy = Carbon::now();
        //     $diasTranscurridos = ($hoy->year === $year && $hoy->month === $month)
        //         ? $hoy->day
        //         : $totalDiasMes; // si el mes es pasado -> usar días completos del mes
        // }
        // // evitar división por cero
        // $diasTranscurridos = max(1, $diasTranscurridos);

        // --- Metas por bodega (clave: bodega_id)
        $metas = \App\Models\Meta::where('mes', $month)
            ->where('anio', $year)
            ->whereNotNull('bodega_id')
            ->pluck('meta', 'bodega_id')
            ->toArray();

        // --- Traer detalles (filtrando por año/mes/día/bodega/genero)
        $detalles = \App\Models\VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->join('bodegas', 'bodegas.id', '=', 'ventas.bodega_id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day, fn($q) => $q->whereDay('ventas.created_at', $day))
            ->when($bodegaFilter, fn($q,$b) => $q->where('bodegas.bodega', $b))
            ->when($generoFilter, fn($q,$g) => $q->where('productos.genero', $g))
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->select('venta_detalles.*') // traemos modelos completos (para usar relaciones en colección)
            ->get();

        // --- Agrupar por asesor (usamos venta->asesor_id en la colección)
        $data = $detalles
            ->groupBy(fn($d) => $d->venta->asesor_id ?? 'sin_asesor')
            ->map(function ($ordenes) use ($metas, $diasTranscurridos, $totalDiasMes) {
                $total = (float) $ordenes->sum('precio');
                $costo = (float) $ordenes->sum(fn($d) => $d->cantidad * ($d->producto->precio_costo ?? 0));
                $unidadesVendidas = (int) $ordenes->sum('cantidad');

                // si el asesor vendió en varias bodegas, tomamos la primera (como tu lógica original)
                $bodegaId = $ordenes->first()->venta->bodega_id ?? null;
                $meta = isset($metas[$bodegaId]) ? ((float)$metas[$bodegaId] / 2) : 0;

                $alcance = $meta > 0 ? round(($total * 100) / $meta, 2) : 0;
                $proyeccion = ($total / $diasTranscurridos) * $totalDiasMes;
                $unidadesProyectadas = ($unidadesVendidas / $diasTranscurridos) * $totalDiasMes;
                $rentabilidad = $total > 0 ? round((($total - $costo) / $total), 4) : 0;

                return [
                    'asesor' => $ordenes->first()->venta->asesor->name ?? 'Sin Asesor',
                    'total' => $total,
                    'meta' => $meta,
                    'alcance' => $alcance,
                    'proyeccion' => $proyeccion,
                    'unidades_vendidas' => $unidadesVendidas,
                    'unidades_proyectadas' => $unidadesProyectadas,
                    'rentabilidad' => $rentabilidad, // decimal (0.25 = 25%)
                ];
            });

        // --- Preparar datasets (igual a lo que usabas antes)
        return [
            'labels' => $data->pluck('asesor')->toArray(),
            'datasets' => [
                [
                    'label' => 'Meta (Q) ' . Functions::money($data->sum('meta')),
                    'data' => $data->pluck('meta')->toArray(),
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#34D399',
                ],
                [
                    'label' => 'Proyección (Q) ' . Functions::money($data->sum('proyeccion')),
                    'data' => $data->pluck('proyeccion')->toArray(),
                    'backgroundColor' => '#FACC15',
                    'borderColor' => '#EAB308',
                ],
                [
                    'label' => 'Venta (Q) ' . Functions::money($data->sum('total')),
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#38BDF8',
                    'borderColor' => '#0EA5E9',
                ],
                [
                    'label' => 'Unidades Proyectadas '. $data->sum('unidades_proyectadas'),
                    'data' => $data->pluck('unidades_proyectadas')->toArray(),
                    'backgroundColor' => '#87674a',
                    'borderColor' => '#87674a',
                ],
                [
                    'label' => 'Unidades Vendidas ' . $data->sum('unidades_vendidas'),
                    'data' => $data->pluck('unidades_vendidas')->toArray(),
                    'backgroundColor' => '#f2d8cd',
                    'borderColor' => '#f2d8cd',
                ],
                [
                    'label' => 'Rendimiento (%) ' . number_format($data->avg('rentabilidad') * 100, 2),
                    'data' => $data->pluck('rentabilidad')->map(fn($r) => $r * 100)->toArray(),
                    'backgroundColor' => '#653952',
                    'borderColor' => '#653952',
                ],
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
