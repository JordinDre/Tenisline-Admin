<?php

namespace App\Filament\Ventas\Resources;

use App\Filament\Ventas\Resources\UserResource\Pages;
use App\Http\Controllers\UserController;
use App\Models\Bodega;
use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Observacion;
use App\Models\Orden;
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
use Illuminate\Support\Facades\DB;

class UserResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

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
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    // Solo validar unique si el NIT no es CF
                                    if (strtoupper(trim($value)) !== 'CF') {
                                        $query = User::query()->where('nit', $value);

                                        // Si estamos editando, ignorar el registro actual
                                        if ($get('id')) {
                                            $query->where('id', '!=', $get('id'));
                                        }

                                        if ($query->exists()) {
                                            $fail('El campo NIT ya ha sido registrado.');
                                        }
                                    }
                                },
                            ])
                            ->afterStateUpdated(function (Set $set, $state) {
                                $nit = UserController::nit($state);
                                $set('razon_social', $nit);
                            }),
                        /* TextInput::make('dpi')
                            ->label('DPI')
                            ->maxLength(13)
                            ->minLength(13), */
                        TextInput::make('razon_social')
                            ->required()
                            ->readOnly()
                            ->default('CF')
                            ->label('Razón Social'),
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
                            /* ->live(onBlur: true)
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
                            }) */
                            ->searchable(),
                        /* Select::make('comercios')
                            ->label('Tipos de Comercio')
                            ->required(fn (Get $get) => in_array(4, $get('roles')) || in_array(5, $get('roles')))
                            ->relationship('comercios', 'comercio')
                            ->multiple()
                            ->live()
                            ->searchable(),
                        Select::make('asesores')
                            ->multiple()
                            ->rules([
                                'max:1',
                                fn (Get $get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                                    if ($record) {
                                        // Obtener los IDs de los asesores actuales (en caso de que sea una colección de User)
                                        $asesoresAnteriores = $record->asesores->pluck('id')->toArray();
                                        $nuevosAsesores = (array) $value; // Convertir el valor a array si es necesario

                                        // Verificar si hay cambios en la lista de asesores
                                        if (array_diff($nuevosAsesores, $asesoresAnteriores) || array_diff($asesoresAnteriores, $nuevosAsesores)) {

                                            // ❌ Validar que los nuevos asesores no tengan el rol "Asesor Telemarketing"
                                            $asesoresInvalidos = \App\Models\User::whereIn('id', $nuevosAsesores)
                                                ->whereHas('roles', function ($query) {
                                                    $query->where('name', 'Asesor Telemarketing');
                                                })
                                                ->pluck('id')
                                                ->toArray();

                                            if (! empty($asesoresInvalidos)) {
                                                $fail('No puedes asignar asesores con el rol de "Asesor Telemarketing".');
                                            }

                                            // ❌ Validar los 60 días desde la última compra
                                            if ($record->ordenes()->exists()) {
                                                $ultimaOrden = $record->ordenes()
                                                    ->whereNotIn('estado', Orden::ESTADOS_EXCLUIDOS)
                                                    ->latest()
                                                    ->first();

                                                if ($ultimaOrden && $ultimaOrden->created_at->diffInDays() < 60) {
                                                    $fail('No se puede realizar el traslado de Asesor porque el cliente no cumple los 60 días sin compra.');
                                                }
                                            }
                                        }
                                    }
                                },
                            ])
                            ->disabled(fn (Get $get) => ! in_array(5, $get('roles')))
                            ->relationship('asesores', 'name', fn (Builder $query) => $query->role(User::ASESOR_ROLES))
                            ->searchable(), */
                        /* Select::make('supervisores')
                            ->multiple()
                            ->disabled(fn(Get $get) => ! array_intersect([9, 10, 11], $get('roles')))
                            ->relationship('supervisores', 'name', fn(Builder $query) => $query->role(User::SUPERVISOR_ROLES))
                            ->searchable(), */
                        Select::make('tipo_pagos')
                            ->label('Tipos de Pago')
                            ->required(fn (Get $get) => in_array(5, $get('roles')))
                            ->relationship('tipo_pagos', 'tipo_pago', fn ($query) => $query->whereIn('tipo_pago', TipoPago::CLIENTE_PAGOS_SIN_CREDITO))
                            ->multiple()
                            ->preload()
                            ->searchable(),
                        /* Select::make('tipo_pagos')
                            ->label('Tipos de Pago')
                            ->required(fn(Get $get) => in_array(5, $get('roles')))
                            ->relationship('tipo_pagos', 'tipo_pago', fn($query) => $query->whereIn('tipo_pago', TipoPago::CLIENTE_PAGOS))
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if (! in_array(2, $state)) {
                                    $set('credito', null);
                                    $set('credito_dias', null);
                                }
                            })
                            ->multiple()
                            ->preload()
                            ->searchable(), */
                        /* TextInput::make('credito')
                            ->required(fn(Get $get) => in_array(2, $get('tipo_pagos')))
                            ->disabled(fn(Get $get) => ! in_array(2, $get('tipo_pagos')))
                            ->minValue(0)
                            ->inputMode('decimal')
                            ->rule('numeric')
                            ->label('Credito Máximo(Q)'),
                        TextInput::make('credito_dias')
                            ->required(fn(Get $get) => in_array(2, $get('tipo_pagos')))
                            ->disabled(fn(Get $get) => ! in_array(2, $get('tipo_pagos')))
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
                /* FileUpload::make('imagenes')
                    ->label('Imágenes')
                    ->image()
                    ->downloadable()
                    ->imageEditor()
                    ->multiple()
                    ->panelLayout('grid')
                    ->reorderable()
                    ->appendFiles()
                    ->maxSize(5000)
->resize(50)
                    ->openable()
                    ->optimize('webp'), */
                Repeater::make('direcciones')
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
                        /* TextInput::make('encargado')
                            ->required()
                            ->maxLength(50),
                        TextInput::make('encargado_contacto')
                            ->label('Contacto del Encargado')
                            ->required()
                            ->tel()
                            ->minLength(8)
                            ->maxLength(8), */
                    ])->columnSpanFull()->columns(4)->defaultItems(0),
                Repeater::make('observaciones')
                    ->relationship()
                    ->defaultItems(0)
                    ->schema([
                        Textarea::make('observacion')->label('Observación'),
                        Hidden::make('user_id')
                            ->default(auth()->user()->id),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('id')
                    ->searchable()
                    ->label('ID')
                    ->copyable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->listWithLineBreaks()
                    ->bulleted(),
                TextColumn::make('razon_social')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre/Nombre Comercial')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('asesores.name')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted(),
                /* TextColumn::make('supervisores.name')
                    ->searchable()
                    ->copyable()
                    ->sortable(), */
                TextColumn::make('nit')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('telefono')
                    ->searchable()
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
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('Desactivar')
                        ->visible(fn ($record) => auth()->user()->can('delete', $record))
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->modalWidth(MaxWidth::SevenExtraLarge)
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
                    Tables\Actions\Action::make('historial')
                        ->icon('heroicon-o-document-text')
                        ->modalWidth(MaxWidth::SevenExtraLarge)
                        ->modalSubmitAction(false)
                        ->modalContent(fn ($record): View => view(
                            'filament.pages.actions.historial-ventas',
                            [
                                'ventas' => DB::select('
                                                select
                                                ventas.id as venta_id,
                                                users.name,
                                                ventas.created_at as fecha_venta,
                                                productos.codigo,
                                                productos.descripcion,
                                                marcas.marca,
                                                productos.talla,
                                                productos.genero,
                                                venta_detalles.cantidad,
                                                venta_detalles.subtotal,
                                                (
                                                    select
                                                        u.name
                                                    from
                                                        users u
                                                    where
                                                        u.id = ventas.asesor_id
                                                ) as asesor
                                            from
                                                ventas
                                                inner join users on users.id = ventas.cliente_id
                                                inner join venta_detalles on venta_detalles.venta_id = ventas.id
                                                inner join productos on venta_detalles.producto_id = productos.id
                                                inner join marcas on productos.marca_id = marcas.id
                                            WHERE
                                                ventas.cliente_id = ?
                                            ORDER BY
                                                ventas.created_at DESC
                                            ', [
                                    $record->id,
                                ]),
                            ],
                        ))
                        ->label('Historial'),
                    Tables\Actions\RestoreAction::make(),
                ])
                    ->link()
                    ->label('Acciones'),

            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Desactivar'),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
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
