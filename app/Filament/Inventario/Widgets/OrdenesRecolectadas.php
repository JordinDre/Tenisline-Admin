<?php

namespace App\Filament\Inventario\Widgets;

use App\Models\Orden;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class OrdenesRecolectadas extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?string $heading = 'Ordenes Recolectadas';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->user()->can('widget_OrdenesRecolectadas');
    }

    public function table(Table $table): Table
    {
        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;

        return $table
            ->query(
                Orden::query()
                    ->selectRaw('recolector_id as id, users.name, COUNT(*) as cantidad, SUM(total) as total')
                    ->join('users', 'ordens.recolector_id', '=', 'users.id')
                    ->whereNotNull('recolector_id')
                    ->whereYear('fecha_fin_recolectada', $year)
                    ->whereMonth('fecha_fin_recolectada', $month)
                    ->when($day, fn ($query) => $query->whereDay('fecha_fin_recolectada', $day))
                    ->groupBy('recolector_id', 'users.name')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Recolector')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'Q'.number_format($state, 2)),
            ])->paginated(false);
    }
}
