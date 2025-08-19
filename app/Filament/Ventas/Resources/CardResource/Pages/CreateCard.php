<?php

namespace App\Filament\Ventas\Resources\CardResource\Pages;

use App\Filament\Ventas\Resources\CardResource;
use App\Models\Venta;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateCard extends CreateRecord
{
    protected static string $resource = CardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $ventasCount = Venta::where('cliente_id', $data['cliente_id'])->count();

        if ($ventasCount === 0) {
            Notification::make()
                ->title('Error')
                ->body('Este cliente no tiene ventas registradas.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'cliente_id' => 'Este cliente no tiene ventas registradas. No se puede crear la tarjeta.',
            ]);
        }

        return $data;
    }
}
