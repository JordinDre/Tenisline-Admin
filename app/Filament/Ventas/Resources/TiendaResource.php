<?php

namespace App\Filament\Ventas\Resources;

use App\Filament\Ventas\Resources\TiendaResource\Pages;
use App\Models\Tienda;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TiendaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Tienda::class;

    protected static ?string $modelLabel = 'Tienda';

    protected static ?string $pluralModelLabel = 'Tienda';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationIcon = 'tabler-world-www';

    protected static ?string $navigationLabel = 'Tienda';

    protected static ?string $navigationGroup = 'Gestiones';

    protected static ?int $navigationSort = 3;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'update',
            'view',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Builder::make('contenido')
                    ->columnSpanFull()
                    ->cloneable()
                    ->reorderableWithButtons()
                    ->blocks([
                        Builder\Block::make('carrusel')
                            ->schema([
                                FileUpload::make('imagen')
                                    ->image()
                                    ->downloadable()
                                    ->label('Imágenes')
                                    ->imageEditor()
                                    ->disk(config('filesystems.disks.s3.driver'))
                                    ->directory(config('filesystems.default'))
                                    ->visibility('public')
                                    ->appendFiles()
                                    ->maxSize(5000)
                                    ->resize(50)
                                    ->openable()
                                    ->columnSpan(['sm' => 1, 'md' => 3])
                                    ->optimize('webp')
                                    ->multiple()
                                    ->panelLayout('grid')
                                    ->required(),
                            ])
                            ->columns(2),

                        Builder\Block::make('banner')
                            ->label('Banner')
                            ->schema([
                                Textarea::make('contenido')
                                    ->required(),
                                Select::make('color')
                                    ->label('Color del Banner')
                                    ->options([
                                        'blue' => 'Azul',
                                        'green' => 'Verde',
                                        'red' => 'Rojo',
                                        'yellow' => 'Amarillo',
                                    ])
                                    ->default('black') // Color por defecto
                                    ->required(),
                            ]),

                        Builder\Block::make('productos')
                            ->label('Productos')
                            ->schema([
                                TextInput::make('titulo')
                                    ->required(),
                                FileUpload::make('imagen')
                                    ->image()
                                    ->downloadable()
                                    ->label('Imágenes')
                                    ->imageEditor()
                                    ->disk(config('filesystems.disks.s3.driver'))
                                    ->directory(config('filesystems.default'))
                                    ->visibility('public')
                                    ->appendFiles()
                                    ->maxSize(5000)
                                    ->resize(50)
                                    ->openable()
                                    ->columnSpan(['sm' => 1, 'md' => 3])
                                    ->optimize('webp')
                                    ->multiple()
                                    ->panelLayout('grid')
                                    ->required(),
                            ]),
                        Builder\Block::make('seccion')
                            ->label('Sección')
                            ->schema([
                                FileUpload::make('imagen')
                                    ->image()
                                    ->downloadable()
                                    ->label('Imágen')
                                    ->imageEditor()
                                    ->disk(config('filesystems.disks.s3.driver'))
                                    ->directory(config('filesystems.default'))
                                    ->visibility('public')
                                    ->appendFiles()
                                    ->maxSize(5000)
                                    ->resize(50)
                                    ->openable()
                                    ->columnSpan(['sm' => 1, 'md' => 3])
                                    ->optimize('webp')
                                    ->required(),
                                Textarea::make('contenido')
                                    ->label('Contenido')
                                    ->columnSpanFull()
                                    ->required(),
                            ])
                            ->columns(2),
                        Builder\Block::make('imagen')
                            ->label('Imágen')
                            ->schema([
                                FileUpload::make('imagen')
                                    ->image()
                                    ->downloadable()
                                    ->label('Imágen')
                                    ->imageEditor()
                                    ->disk(config('filesystems.disks.s3.driver'))
                                    ->directory(config('filesystems.default'))
                                    ->visibility('public')
                                    ->appendFiles()
                                    ->maxSize(5000)
                                    ->resize(50)
                                    ->openable()
                                    ->columnSpan(['sm' => 1, 'md' => 3])
                                    ->optimize('webp')
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contenido')
                    ->label('Contenido')
                    ->limit(100) // Limitar el número de caracteres mostrados
                    ->copyable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTiendas::route('/'),
            'create' => Pages\CreateTienda::route('/create'),
            'edit' => Pages\EditTienda::route('/{record}/edit'),
        ];
    }

    public static function getNavigationItems(): array //  AÑADE ESTE MÉTODO
    {
        return [
            parent::getNavigationItems()[0] // Obtiene el elemento de navegación por defecto
                ->visible(false), //  Aplica ->visible(false) para ocultarlo SIEMPRE
        ];
    }
}
