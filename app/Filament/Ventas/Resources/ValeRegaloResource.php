<?php

namespace App\Filament\Ventas\Resources;

use App\Filament\Ventas\Resources\ValeRegaloResource\Pages;
use App\Models\ValeRegalo;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ValeRegaloResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = ValeRegalo::class;

    protected static ?string $modelLabel = 'Vale de regalo';

    protected static ?string $pluralModelLabel = 'Vales de regalo';

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Ventas';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'force_delete',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                ])
                    ->schema([
                        TextInput::make('correlativo')
                            ->label('Número de correlativo')
                            ->required()
                            ->maxLength(100)
                            ->unique(table: ValeRegalo::class, ignorable: fn ($record) => $record),
                        TextInput::make('monto')
                            ->label('Monto')
                            ->prefix('Q')
                            ->inputMode('decimal')
                            ->rule('numeric')
                            ->minValue(0.01)
                            ->required(),
                        TextInput::make('de')
                            ->label('De (Remitente)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('para')
                            ->label('Para (Destinatario)')
                            ->required()
                            ->maxLength(255),
                        Select::make('estado')
                            ->label('Estado')
                            ->options([
                                'disponible' => 'Disponible',
                                'canjeado' => 'Canjeado',
                                'anulado' => 'Anulado',
                            ])
                            ->default('disponible')
                            ->required(),
                        Hidden::make('user_id')
                            ->default(fn () => Auth::id()),
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('correlativo')
                    ->label('Correlativo')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('de')
                    ->label('De')
                    ->searchable(),
                TextColumn::make('para')
                    ->label('Para')
                    ->searchable(),
                TextColumn::make('monto')
                    ->label('Monto')
                    ->money('GTQ')
                    ->sortable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'success' => 'disponible',
                        'warning' => 'canjeado',
                        'danger' => 'anulado',
                    ]),
                TextColumn::make('user.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fecha_canje')
                    ->label('Fecha de Canje')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('venta_id')
                    ->label('Venta')
                    ->formatStateUsing(fn ($state) => $state ? "#{$state}" : '-')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Fecha Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'disponible' => 'Disponible',
                        'canjeado' => 'Canjeado',
                        'anulado' => 'Anulado',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListValesRegalo::route('/'),
            'create' => Pages\CreateValeRegalo::route('/create'),
            'edit' => Pages\EditValeRegalo::route('/{record}/edit'),
        ];
    }
}
