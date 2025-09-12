<?php

namespace App\Filament\Ventas\Resources\VentaResource\Pages;

use App\Enums\EstadoVentaStatus;
use App\Filament\Ventas\Resources\VentaResource;
use App\Models\CierreDia;
use App\Models\Venta;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListVentas extends ListRecords
{
    protected static string $resource = VentaResource::class;

    /* public function getTabs(): array
    {
        return collect(EstadoVentaStatus::cases())->mapWithKeys(function (EstadoVentaStatus $estado) {
            return [
                $estado->getLabel() => Tab::make()
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', $estado->value))
                    ->badge(Venta::query()->where('estado', $estado->value)->count())
                    ->badgeColor($estado->getColor()),
            ];
        })->toArray();
    } */

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->visible(function () {
                return \App\Models\Cierre::whereNotNull('apertura')
                    ->whereNull('cierre')
                    ->exists();
            }),

            /* Actions\Action::make('liquidarVentas')
                ->label('Liquidar Venta del Día')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Liquidar Ventas del Día')
                ->modalDescription('¿Estás seguro de que deseas liquidar las ventas del día actual? Esta acción marcará el final del día de ventas actual.')
                ->modalSubmitActionLabel('Liquidar')
                ->action(function () {
                    $today = Carbon::today();

                    $cierreDia = CierreDia::create([
                        'fecha_cierre' => $today->toDateString(),
                        'usuario_id' => auth()->user()->id,
                    ]);

                    Venta::whereDate('created_at', $today)
                        ->whereNull('cierre_dia_id')
                        ->update(['cierre_dia_id' => $cierreDia->id,]);

                    Cache::put('dia_liquidado', true, now()->addDay());

                    Notification::make()
                        ->title('Ventas del día liquidadas')
                        ->color('success')
                        ->success()
                        ->send();
                }), */
        ];
    }
}
