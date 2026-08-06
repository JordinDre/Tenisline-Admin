<?php

namespace App\Filament\Ventas\Resources\VentaResource\Pages;

use App\Filament\Traits\ManageDiscountLogic;
use App\Filament\Ventas\Resources\VentaResource;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Kenepa\ResourceLock\Resources\Pages\Concerns\UsesResourceLock;

class EditVenta extends EditRecord
{
    use UsesResourceLock;
    use ManageDiscountLogic;

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
            $productoIds = collect($detallesData)->pluck('producto_id')->filter()->unique();
            $productos = \App\Models\Producto::whereIn('id', $productoIds)->get()->keyBy('id');

            $esMarchamoRojoLiq = function ($d) use ($productos) {
                if (($d['tipo_precio'] ?? null) !== 'liquidacion') {
                    return false;
                }
                $p = $productos->get($d['producto_id'] ?? null);
                return $p && strtolower($p->marchamo ?? '') === 'rojo';
            };

            $tieneSegundoPar = collect($detallesData)->contains(fn ($d) => in_array($d['tipo_precio'] ?? null, ['segundo_par', 'segundo_par_marchamo'], true));
            $tieneOtrasPromos = collect($detallesData)->contains(function ($d) use ($esMarchamoRojoLiq) {
                if ($esMarchamoRojoLiq($d)) {
                    return false;
                }
                return in_array($d['tipo_precio'] ?? null, ['oferta', 'liquidacion', 'descuento', 'apertura_20'], true);
            });

            if ($tieneSegundoPar && $tieneOtrasPromos) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'total' => 'No se puede combinar el descuento de "Segundo Par" con otras promociones o descuentos en la misma venta.',
                ]);
            }

            $tieneSegundoParClasico = collect($detallesData)->contains(fn ($d) => ($d['tipo_precio'] ?? null) === 'segundo_par');
            $tieneSegundoParMarchamo = collect($detallesData)->contains(fn ($d) => ($d['tipo_precio'] ?? null) === 'segundo_par_marchamo');

            if ($tieneSegundoParClasico && $tieneSegundoParMarchamo) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'total' => 'No se puede combinar el "Segundo Par" clásico con "Segundo Par 50% (Marchamo)" en la misma venta.',
                ]);
            }

            // Re-validar cada línea con "Segundo Par 50% (Marchamo)" contra las reglas de grupos de color y precio.
            foreach ($detallesData as $detalle) {
                if (($detalle['tipo_precio'] ?? null) !== 'segundo_par_marchamo') {
                    continue;
                }

                $productoDetalle = $productos->get($detalle['producto_id'] ?? null);
                if (! $productoDetalle) {
                    continue;
                }

                $resultado = $this->evaluarSegundoParMarchamo($productoDetalle, $detallesData);

                if (! $resultado['ok']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'total' => $resultado['motivo'] ?? 'La promoción "Segundo Par 50% (Marchamo)" no es válida para esta combinación de productos.',
                    ]);
                }
            }

            // Validar restricción de Marchamo Rojo: requiere un par a precio normal por cada par en liquidación.
            // Si también hay segundo_par o segundo_par_marchamo, la suma no puede exceder el total de pares a precio normal.
            $cantNormal = collect($detallesData)
                ->filter(fn ($d) => ($d['tipo_precio'] ?? null) === 'normal')
                ->sum(fn ($d) => (int) ($d['cantidad'] ?? 0));

            $cantSegundoPar = collect($detallesData)
                ->filter(fn ($d) => ($d['tipo_precio'] ?? null) === 'segundo_par')
                ->sum(fn ($d) => (int) ($d['cantidad'] ?? 0));

            $cantSegundoParMarchamo = collect($detallesData)
                ->filter(fn ($d) => ($d['tipo_precio'] ?? null) === 'segundo_par_marchamo')
                ->sum(fn ($d) => (int) ($d['cantidad'] ?? 0));

            $cantMarchamoRojoLiq = collect($detallesData)
                ->filter(fn ($d) => $esMarchamoRojoLiq($d))
                ->sum(fn ($d) => (int) ($d['cantidad'] ?? 0));

            if ($cantMarchamoRojoLiq > 0 || $cantSegundoParMarchamo > 0) {
                if ($cantSegundoPar + $cantMarchamoRojoLiq + $cantSegundoParMarchamo > $cantNormal) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'total' => 'Para aplicar la liquidación de Marchamo Rojo a Q100, el "Segundo Par" o "Segundo Par 50% (Marchamo)", debe llevar un par a precio normal por cada par en promoción. Actualmente no tiene suficientes pares a precio normal.',
                    ]);
                }
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
