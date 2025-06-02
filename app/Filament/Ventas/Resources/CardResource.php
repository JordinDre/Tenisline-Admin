<?php

namespace App\Filament\Ventas\Resources;

use Filament\Forms;
use App\Models\Card;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Ventas\Resources\CardResource\Pages;
use App\Filament\Ventas\Resources\CardResource\RelationManagers;

class CardResource extends Resource
{
    protected static ?string $model = Card::class;

    protected static ?string $modelLabel = 'Tarjeta %';

    protected static ?string $pluralModelLabel = 'Tarjetas %';

    protected static ?string $navigationIcon = 'heroicon-o-percent-badge';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                    'md' => 4,
                    'lg' => 2,
                    'xl' => 4,
                ])
                    ->schema([
                        TextInput::make('correlativo')
                            ->required()
                            ->maxLength(100),
                        Select::make('cliente_id')
                            ->label('Cliente')
                            ->relationship('user', 'name', fn (Builder $query) => $query->role(['cliente', 'colaborador']))
                            ->optionsLimit(20)
                            ->required()
                            ->searchable(),
                        Hidden::make('user_id')
                            ->default(auth()->user()->id),
                        TextInput::make('dpi')
                            ->label('DPI')
                            ->maxLength(13)
                            ->minLength(13)
                            ->required()
                            ->unique(table: User::class, ignorable: fn ($record) => $record),
                        /* Toggle::make('estado')
                        ->label('Activo') */
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->searchable()
                    ->label('ID')
                    ->copyable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->listWithLineBreaks(),
                TextColumn::make('correlativo')
                    ->listWithLineBreaks(),
                TextColumn::make('cliente.name')
                    ->label('Cliente')
                    ->listWithLineBreaks(),
                TextColumn::make('dpi')
                    ->label('DPI')
                    ->listWithLineBreaks(),
                /* Tables\Columns\TextColumn::make('estado')
                ->badge()
                ->formatStateUsing(fn ($state) => $state == 1 ? 'Activo' : 'Inactivo')
                ->colors([
                    'success' => fn ($state) => $state == 1,
                    'danger' => fn ($state) => $state == 0,
                ]), */
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
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
            'index' => Pages\ListCards::route('/'),
            'create' => Pages\CreateCard::route('/create'),
            'edit' => Pages\EditCard::route('/{record}/edit'),
        ];
    }
}
