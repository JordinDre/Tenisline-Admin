<?php

namespace App\Filament\Ventas\Resources\VentaResource\Pages;

use App\Filament\Ventas\Resources\VentaResource;
use App\Models\Venta;
use Filament\Actions;
use Filament\Actions\Action;
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
}
