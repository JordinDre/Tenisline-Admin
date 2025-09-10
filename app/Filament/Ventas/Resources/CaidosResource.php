<?php

namespace App\Filament\Ventas\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Venta;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use App\Http\Controllers\VentaController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Ventas\Resources\CaidosResource\Pages;
use App\Filament\Ventas\Resources\CaidosResource\RelationManagers;

class CaidosResource extends Resource
{
    protected static ?string $model = Venta::class;

    protected static ?string $modelLabel = 'Usuario Caido';

    protected static ?string $pluralModelLabel = 'Usuarios Caidos';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationIcon = 'heroicon-o-user-minus';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return Venta::query()
            ->fromSub(function ($query) {
                $query->selectRaw('ventas.*, ROW_NUMBER() OVER (PARTITION BY cliente_id ORDER BY created_at DESC) as rn')
                    ->from('ventas');
            }, 'ventas')
            ->where('rn', 1)
            ->with(['cliente'])
            ->orderBy('created_at', 'asc');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente.id')
                    ->label('Cliente ID')
                    ->searchable()
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.telefono')
                    ->label('Telefono')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.name')
                    ->label('Nombre')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.razon_social')
                    ->label('Razón Social')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.nit')
                    ->label('NIT')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id')
                    ->label('Venta ID')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de la venta')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ultima_consulta')
                    ->label('Última consulta')
                    ->getStateUsing(fn ($record) => $record->cliente?->ultimaConsulta()?->seguimiento ?? '—')
                    ->description(fn ($record) => $record->cliente?->ultimaConsulta()?->created_at?->format('d/m/Y H:i') ?? ''),
                
                Tables\Columns\TextColumn::make('ultimo_seguimiento')
                    ->label('Último seguimiento')
                    ->getStateUsing(fn ($record) => $record->cliente?->ultimoSeguimiento()?->seguimiento ?? '—')
                    ->description(fn ($record) => $record->cliente?->ultimoSeguimiento()?->created_at?->format('d/m/Y H:i') ?? ''),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('consulta')
                    ->label('Consulta')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->form([
                        Forms\Components\Textarea::make('seguimiento')
                            ->label('Nota de consulta')
                            ->required(),
                    ])
                    ->action(function (Venta $record, array $data): void {
                        \App\Models\Seguimiento::create([
                            'seguimiento'          => $data['seguimiento'],
                            'user_id'              => auth()->id(),
                            'seguimientable_id'    => $record->cliente_id,
                            'seguimientable_type'  => \App\Models\User::class,
                            'tipo'                 => 'consulta', 
                        ]);

                        Notification::make()
                        ->title('Consulta registrada')
                        ->success()
                        ->send();
                    })
                    ->modalHeading('Nueva consulta')
                    ->modalSubmitActionLabel('Guardar'),

                Tables\Actions\Action::make('seguimiento')
                    ->label('Seguimiento')
                    ->icon('heroicon-o-phone')
                    ->form([
                        Forms\Components\Textarea::make('seguimiento')
                            ->label('Nota de seguimiento')
                            ->required(),
                    ])
                    ->action(function (Venta $record, array $data): void {
                        \App\Models\Seguimiento::create([
                            'seguimiento'          => $data['seguimiento'],
                            'user_id'              => auth()->id(),
                            'seguimientable_id'    => $record->cliente_id,
                            'seguimientable_type'  => \App\Models\User::class,
                            'tipo'                 => 'seguimiento', 
                        ]);

                        Notification::make()
                        ->title('Seguimiento registrado')
                        ->success()
                        ->send();
                    })
                    ->modalHeading('Nuevo seguimiento')
                    ->modalSubmitActionLabel('Guardar'),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCaidos::route('/'),
            'create' => Pages\CreateCaidos::route('/create'),
            'edit' => Pages\EditCaidos::route('/{record}/edit'),
        ];
    }
}
