<?php

namespace App\Filament\Ventas\Widgets;

use App\Http\Controllers\Utils\Functions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AsesoresAsignados extends BaseWidget
{
    protected static ?int $sort = 1;

    /* protected int|string|array $columnSpan = 'full'; */

    protected static ?string $heading = 'Asesores Asignados';

    public static function canView(): bool
    {
        return auth()->user()->can('widget_AsesoresAsignados');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                auth()->user()->asesoresSupervisados()->getQuery()
            )
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Asesor')
                    ->sortable(),
                TextColumn::make('meta_actual')
                    ->label('Meta Actual')
                    ->getStateUsing(function ($record) {
                        $meta = $record->metas()->latest()->first();

                        return $meta ? Functions::money($meta->meta) : 'No asignada';
                    })

                    ->sortable(),
            ])
            ->paginated(false);
    }
}
