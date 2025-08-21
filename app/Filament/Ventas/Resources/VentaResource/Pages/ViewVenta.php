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
                ->modalWidth(MaxWidth::FiveExtraLarge)
                ->slideOver()
                ->color('orange')
                ->stickyModalHeader()
                ->modalSubmitAction(false),
            Action::make('factura')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn ($record) => auth()->user()->can('factura', $record))
                ->modalContent(fn (Venta $record): View => view(
                    'filament.pages.actions.iframe',
                    [
                        'record' => $record,
                        'title' => 'Factura Venta #'.$record->id,
                        'route' => route('pdf.factura.venta', ['id' => $record->id]),
                        'open' => true,
                    ],
                ))
                ->modalWidth(MaxWidth::FiveExtraLarge)
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
