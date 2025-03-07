<?php

namespace App\Filament\Inventario\Pages;

use App\Http\Controllers\Utils\Functions;
use Carbon\Carbon;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Inventario';

    protected static ?string $navigationLabel = 'Dashboard';

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
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
            ]);
    }
}
