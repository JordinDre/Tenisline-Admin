<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms\Form;
use App\Filament\Widgets\MetasBodega;
use Filament\Forms\Components\Select;
use App\Filament\Widgets\VentasBodega;
use Filament\Forms\Components\Section;
use App\Filament\Widgets\VentasPorMarca;
use App\Filament\Widgets\VentasPorGenero;
use App\Http\Controllers\Utils\Functions;
use App\Filament\Widgets\VentasPorTallaCompleto;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;
    use InteractsWithPageFilters;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtros de Tiempo')
                    ->schema([
                        Select::make('year')
                            ->label('Año')
                            ->options(array_combine(Functions::obtenerAnios(), Functions::obtenerAnios()))
                            ->default(now()->year)
                            ->reactive(),
                        Select::make('mes')
                            ->label('Mes')
                            ->options(Functions::obtenerMeses())
                            ->default(now()->month)
                            ->reactive(),
                        Select::make('dia')
                            ->label('Día')
                            ->options(function ($get) {
                                $year = $get('year');
                                $month = $get('mes');
                                $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

                                return array_combine(range(1, $daysInMonth), range(1, $daysInMonth));
                            })
                            ->default(now()->day),
                    ])
                    ->columns(3),
                Section::make('Filtros de Datos')
                    ->schema([
                        Select::make('bodega')
                            ->label('Bodega')
                            ->options([
                                '' => 'Todas las Bodegas',
                                'Zacapa' => 'Zacapa',
                                'Chiquimula' => 'Chiquimula',
                                'Esquipulas' => 'Esquipulas',
                            ])
                            ->default('')
                            ->reactive(),
                        Select::make('genero')
                            ->label('Género')
                            ->options([
                                '' => 'Todos los Géneros',
                                'CABALLERO' => 'Caballero',
                                'DAMA' => 'Dama',
                                'INFANTE' => 'Infante',
                                'NIÑO' => 'Niño',
                            ])
                            ->default('')
                            ->reactive(),
                    ])
                    ->columns(2),
            ]);
    }

    protected static ?string $title = 'Admin';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getColumns(): int|string|array
    {
        return [
            'sm' => 1,
            'md' => 1,
            'lg' => 2,
            'xl' => 2,
        ];
    }

    public function getWidgets(): array
    {
        return [
            VentasBodega::class,
            MetasBodega::class,
            VentasPorTallaCompleto::class,
            VentasPorMarca::class,
            VentasPorGenero::class,
            \App\Filament\Widgets\MarchamosChart::class
        ];
    }
}
