<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MetaResource\Pages;
use App\Http\Controllers\Utils\Functions;
use App\Models\Meta;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MetaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Meta::class;

    protected static ?string $modelLabel = 'Meta';

    protected static ?string $pluralModelLabel = 'Metas';

    protected static ?string $navigationIcon = 'tabler-file-text';

    protected static ?string $navigationLabel = 'Metas';

    protected static ?int $navigationSort = 4;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'update',
            'restore',
            'delete',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bodega_id')
                    ->label('Bodega')
                    ->relationship(
                        name: 'bodega',
                        titleAttribute: 'bodega',
                        modifyQueryUsing: function (Builder $query) {
                            $query->whereNotIn('bodega', ['Mal estado', 'Traslado'])
                                ->where('bodega', 'not like', '%bodega%');
                        }
                    )
                    ->optionsLimit(20)
                    ->reactive()
                    ->rules([
                        fn (Get $get, string $operation): Closure => function (string $attribute, $value, Closure $fail) use ($get, $operation) {
                            if ($operation == 'create') {
                                $exists = Meta::where('bodega_id', $value)
                                    ->where('mes', $get('mes'))
                                    ->where('anio', $get('anio'))
                                    ->exists();
                                if ($exists) {
                                    $fail('Ya existe una meta para esta bodega en el mes y año indicados.');
                                }
                            }
                        },
                    ])
                    ->required(),
                Forms\Components\Select::make('mes')
                    ->options(Functions::obtenerMeses())
                    ->default(now()->month)
                    ->required(),
                Forms\Components\Select::make('anio')
                    ->label('Año')
                    ->options(array_combine(Functions::obtenerAnios(), Functions::obtenerAnios()))
                    ->default(now()->year)
                    ->required(),
                Forms\Components\TextInput::make('meta')
                    ->label('Meta (Q)')
                    ->required()
                    ->inputMode('decimal')
                    ->rule('numeric')
                    ->minValue(0)
                    ->step(0.01),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('bodega.bodega')
                    ->numeric()
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mes')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        return Functions::nombreMes($record->mes);
                    })
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('anio')
                    ->label('Año')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('meta')
                    ->label('Meta (Q)')
                    ->money('GTQ')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ventas_reales')
                    ->label('Ventas Reales (Q)')
                    ->money('GTQ')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('alcance')
                    ->label('Alcance (%)')
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->copyable()
                    ->sortable()
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 75 ? 'warning' : 'danger')),
                Tables\Columns\IconColumn::make('cumplida')
                    ->label('Cumplida')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('mes')
                    ->options(Functions::obtenerMeses()),
                Tables\Filters\SelectFilter::make('anio')
                    ->label('Año')
                    ->options(array_combine(Functions::obtenerAnios(), Functions::obtenerAnios())),
                Tables\Filters\SelectFilter::make('bodega_id')
                    ->label('Bodega')
                    ->relationship('bodega', 'bodega')
                    ->searchable()
                    ->preload(),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMetas::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
