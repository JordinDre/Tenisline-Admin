<?php

namespace App\Filament\Widgets;

use App\Models\Orden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OrdenEstados extends BaseWidget
{
    protected static ?string $heading = 'Conteo de Órdenes por Estado';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->user()->can('widget_OrdenEstados');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Orden::selectRaw('MIN(id) as id, estado, COUNT(*) as cantidad, SUM(total) as total')
                    ->groupBy('estado')
            )
            ->columns([
                TextColumn::make('estado')
                    ->label('Estado')
                    ->sortable(),
                TextColumn::make('cantidad')
                    ->label('Cantidad de Órdenes')
                    ->sortable()
                    ->numeric(),
                TextColumn::make('total')
                    ->label('Total de Órdenes')
                    ->sortable()
                    ->money('GTQ'),
            ])->paginated(false);
    }
}
