<?php

namespace App\Filament\Inventario\Resources;

use App\Filament\Inventario\Resources\InventarioResource\Pages;
use App\Models\Bodega;
use App\Models\Inventario;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class InventarioResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Inventario::class;

    protected static ?string $slug = 'inventario';

    protected static ?string $pluralModelLabel = 'Inventario';

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 1;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'adjust',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->groups([
                'producto.id',
            ])
            ->defaultGroup('producto.id')
            ->columns([
                Tables\Columns\TextColumn::make('producto.imagenes')
                    ->label('Imágen')
                    ->formatStateUsing(function ($record): View {
                        return view('filament.tables.columns.image', [
                            'url' => config('filesystems.disks.s3.url').$record->producto->imagenes[0],
                            'alt' => $record->producto->descripcion,
                        ]);
                    }),
                Tables\Columns\TextColumn::make('existencia')
                    ->copyable()
                    ->summarize(Sum::make())
                    ->sortable(),
                Tables\Columns\TextColumn::make('bodega.bodega')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.id')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.codigo')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.descripcion')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.marca.marca')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                /* Tables\Columns\TextColumn::make('producto.presentacion.presentacion')
                    ->searchable()
                    ->copyable()
                    ->sortable(), */
            ])
            ->filters([
                SelectFilter::make('bodega_id')
                    ->label('Bodega')
                    ->options(fn () => Bodega::pluck('bodega', 'id')->toArray()),
            ])->poll('10s');
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
            'index' => Pages\ListInventarios::route('/'),
        ];
    }
}
