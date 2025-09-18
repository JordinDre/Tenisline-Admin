<?php

namespace App\Filament\Ventas\Resources\VentaResource\Pages;

use App\Enums\EstadoVentaStatus;
use App\Filament\Ventas\Resources\VentaResource;
use App\Models\Cierre;
use App\Models\CierreDia;
use App\Models\Venta;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ListVentas extends ListRecords
{
    protected static string $resource = VentaResource::class;

    public function mount(): void
    {
        parent::mount();
        
        // Verificar si el usuario actual tiene al menos un cierre abierto
        $userId = Auth::user()?->id;
        $tieneCierreAbierto = $userId ? Cierre::where('user_id', $userId)
            ->whereNull('cierre')
            ->exists() : false;
        
        // Mostrar notificación si no tiene cierre abierto
        if (!$tieneCierreAbierto) {
            Notification::make()
                ->warning()
                ->title('Sin cierre abierto')
                ->body('No tienes un cierre abierto. Debes aperturar un cierre antes de poder crear ventas.')
                ->persistent()
                ->send();
        }
    }

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
        $actions = [];
        
        // Verificar si el usuario actual tiene al menos un cierre abierto
        $userId = Auth::user()?->id;
        $tieneCierreAbierto = $userId ? Cierre::where('user_id', $userId)
            ->whereNull('cierre')
            ->exists() : false;
        
        // Solo mostrar el botón de crear si el usuario tiene un cierre abierto
        if ($tieneCierreAbierto) {
            $actions[] = Actions\CreateAction::make();
        }

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
        
        return $actions;
    }
}
