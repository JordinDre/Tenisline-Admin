<?php

namespace App\Livewire\Users;

use App\Filament\Resources\UserResource\RelationManagers\OrdenesRelationManager;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Guava\FilamentModalRelationManagers\Actions\Table\RelationManagerAction;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ListClientes extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $user = auth()->user();

        // Obtener solo los clientes asignados al asesor autenticado
        $query = User::whereHas('asesores', function ($query) use ($user) {
            $query->where('asesor_id', $user->id);
        });

        return $table
            ->query($query)
            ->extremePaginationLinks()
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('nit')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('dpi')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('razon_social')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('telefono')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('whatsapp')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('email')->copyable(),
                /* Tables\Columns\TextColumn::make('saldo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('credito')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('credito_dias')
                    ->numeric()
                    ->sortable(), */
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                RelationManagerAction::make('historial')
                    ->label('Historial')
                    ->modalWidth(MaxWidth::FiveExtraLarge)
                    ->icon('tabler-history')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->relationManager(OrdenesRelationManager::make()),
            ]);
    }

    public function render(): View
    {
        return view('livewire.users.list-clientes');
    }
}
