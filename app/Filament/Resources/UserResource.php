<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\OrdenesRelationManager;
use App\Http\Controllers\UserController;
use App\Models\Bodega;
use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Observacion;
use App\Models\TipoPago;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class UserResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'update',
            'restore',
            'delete',
            'impersonate',
        ];
    }

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
                        TextInput::make('nit')
                            ->default('CF')
                            ->required()
                            ->maxLength(25)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, $state) {
                                $nit = UserController::nit($state);
                                $set('razon_social', $nit);
                            }),
                        TextInput::make('dpi')
                            ->label('DPI')
                            ->maxLength(13)
                            ->minLength(13),
                        /* TextInput::make('razon_social')
                            ->required()
                            ->readOnly()
                            ->default('CF')
                            ->label('Razón Social'), */
                        TextInput::make('name')
                            ->required()
                            ->label('Nombre/Nombre Comercial'),
                        TextInput::make('telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->required()
                            ->minLength(8)
                            ->maxLength(8),
                        TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->tel()
                            ->minLength(8)
                            ->maxLength(8),
                        TextInput::make('email')
                            ->unique(ignoreRecord: true)
                            ->email()
                            ->maxLength(100),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                        DatePicker::make('fecha_nacimiento')
                            ->label('Fecha de Nacimiento'),
                        Select::make('roles')
                            ->relationship('roles', 'name', fn ($query) => $query->whereNotIn('name', User::ROLES_ADMIN))
                            ->multiple()
                            ->preload()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, $state, $record) {
                                if ($record) {
                                    if (! in_array(5, $state)) {
                                        $set('asesores', []);
                                        $record->asesores()->detach();
                                    }
                                    if (! array_intersect([9, 10, 11], $state)) {
                                        $set('supervisores', []);
                                        $record->supervisores()->detach();
                                    }
                                }
                            })
                            ->searchable(),
                        /* Select::make('comercios')
                            ->label('Tipos de Comercio')
                            ->required(fn (Get $get) => in_array(4, $get('roles')) || in_array(5, $get('roles')))
                            ->relationship('comercios', 'comercio')
                            ->multiple()
                            ->live()
                            ->searchable(), */
                        /* Select::make('asesores')
                            ->multiple()
                            ->rules(['max:1'])
                            ->disabled(fn (Get $get) => ! in_array(5, $get('roles')))
                            ->relationship('asesores', 'name', fn (Builder $query) => $query->role(User::ASESOR_ROLES))
                            ->searchable(), */
                        /* Select::make('supervisores')
                            ->multiple()
                            ->disabled(fn (Get $get) => ! array_intersect([9, 10, 11], $get('roles')))
                            ->relationship('supervisores', 'name', fn (Builder $query) => $query->role(User::SUPERVISOR_ROLES))
                            ->searchable(), */
                        Select::make('tipo_pagos')
                            ->label('Tipos de Pago')
                            ->required(fn (Get $get) => in_array([4,6], $get('roles')))
                            ->relationship('tipo_pagos', 'tipo_pago', fn ($query) => $query->whereIn('tipo_pago', TipoPago::CLIENTE_PAGOS))
                            ->live()
                            /* ->afterStateUpdated(function (Set $set, $state) {
                                if (! in_array(2, $state)) {
                                    $set('credito', null);
                                    $set('credito_dias', null);
                                }
                            })
                            ->rules([
                                fn (Get $get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                                    if ($record && ($record->creditosOrdenesPendientes->count() > 0 || $record->creditosVentasPendientes->count() > 0) && ! in_array(2, $value)) {
                                        $fail('No se puede quitar el tipo de pago CREDITO porque tiene créditos pendientes');
                                    }
                                },
                            ]) */
                            ->multiple()
                            ->preload()
                            ->searchable(),
                        /* TextInput::make('credito')
                            ->required(fn (Get $get) => in_array(2, $get('tipo_pagos')))
                            ->disabled(fn (Get $get) => ! in_array(2, $get('tipo_pagos')))
                            ->minValue(0)
                            ->inputMode('decimal')
                            ->rule('numeric')
                            ->label('Credito Máximo(Q)'),
                        TextInput::make('credito_dias')
                            ->required(fn (Get $get) => in_array(2, $get('tipo_pagos')))
                            ->disabled(fn (Get $get) => ! in_array(2, $get('tipo_pagos')))
                            ->minValue(0)
                            ->inputMode('decimal')
                            ->rule('numeric')
                            ->label('Días de Crédito'), */
                    ]),
                Select::make('bodegas')
                    ->multiple()
                    ->required(fn (Get $get) => ! empty(array_intersect([1, 2, 3, 12, 13, 14, 16, 17], $get('roles'))))
                    ->relationship(
                        'bodegas',
                        'bodega',
                        fn (Builder $query) => $query->whereNotIn('bodega', Bodega::TRASLADO_NAME)
                    )
                    ->preload()
                    ->searchable(),
                FileUpload::make('imagenes')
                    ->label('Imágenes')
                    ->image()
                    ->downloadable()
                    ->imageEditor()
                    ->multiple()
                    ->panelLayout('grid')
                    ->reorderable()
                    ->appendFiles()
                    ->maxSize(1024)
                    ->openable()
                    ->optimize('webp'),
                /* Repeater::make('direcciones')
                    ->relationship()
                    ->schema([
                        Select::make('pais_id')
                            ->relationship('pais', 'pais')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set) {
                                $set('departamento_id', null);
                                $set('municipio_id', null);
                            })
                            ->default(1)
                            ->searchable()
                            ->preload(),
                        Select::make('departamento_id')
                            ->label('Departamento')
                            ->options(fn (Get $get) => Departamento::where('pais_id', $get('pais_id'))->pluck('departamento', 'id'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set) {
                                $set('municipio_id', null);
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('municipio_id')
                            ->label('Municipio')
                            ->options(fn (Get $get) => Municipio::where('departamento_id', $get('departamento_id'))->pluck('municipio', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('direccion')
                            ->required()
                            ->label('Dirección')
                            ->maxLength(255),
                        TextInput::make('referencia')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('zona')
                            ->label('Zona')
                            ->inputMode('decimal')
                            ->rule('numeric')
                            ->minValue(0),
                        TextInput::make('encargado')
                            ->required()
                            ->maxLength(50),
                        TextInput::make('encargado_contacto')
                            ->label('Contacto del Encargado')
                            ->required()
                            ->tel()
                            ->minLength(8)
                            ->maxLength(8),
                    ])->columnSpanFull()->columns(4)->defaultItems(0)->required(fn (Get $get) => in_array(4, $get('roles')) || in_array(5, $get('roles'))), */
                /* Repeater::make('observaciones')
                    ->relationship()
                    ->defaultItems(0)
                    ->schema([
                        Textarea::make('observacion')->label('Observación'),
                        Hidden::make('user_id')
                            ->default(auth()->user()->id),
                    ])->columnSpanFull(), */
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->listWithLineBreaks()
                    ->bulleted(),
                /* TextColumn::make('razon_social')
                    ->searchable()
                    ->copyable()
                    ->sortable(), */
                TextColumn::make('name')
                    ->label('Nombre/Nombre Comercial')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->listWithLineBreaks()
                    ->bulleted(),
                /* TextColumn::make('asesores.name')
                    ->listWithLineBreaks()
                    ->bulleted(),
                TextColumn::make('supervisores.name')
                    ->copyable()
                    ->sortable(), */
                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->copyable()
                    ->sortable(),
                TextColumn::make('whatsapp')
                    ->label('Whatsapp')
                    ->copyable()
                    ->sortable(),
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Impersonate::make()->visible(fn ($record) => auth()->user()->can('impersonate', $record)),
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('Desactivar')
                        ->visible(fn ($record) => auth()->user()->can('delete', $record))
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->modalWidth(MaxWidth::ThreeExtraLarge)
                        ->form([
                            Textarea::make('observacion')
                                ->label('Observación')
                                ->minLength(5)
                                ->required(),
                        ])
                        ->action(function (array $data, User $record): void {
                            $observacion = new Observacion;
                            $observacion->observacion = $data['observacion'];
                            $observacion->user_id = auth()->user()->id;
                            $record->observaciones()->save($observacion);
                            $record->delete();

                            Notification::make()
                                ->title('Usuario desactivado')
                                ->color('success')
                                ->success()
                                ->send();
                        })
                        ->modalContent(fn (User $record): View => view(
                            'filament.pages.actions.observaciones',
                            ['record' => $record],
                        ))
                        ->label('Desactivar'),
                    Tables\Actions\RestoreAction::make(),
                ])
                    ->link()
                    ->label('Acciones'),

            ], position: ActionsPosition::BeforeColumns)
            /* ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Desactivar'),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]) */
            ->poll('10s');
    }

    public static function getRelations(): array
    {
        return [
        /* OrdenesRelationManager::class, */];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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
