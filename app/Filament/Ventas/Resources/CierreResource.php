<?php

namespace App\Filament\Ventas\Resources;

use Closure;
use Filament\Forms;
use App\Models\Pago;
use Filament\Tables;
use App\Models\Banco;
use App\Models\Cierre;
use Filament\Forms\Get;
use App\Models\TipoPago;
use Filament\Forms\Form;
use App\Models\CajaChica;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Illuminate\Contracts\View\View;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Http\Controllers\VentaController;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Ventas\Resources\CierreResource\Pages;

class CierreResource extends Resource
{
    protected static ?string $model = Cierre::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?string $navigationLabel = 'Cierres';

    protected static ?string $label = 'Cierre';

    protected static ?string $pluralLabel = 'Cierres';

    protected static ?string $slug = 'cierres';

    public static function shouldRegisterNavigation(): bool
    {
        return ! \App\Filament\Ventas\Resources\VentaResource::hayBloqueo();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bodega_id')
                    ->label('Bodega')
                    ->relationship(
                        'bodega',
                        'bodega',
                        fn (Builder $query) => $query
                            ->whereHas('user', fn ($q) => $q->where('user_id', Auth::id())
                            )
                            ->whereNotIn('bodega', ['Mal estado', 'Traslado'])
                            ->where('bodega', 'not like', '%bodega%')
                    )
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) {
                            $exists = Cierre::where('bodega_id', $value)
                                ->whereNull('cierre')
                                ->exists();

                            if ($exists) {
                                $fail('Ya existe un cierre abierto para esta bodega. Debe cerrar el anterior.');
                            }

                            $cierreHoy = Cierre::where('user_id', Auth::id())
                                ->whereDate('apertura', now()->toDateString())
                                ->exists();

                            if ($cierreHoy) {
                                $fail('Ya realizaste un cierre hoy. Solo puedes crear uno por día.');
                            }

                        },
                    ])
                    ->searchable()
                    ->preload()
                    ->live()
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\Hidden::make('user_id')
                    ->default(Auth::user()->id),
                Forms\Components\Hidden::make('apertura')
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bodega.bodega')
                    ->label('Bodega')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('apertura')
                    ->dateTime()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('cierre')
                    ->searchable()
                    ->dateTime()
                    ->sortable(),
                /* Tables\Columns\TextColumn::make('ventas_ids')
                    ->label('Ventas')
                    ->listWithLineBreaks()
                    ->searchable(), */
                Tables\Columns\TextColumn::make('total_tenis')
                    ->label('Cantidad Tenis'),
                Tables\Columns\TextColumn::make('total_ventas')
                    ->label('Total')
                    ->money('GTQ') // o 'USD', o elimina si no quieres formato de moneda
                    ->sortable(),
                Tables\Columns\TextColumn::make('resumen_pagos')
                    ->label('Resumen Pagos')
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('resumen_pagos_liquidacion')
                    ->label('Liquidaciones Realizados')
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('cajas_chicas_relacionadas')
                    ->label('Cajas Chicas (Pendientes o Pagadas en Cierre)')
                    ->listWithLineBreaks()
                    ->wrap()
                    ->limitList(5)
                    ->placeholder('— Sin registros —'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('Cerrar')
                    ->label('Cerrar Turno')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->visible(fn (Cierre $record) => $record->user_id === Auth::id() && $record->cierre === null)
                    ->action(function (Cierre $record) {
                        DB::transaction(function () use ($record) {
                            $record->update(['cierre' => now()]);
                            if ($record->tieneVentaContado()) {
                                CajaChica::where('bodega_id', $record->bodega_id)
                                    ->where('aplicado', false)
                                    ->update([
                                        'aplicado' => true,
                                        'aplicado_en_cierre_id' => $record->id,
                                    ]);
                            }

                            activity()
                                ->performedOn($record)
                                ->causedBy(Auth::user())
                                ->withProperties(['bodega_id' => $record->bodega_id])
                                ->log('Cierre de turno completado');
                        });

                        Notification::make()
                            ->title('Cierre completado correctamente')
                            ->success()
                            ->send();
                    }),
                Action::make('cierre')
                    ->icon('heroicon-o-document-arrow-down')
                    ->modalContent(fn (Cierre $record): View => view(
                        'filament.pages.actions.iframe',
                        [
                            'record' => $record,
                            'title' => 'Cierre #'.$record->id,
                            'route' => route('pdf.cierre', ['id' => $record->id]),
                            'open' => true,
                        ],
                    ))
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->slideOver()
                    ->stickyModalHeader()
                    ->modalSubmitAction(false),
                Action::make('ver_pagos_liquidacion')
                    ->label('Ver Pagos')
                    ->icon('heroicon-o-eye')
                    ->visible(fn () => Auth::user()->hasAnyRole(['administrador', 'super_admin']))
                    ->color('info')
                    ->modalHeading(fn (Cierre $record) => "Pagos de Liquidación - Cierre #{$record->id}")
                    ->modalContent(function (Cierre $record): View {
                        $pagos = $record->pagos()
                            ->where('pagable_type', \App\Models\Cierre::class)
                            ->with(['tipoPago', 'banco', 'user'])
                            ->orderBy('created_at', 'desc')
                            ->get();

                        return view('filament.pages.actions.ver-pagos-liquidacion', [
                            'pagos' => $pagos,
                        ]);
                    })
                    ->modalWidth(MaxWidth::FiveExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->badge(function (Cierre $record) {
                        $countPagos = $record->pagos()
                            ->where('pagable_type', \App\Models\Cierre::class)
                            ->count();

                        return $countPagos > 0 ? $countPagos : null;
                    })
                    ->badgeColor('success'),
                Action::make('liquidar_completo_manual')
                    ->label('Liquidar Cierre')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Cierre $record) => 
                        Auth::user()->can('view_costs_producto') && 
                        !$record->liquidado_completo && 
                        $record->puedeLiquidar()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Liquidar Cierre')
                    ->modalDescription('Se ha detectado que todos los pagos han sido completados. ¿Desea liquidar todas las ventas de este cierre ahora?')
                    ->action(function (Cierre $record) {
                        try {
                            if ($record->cierre === null) {
                                throw new \Exception('Debe cerrar el turno (Cerrar Turno) antes de poder liquidar completamente.');
                            }
                            VentaController::liquidar_cierre_completo($record);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al liquidar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('liquidar')
                    ->label('Agregar Pago')
                    ->icon('heroicon-o-currency-dollar')
                    ->visible(fn (Cierre $record) => 
                        Auth::user()->can('view_costs_producto') && 
                        !$record->liquidado_completo && 
                        !$record->puedeLiquidar()
                    )
                    ->color('warning')
                    ->tooltip(function (Cierre $record) {
                        $montosRestantes = $record->getMontosRestantes();

                        $faltantes = [];
                        foreach ($montosRestantes as $tipoPago => $montoRestante) {
                            $faltantes[] = "{$tipoPago}: Q".number_format($montoRestante, 2);
                        }

                        return 'Montos restantes: '.implode(', ', $faltantes).'. Puedes agregar múltiples pagos del mismo tipo.';
                    })
                    ->form([
                        Select::make('tipo_pago_id')
                            ->label('Forma de Pago')
                            ->options(function (Cierre $record) {
                                $montosRestantes = $record->getMontosRestantes();

                                // Mostrar tipos de pago que aún tienen monto restante
                                $tiposDisponibles = [];
                                foreach ($montosRestantes as $tipoPago => $montoRestante) {
                                    $tipoPagoModel = TipoPago::where('tipo_pago', $tipoPago)->first();
                                    if ($tipoPagoModel) {
                                        $tiposDisponibles[$tipoPagoModel->id] = "{$tipoPago} (Faltan: Q".number_format($montoRestante, 2).')';
                                    }
                                }

                                return $tiposDisponibles;
                            })
                            ->required()
                            ->live()
                            ->columnSpan(['sm' => 1, 'md' => 1])
                            ->searchable()
                            ->preload()
                            ->helperText(function (Cierre $record) {
                                $montosRestantes = $record->getMontosRestantes();

                                if (empty($montosRestantes)) {
                                    return 'Todos los pagos han sido completados. Al agregar el último pago, se liquidarán automáticamente todas las ventas.';
                                }

                                $faltantes = [];
                                foreach ($montosRestantes as $tipoPago => $montoRestante) {
                                    $faltantes[] = "{$tipoPago}: Q".number_format($montoRestante, 2);
                                }

                                return 'Montos restantes: '.implode(', ', $faltantes).'. Puedes agregar múltiples pagos del mismo tipo hasta completar el monto.';
                            }),
                        Select::make('banco_id')
                            ->label('Banco')
                            ->options(function () {
                                // Mostrar bancos permitidos + especiales si existen en BD
                                $permitidos = array_merge(Banco::BANCOS_DISPONIBLES, ['Efectivo', 'Nota de Crédito']);

                                return Banco::whereIn('banco', $permitidos)
                                    ->pluck('banco', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->columnSpan(['sm' => 1, 'md' => 2]),
                        TextInput::make('monto')
                            ->label('Monto')
                            ->prefix('Q')
                            ->inputMode('decimal')
                            ->rule('numeric')
                            ->minValue(1)
                            ->required()
                            ->live()
                            ->helperText(function (Get $get, Cierre $record) {
                                $tipoPagoId = $get('tipo_pago_id');
                                if (! $tipoPagoId) {
                                    return '';
                                }

                                $tipoPago = TipoPago::find($tipoPagoId);
                                if (! $tipoPago) {
                                    return '';
                                }

                                $montosRestantes = $record->getMontosRestantes();
                                $montoRestante = $montosRestantes[$tipoPago->tipo_pago] ?? 0;

                                return 'Monto restante: Q'.number_format($montoRestante, 2).' (máximo permitido)';
                            })
                            ->rules([
                                function (Get $get, Cierre $record) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                        $tipoPagoId = $get('tipo_pago_id');
                                        if (! $tipoPagoId) {
                                            return;
                                        }

                                        try {
                                            $record->validarPagoLiquidacion($tipoPagoId, floatval($value));
                                        } catch (\Exception $e) {
                                            $fail($e->getMessage());
                                        }
                                    };
                                },
                            ]),
                        TextInput::make('no_documento')
                            ->label('No. Documento o Autorización')
                            ->required(fn (Get $get) => ! in_array(optional(TipoPago::find($get('tipo_pago_id')))->tipo_pago, ['CONTADO', 'PAGO CONTRA ENTREGA']))
                                        ->rules([
                                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) {
                                                // Solo validar si el valor no está vacío
                                                if (empty($value)) {
                                                    return;
                                                }

                                                // Validar que no_documento sea único en toda la tabla de pagos
                                                if (Pago::where('no_documento', $value)->exists()) {
                                                    $fail('El número de documento ya existe en los pagos.');
                                                }
                                            },
                                        ]),
                        DatePicker::make('fecha_transaccion')
                            ->default(now())
                            ->required(),
                        FileUpload::make('imagen')
                            // ->required()
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
                            ->optimize('webp'),
                    ])
                    ->action(function (array $data, Cierre $record): void {
                        VentaController::liquidar_cierre($data, $record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCierres::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = \Filament\Facades\Filament::auth()->user();

        $query = parent::getEloquentQuery()
            ->with(['bodega', 'user'])
            ->orderByDesc('apertura');

        if ($user->hasAnyRole(['administrador', 'super_admin'])) {
            return $query;
        }

        if ($user && $user->bodegas()->exists()) {
            $bodegaIds = $user->bodegas->pluck('id')->toArray();

            return $query
                ->whereIn('bodega_id', $bodegaIds)
                ->where('user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }
}
