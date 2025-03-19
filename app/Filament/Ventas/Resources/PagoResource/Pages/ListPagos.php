<?php

namespace App\Filament\Ventas\Resources\PagoResource\Pages;

use App\Filament\Ventas\Resources\PagoResource;
use App\Http\Controllers\UserController;
use App\Models\Pago;
use App\Models\TipoPago;
use App\Models\User;
use Closure;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListPagos extends ListRecords
{
    protected static string $resource = PagoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->closeModalByClickingAway(false)->label('Crear Pago Único'),
            Action::make('credito')
                ->label('Crear Pago Crédito')
                ->closeModalByClickingAway(false)
                ->color('success')
                ->visible(auth()->user()->can('credit_pago'))
                ->form([
                    Select::make('cliente_id')
                        ->label('Cliente')
                        /*  ->options(
                            User::where(function ($query) {
                                $query->whereHas('creditosOrdenesPendientes')
                                    ->orWhereHas('creditosVentasPendientes');
                            })
                                ->role('cliente')
                                ->get()
                                ->mapWithKeys(function ($user) {
                                    $totalDeuda = $user->creditosOrdenesPendientes->sum(function ($orden) {
                                        return $orden->total - $orden->pagos->sum('monto');
                                    }) + $user->creditosVentasPendientes->sum(function ($venta) {
                                        return $venta->total - $venta->pagos->sum('monto');
                                    });

                                    $label = collect([
                                        $user->id,
                                        $user->nit ?: 'CF',
                                        $user->name,
                                        $user->razon_social,
                                        $totalDeuda > 0 ? "(Total deuda: Q{$totalDeuda})" : '(Sin deuda)',
                                    ])->filter()->join(' - ');

                                    return [$user->id => $label];
                                })
                                ->toArray()
                        ) */
                        ->optionsLimit(12)
                        ->required()
                        ->searchable(),
                    Select::make('tipo_pago_id')
                        ->label('Forma de Pago')
                        ->relationship('tipoPago', 'tipo_pago', fn (Builder $query) => $query->whereIn('tipo_pago', TipoPago::FORMAS_PAGO))
                        ->required()
                        ->live()
                        ->searchable()
                        ->preload(),
                    Grid::make(2)
                        ->schema([
                            TextInput::make('monto')
                                ->label('Monto')
                                ->prefix('Q')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, Get $get) {
                                    $set('total', $get('monto'));
                                })
                                ->inputMode('decimal')
                                ->rule('numeric')
                                ->minValue(1)
                                ->required(),
                            Hidden::make('total'),
                            Hidden::make('user_id')
                                ->default(auth()->user()->id),
                            TextInput::make('no_documento')
                                ->rules([
                                    fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                        if (
                                            Pago::where('banco_id', $get('banco_id'))
                                                ->where('fecha_transaccion', $get('fecha_transaccion'))
                                                ->where('no_documento', $value)
                                                ->exists()
                                        ) {
                                            $fail('La combinación de Banco, Fecha de Transacción y No. Documento ya existe en los pagos.');
                                        }
                                    },
                                ])
                                ->label('No. Documento')
                                ->required(),
                            TextInput::make('no_autorizacion')
                                ->label('No. Autorización')
                                ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                                ->required(),
                            TextInput::make('no_auditoria')
                                ->label('No. Auditoría')
                                ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                                ->required(),
                            TextInput::make('afiliacion')
                                ->label('Afiliación')
                                ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                                ->required(),
                            Select::make('cuotas')
                                ->options([1 => 1, 3 => 3, 6 => 6, 9 => 9, 12 => 12])
                                ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                                ->required(),
                            TextInput::make('nombre_cuenta')
                                ->visible(fn (Get $get) => $get('tipo_pago_id') == 6 && $get('tipo_pago_id') != null)
                                ->required(),
                            Select::make('banco_id')
                                ->label('Banco')
                                ->required()
                                ->relationship('banco', 'banco')
                                ->searchable()
                                ->preload(),
                            DatePicker::make('fecha_transaccion')
                                ->default(now())
                                ->required(),
                        ]),
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
                        ->openable()
                        ->columnSpanFull()
                        ->optimize('webp')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        DB::transaction(function () use ($data) {
                            $cliente = User::find($data['cliente_id']);
                            $monto = $data['monto'];

                            // Calcular el total pendiente
                            $ordenesPendientes = $cliente->creditosOrdenesPendientes;
                            $ventasPendientes = $cliente->creditosVentasPendientes;
                            $pendientes = $ordenesPendientes->merge($ventasPendientes);
                            $totalPendiente = $pendientes->sum(function ($pendiente) {
                                return $pendiente->total - $pendiente->pagos->sum('monto');
                            });

                            // Validar que el monto no exceda el total pendiente
                            if ($monto > $totalPendiente) {
                                throw new \Exception("El monto ingresado ({$monto}) supera el total pendiente ({$totalPendiente}).");
                            }

                            // Restar saldo al cliente
                            UserController::restarSaldo($cliente, $monto);

                            // Procesar pagos pendientes
                            $pendientes = $pendientes->sortBy('created_at');
                            foreach ($pendientes as $pendiente) {
                                $saldoPendiente = $pendiente->total - $pendiente->pagos->sum('monto');

                                if ($monto <= 0) {
                                    break;
                                }

                                $montoAsignar = min($monto, $saldoPendiente);
                                $monto -= $montoAsignar;

                                Pago::create([
                                    'pagable_type' => get_class($pendiente),
                                    'pagable_id' => $pendiente->id,
                                    'monto' => $montoAsignar,
                                    'total' => $montoAsignar,
                                    'tipo_pago_id' => $data['tipo_pago_id'],
                                    'banco_id' => $data['banco_id'],
                                    'fecha_transaccion' => $data['fecha_transaccion'],
                                    'no_documento' => $data['no_documento'],
                                    'no_autorizacion' => $data['no_autorizacion'] ?? null,
                                    'no_auditoria' => $data['no_auditoria'] ?? null,
                                    'afiliacion' => $data['afiliacion'] ?? null,
                                    'cuotas' => $data['cuotas'] ?? null,
                                    'nombre_cuenta' => $data['nombre_cuenta'] ?? null,
                                    'imagen' => $data['imagen'] ?? null,
                                    'user_id' => auth()->user()->id,
                                ]);
                            }
                        });

                        Notification::make()
                            ->title('Pago Crédito agregado correctamente')
                            ->color('success')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al realizar el pago crédito')
                            ->body($e->getMessage())
                            ->danger()
                            ->color('danger')
                            ->send();
                    }
                }),
        ];
    }
}
