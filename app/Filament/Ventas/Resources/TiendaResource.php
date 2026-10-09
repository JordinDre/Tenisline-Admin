<?php

namespace App\Filament\Ventas\Resources;

use App\Filament\Ventas\Resources\TiendaResource\Pages;
use App\Models\Tienda;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
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

    protected static ?string $navigationIcon = 'tabler-world-www';

    protected static ?string $navigationLabel = 'Promociones web';

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
                    ->label('Promociones y avisos del sitio web')
                    ->helperText('Las promociones se muestran como carrusel al inicio; los avisos rotan en la barra superior de todas las páginas.')
                    ->columnSpanFull()
                    ->cloneable()
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->addActionLabel('Agregar promoción')
                    ->blocks([
                        Builder\Block::make('promocion')
                            ->label(fn (?array $state) => 'Promoción'.(! empty($state['titulo']) ? ': '.$state['titulo'] : ''))
                            ->icon('heroicon-o-megaphone')
                            ->schema([
                                FileUpload::make('imagen')
                                    ->label('Imagen (computadora)')
                                    ->helperText('Horizontal, idealmente 1920 × 720 px.')
                                    ->image()
                                    // 'android/allowCamera' hace que Chrome en Android 14+ muestre la opción de cámara.
                                    ->acceptedFileTypes(['image/*', 'android/allowCamera'])
                                    ->imageEditor()
                                    ->disk(config('filesystems.disks.s3.driver'))
                                    ->directory(config('filesystems.upload_directory').'/promociones')
                                    ->visibility('public')
                                    ->maxSize(5000)
                                    ->optimize('webp')
                                    ->openable(),
                                FileUpload::make('imagen_movil')
                                    ->label('Imagen (celular, opcional)')
                                    ->helperText('Vertical, idealmente 1080 × 1350 px. Si no se sube, se usa la de computadora.')
                                    ->image()
                                    // 'android/allowCamera' hace que Chrome en Android 14+ muestre la opción de cámara.
                                    ->acceptedFileTypes(['image/*', 'android/allowCamera'])
                                    ->imageEditor()
                                    ->disk(config('filesystems.disks.s3.driver'))
                                    ->directory(config('filesystems.upload_directory').'/promociones')
                                    ->visibility('public')
                                    ->maxSize(5000)
                                    ->optimize('webp')
                                    ->openable(),
                                TextInput::make('titulo')->label('Título')->maxLength(80),
                                TextInput::make('subtitulo')->label('Subtítulo')->maxLength(160),
                                TextInput::make('boton')->label('Texto del botón')->placeholder('Ver ofertas')->maxLength(30),
                                TextInput::make('enlace')
                                    ->label('Enlace del botón')
                                    ->placeholder('/catalogo?categoria=ofertas')
                                    ->helperText('Ej.: /catalogo?marca=NIKE, /catalogo?categoria=dama, /catalogo?categoria=ofertas'),
                                DatePicker::make('desde')->label('Mostrar desde'),
                                DatePicker::make('hasta')->label('Mostrar hasta'),
                                Toggle::make('activo')->label('Activa')->default(true),
                            ])
                            ->columns(2),
                        Builder\Block::make('aviso')
                            ->label(fn (?array $state) => 'Aviso de la barra superior'.(! empty($state['texto']) ? ': '.$state['texto'] : ''))
                            ->icon('heroicon-o-bell-alert')
                            ->schema([
                                TextInput::make('texto')->label('Texto')->required()->maxLength(90),
                                TextInput::make('boton')->label('Texto del enlace')->placeholder('Ver ofertas')->maxLength(25),
                                TextInput::make('enlace')->label('Enlace')->placeholder('/catalogo?categoria=ofertas'),
                                Toggle::make('activo')->label('Activo')->default(true),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->paginated([10, 25, 50])
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
