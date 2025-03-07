<?php

namespace App\Filament\Inventario\Resources\CompraResource\Pages;

use App\Filament\Inventario\Resources\CompraResource;
use Filament\Actions;
use Filament\Resources\Pages\ContentTabPosition;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Kenepa\ResourceLock\Resources\Pages\Concerns\UsesResourceLock;

class EditCompra extends EditRecord
{
    use UsesResourceLock;

    protected static string $resource = CompraResource::class;

    /* public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabIcon(): ?string
    {
        return 'heroicon-m-cog';
    }

    public function getContentTabPosition(): ?ContentTabPosition
    {
        return ContentTabPosition::After;
    } */

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $sum = $record->detalles->sum(fn ($detalle) => $detalle->cantidad * ($detalle->precio + $detalle->envio + $detalle->envase));
        $total = $data['total'];
        if (round($sum, 2) == round($total, 2)) {
            $data['estado'] = 'completada';
        } else {
            $data['estado'] = 'creada';
        }
        $record->update($data);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()->label('Desactivar'),
            Actions\RestoreAction::make(),
        ];
    }
}
