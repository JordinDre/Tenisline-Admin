<?php

namespace App\Filament\Ventas\Widgets;

use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;
use Filament\Tables\Columns\TextColumn;
use App\Http\Controllers\Utils\Functions;
use Filament\Widgets\TableWidget as BaseWidget;

class AsesoresAsignados extends BaseWidget
{
    protected static ?int $sort = 1;

    /* protected int|string|array $columnSpan = 'full'; */

    protected static ?string $heading = 'Asesores Asignados';

    public static function canView(): bool
    {
        if (!Schema::hasTable('ordens')) { // Reemplaza 'ordens' con el nombre real de tu tabla
            return false; // Si la tabla 'ordens' NO existe, NO mostrar el widget
             }
             
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
