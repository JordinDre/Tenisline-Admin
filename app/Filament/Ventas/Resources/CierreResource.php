<?php

namespace App\Filament\Ventas\Resources;

use Closure;
use Filament\Forms;
use App\Models\Pago;
use Filament\Tables;
use App\Models\Banco;
use App\Models\Cierre;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\TipoPago;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Contracts\View\View;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('Cerrar')
                    ->action(function (Cierre $record) {
                        $record->update([
                            'cierre' => now(),
                        ]);
                    })
                    ->visible(fn (Cierre $record) => $record->user_id === Auth::id() && $record->cierre === null)
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check'),
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
                Action::make('liquidar')
                    ->label('Agregar Pago')
                    ->icon('heroicon-o-currency-dollar')
                    ->visible(Auth::user()->can('view_costs_producto'))
                    ->color('warning')
                    ->disabled(function (Cierre $record) {
                        // Solo deshabilitar si ya está completamente liquidado
                        return $record->liquidado_completo;
                    })
                    ->tooltip(function (Cierre $record) {
                        if ($record->liquidado_completo) {
                            return 'El cierre ya está completamente liquidado';
                        }
                        
                        $montosRestantes = $record->getMontosRestantes();
                        
                        if (empty($montosRestantes)) {
                            return 'Todos los pagos han sido completados. El cierre se liquidará automáticamente.';
                        }
                        
                        $faltantes = [];
                        foreach ($montosRestantes as $tipoPago => $montoRestante) {
                            $faltantes[] = "{$tipoPago}: Q" . number_format($montoRestante, 2);
                        }
                        
                        return "Montos restantes: " . implode(', ', $faltantes) . ". Puedes agregar múltiples pagos del mismo tipo.";
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
                                        $tiposDisponibles[$tipoPagoModel->id] = "{$tipoPago} (Faltan: Q" . number_format($montoRestante, 2) . ")";
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
                                    return "Todos los pagos han sido completados. Al agregar el último pago, se liquidarán automáticamente todas las ventas.";
                                }
                                
                                $faltantes = [];
                                foreach ($montosRestantes as $tipoPago => $montoRestante) {
                                    $faltantes[] = "{$tipoPago}: Q" . number_format($montoRestante, 2);
                                }
                                
                                return "Montos restantes: " . implode(', ', $faltantes) . ". Puedes agregar múltiples pagos del mismo tipo hasta completar el monto.";
                            }),
                        Select::make('banco_id')
                            ->label('Banco')
                            ->options(fn() => Banco::whereIn('banco', Banco::BANCOS_DISPONIBLES)->pluck('banco', 'id')->toArray())
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
                                if (!$tipoPagoId) return '';
                                
                                $tipoPago = TipoPago::find($tipoPagoId);
                                if (!$tipoPago) return '';
                                
                                $montosRestantes = $record->getMontosRestantes();
                                $montoRestante = $montosRestantes[$tipoPago->tipo_pago] ?? 0;
                                
                                return "Monto restante: Q" . number_format($montoRestante, 2) . " (máximo permitido)";
                            })
                            ->rules([
                                function (Get $get, Cierre $record) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                        $tipoPagoId = $get('tipo_pago_id');
                                        if (!$tipoPagoId) return;
                                        
                                        try {
                                            $record->validarPagoLiquidacion($tipoPagoId, floatval($value));
                                        } catch (\Exception $e) {
                                            $fail($e->getMessage());
                                        }
                                    };
                                }
                            ]),
                        TextInput::make('no_documento')
                            ->label('No. Documento o Autorización'),
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
                        })
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
