<?php

namespace App\Filament\Ventas\Resources\VentaResource\Pages;

use App\Filament\Ventas\Resources\VentaResource;
use App\Models\Venta;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\View\View;

class ViewVenta extends ViewRecord
{
    protected static string $resource = VentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('venta')
                ->icon('heroicon-o-document-arrow-down')
                ->modalContent(fn (Venta $record): View => view(
                    'filament.pages.actions.iframe',
                    [
                        'record' => $record,
                        'title' => 'Venta #'.$record->id,
                        'route' => route('pdf.venta', ['id' => $record->id]),
                        'open' => true,
                    ],
                ))
                ->modalWidth(MaxWidth::SevenExtraLarge)
                ->slideOver()
                ->color('orange')
                ->stickyModalHeader()
                ->modalSubmitAction(false),
            Action::make('factura')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn ($record) => $record && auth()->user()->can('factura', $record) && ! $record->debeOcultarFactura())
                ->disabled(fn ($record) => $record && $record->debeOcultarFactura())
                ->modalContent(fn (Venta $record): View => view(
                    'filament.pages.actions.iframe',
                    [
                        'record' => $record,
                        'title' => 'Factura Venta #'.$record->id,
                        'route' => route('pdf.factura.venta', ['id' => $record->id]),
                        'open' => true,
                    ],
                ))
                ->modalWidth(MaxWidth::SevenExtraLarge)
                ->color('success')
                ->slideOver()
                ->stickyModalHeader()
                ->modalSubmitAction(false),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Información General')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('ID de Venta')
                                    ->icon('heroicon-o-identification'),
                                TextEntry::make('estado')
                                    ->label('Estado')
                                    ->badge()
                                    ->icon('heroicon-o-flag'),
                                TextEntry::make('created_at')
                                    ->label('Fecha de Creación')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->icon('heroicon-o-calendar'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('cliente.name')
                                    ->label('Cliente')
                                    ->icon('heroicon-o-user'),
                                TextEntry::make('asesor.name')
                                    ->label('Vendedor')
                                    ->icon('heroicon-o-user-group'),
                                TextEntry::make('bodega.bodega')
                                    ->label('Bodega')
                                    ->icon('heroicon-o-building-office'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')

                                    ->icon('heroicon-o-currency-dollar'),
                                TextEntry::make('envio')
                                    ->label('Envío')

                                    ->icon('heroicon-o-truck'),
                                TextEntry::make('total')
                                    ->label('Total')

                                    ->icon('heroicon-o-currency-dollar')
                                    ->weight('bold'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('tracking')
                                    ->label('Código de Tracking')
                                    ->icon('heroicon-o-map-pin')
                                    ->copyable()
                                    ->placeholder('Sin tracking')
                                    ->visible(fn ($record) => $record->tracking !== null),
                                TextEntry::make('paquetes')
                                    ->label('Cantidad de Paquetes')
                                    ->icon('heroicon-o-cube')
                                    ->numeric()
                                    ->placeholder('N/A')
                                    ->visible(fn ($record) => $record->paquetes !== null),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Detalles de Productos')
                    ->schema([
                        RepeatableEntry::make('detalles')
                            ->schema([
                                Group::make([
                                    Grid::make(12)
                                        ->schema([
                                            TextEntry::make('producto.imagenes')
                                                ->label('Imágen')
                                                ->formatStateUsing(function ($record): View {
                                                    return view('filament.tables.columns.image', [
                                                        'url' => config('filesystems.disks.s3.url').$record->producto->imagenes[0],
                                                        'alt' => $record->producto->descripcion,
                                                    ]);
                                                }),
                                            Group::make([
                                                TextEntry::make('producto.codigo')
                                                    ->label('Código')
                                                    ->weight('bold'),
                                                TextEntry::make('producto.descripcion')
                                                    ->label('Descripción')
                                                    ->weight('bold')
                                                    ->color('primary'),
                                                TextEntry::make('producto.marca.marca')
                                                    ->label('Marca')
                                                    ->color('gray'),
                                                TextEntry::make('producto.talla')
                                                    ->label('Talla')
                                                    ->color('info')
                                                    ->placeholder('N/A')
                                                    ->visible(fn ($record) => ! empty($record->producto->talla)),
                                            ])
                                                ->columnSpan(4),
                                            Group::make([
                                                TextEntry::make('cantidad')
                                                    ->label('Cantidad')
                                                    ->weight('bold'),
                                                TextEntry::make('precio')
                                                    ->label('Precio Unitario')

                                                    ->weight('bold'),
                                                TextEntry::make('subtotal')
                                                    ->label('Subtotal')

                                                    ->weight('bold')
                                                    ->color('success'),
                                            ])
                                                ->columnSpan(3),
                                            Group::make([
                                                TextEntry::make('devuelto')
                                                    ->label('Cantidad Devuelta')
                                                    ->numeric()
                                                    ->icon('heroicon-o-arrow-uturn-left')
                                                    ->iconColor(fn ($record) => $record->devuelto > 0 ? 'warning' : 'gray')
                                                    ->color(fn ($record) => $record->devuelto > 0 ? 'warning' : 'gray')
                                                    ->weight('bold'),
                                                TextEntry::make('devuelto_mal')
                                                    ->label('Devuelto en Mal Estado')
                                                    ->numeric()
                                                    ->icon('heroicon-o-exclamation-triangle')
                                                    ->iconColor(fn ($record) => $record->devuelto_mal > 0 ? 'danger' : 'gray')
                                                    ->color(fn ($record) => $record->devuelto_mal > 0 ? 'danger' : 'gray')
                                                    ->weight('bold'),
                                            ])
                                                ->columnSpan(3),
                                        ]),
                                    Group::make([
                                        TextEntry::make('estado_producto')
                                            ->label('Estado del Producto')
                                            ->getStateUsing(function ($record) {
                                                if ($record->devuelto > 0) {
                                                    if ($record->devuelto == $record->cantidad) {
                                                        return 'Completamente Devuelto';
                                                    } else {
                                                        return 'Parcialmente Devuelto';
                                                    }
                                                }

                                                return 'Sin Devolución';
                                            })
                                            ->badge()
                                            ->color(function ($record) {
                                                if ($record->devuelto > 0) {
                                                    if ($record->devuelto == $record->cantidad) {
                                                        return 'danger';
                                                    } else {
                                                        return 'warning';
                                                    }
                                                }

                                                return 'success';
                                            })
                                            ->icon(function ($record) {
                                                if ($record->devuelto > 0) {
                                                    if ($record->devuelto == $record->cantidad) {
                                                        return 'heroicon-o-x-circle';
                                                    } else {
                                                        return 'heroicon-o-exclamation-triangle';
                                                    }
                                                }

                                                return 'heroicon-o-check-circle';
                                            }),
                                    ])
                                        ->columnSpanFull()
                                        ->extraAttributes(['class' => 'text-center']),
                                ])
                                    ->extraAttributes(['class' => 'border border-gray-200 rounded-lg p-4 mb-4 bg-gray-50'])
                                    ->columnSpanFull(),
                            ])
                            ->contained(false)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Información de Pagos')
                    ->schema([
                        RepeatableEntry::make('pagos')
                            ->schema([
                                Group::make([
                                    Grid::make(4)
                                        ->schema([
                                            TextEntry::make('tipoPago.tipo_pago')
                                                ->label('Forma de Pago')
                                                ->badge()
                                                ->color('info')
                                                ->icon('heroicon-o-credit-card')
                                                ->weight('bold'),
                                            TextEntry::make('monto')
                                                ->label('Monto')
                                                ->prefix('Q')
                                                ->numeric(
                                                    decimalPlaces: 2,
                                                    decimalSeparator: '.',
                                                    thousandsSeparator: ','
                                                )
                                                ->icon('heroicon-o-currency-dollar')
                                                ->weight('bold')
                                                ->color('success'),
                                            TextEntry::make('total')
                                                ->label('Total')
                                                ->prefix('Q')
                                                ->numeric(
                                                    decimalPlaces: 2,
                                                    decimalSeparator: '.',
                                                    thousandsSeparator: ','
                                                )
                                                ->icon('heroicon-o-banknotes')
                                                ->weight('bold')
                                                ->color('success'),
                                            TextEntry::make('fecha_transaccion')
                                                ->label('Fecha Transacción')
                                                ->date('d/m/Y')
                                                ->icon('heroicon-o-calendar'),
                                        ]),
                                    Grid::make(4)
                                        ->schema([
                                            TextEntry::make('no_documento')
                                                ->label('No. Documento/Autorización')
                                                ->icon('heroicon-o-document-text')
                                                ->copyable()
                                                ->placeholder('N/A'),
                                            TextEntry::make('banco.banco')
                                                ->label('Banco')
                                                ->icon('heroicon-o-building-library')
                                                ->placeholder('N/A'),
                                            TextEntry::make('no_autorizacion')
                                                ->label('No. Autorización')
                                                ->icon('heroicon-o-key')
                                                ->copyable()
                                                ->placeholder('N/A')
                                                ->visible(fn ($record) => $record->no_autorizacion !== null),
                                            TextEntry::make('cuotas')
                                                ->label('Cuotas')
                                                ->icon('heroicon-o-calculator')
                                                ->placeholder('N/A')
                                                ->visible(fn ($record) => $record->cuotas !== null),
                                        ]),
                                    Grid::make(3)
                                        ->schema([
                                            TextEntry::make('no_auditoria')
                                                ->label('No. Auditoría')
                                                ->icon('heroicon-o-clipboard-document-check')
                                                ->copyable()
                                                ->placeholder('N/A')
                                                ->visible(fn ($record) => $record->no_auditoria !== null),
                                            TextEntry::make('afiliacion')
                                                ->label('Afiliación')
                                                ->icon('heroicon-o-identification')
                                                ->copyable()
                                                ->placeholder('N/A')
                                                ->visible(fn ($record) => $record->afiliacion !== null),
                                            TextEntry::make('nombre_cuenta')
                                                ->label('Nombre Cuenta')
                                                ->icon('heroicon-o-user')
                                                ->placeholder('N/A')
                                                ->visible(fn ($record) => $record->nombre_cuenta !== null),
                                        ]),
                                ])
                                    ->extraAttributes(['class' => 'border border-green-200 rounded-lg p-4 mb-4 bg-green-50'])
                                    ->columnSpanFull(),
                            ])
                            ->contained(false)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->icon('heroicon-o-credit-card'),

                Section::make('Información Adicional')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('tipo_envio')
                                    ->label('Tipo de Envío')
                                    ->icon('heroicon-o-truck'),
                                TextEntry::make('observaciones')
                                    ->label('Observaciones')
                                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                    ->columnSpan(2),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('fecha_devuelta')
                                    ->label('Fecha de Devolución')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->icon('heroicon-o-arrow-uturn-left')
                                    ->visible(fn ($record) => $record->fecha_devuelta !== null),
                                TextEntry::make('motivo')
                                    ->label('Motivo de Devolución')
                                    ->icon('heroicon-o-document-text')
                                    ->visible(fn ($record) => $record->motivo !== null)
                                    ->columnSpan(2),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }
}
