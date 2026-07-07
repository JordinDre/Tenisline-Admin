<?php

namespace App\Filament\Ventas\Resources\VentaResource\Pages;

use App\Filament\Ventas\Resources\VentaResource;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Kenepa\ResourceLock\Resources\Pages\Concerns\UsesResourceLock;

class EditVenta extends EditRecord
{
    use UsesResourceLock;

    protected static string $resource = VentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    protected function beforeSave(): void
    {
        try {
            // Validar que no se mezclen promociones (segundo_par y apertura_20 u otras)
            $detallesData = $this->data['detalles'] ?? [];
            $tieneSegundoPar = collect($detallesData)->contains(fn ($d) => ($d['tipo_precio'] ?? null) === 'segundo_par');
            $tieneOtrasPromos = collect($detallesData)->contains(fn ($d) => in_array($d['tipo_precio'] ?? null, ['oferta', 'liquidacion', 'descuento', 'apertura_20'], true));

            if ($tieneSegundoPar && $tieneOtrasPromos) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'total' => 'No se puede combinar el descuento de "Segundo Par" con otras promociones o descuentos en la misma venta.',
                ]);
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->warning()
                ->color('warning')
                ->title('Advertencia')
                ->body($e->getMessage())
                ->persistent()
                ->send();
            $this->halt();
        }
    }
}
