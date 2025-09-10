<?php

namespace App\Filament\Inventario\Resources\ProductoResource\Pages;

use App\Filament\Inventario\Resources\ProductoResource;
use App\Models\Marca;
use App\Models\Observacion;
use App\Models\Presentacion;
use App\Models\Producto;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class EditProducto extends EditRecord
{
    protected static string $resource = ProductoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\Action::make('Desactivar')
                ->visible(fn ($record) => auth()->user()->can('delete', $record))
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->modalWidth(MaxWidth::ThreeExtraLarge)
                ->form([
                    Textarea::make('observacion')
                        ->label('Observación')
                        ->minLength(5)
                        ->required(),
                ])
                ->action(function (array $data, Producto $record): void {
                    $observacion = new Observacion;
                    $observacion->observacion = $data['observacion'];
                    $observacion->user_id = auth()->user()->id;
                    $record->observaciones()->save($observacion);
                    $record->delete();
                    Notification::make()
                        ->title('Producto desactivado')
                        ->color('success')
                        ->success()
                        ->send();
                })
                ->modalContent(fn (Producto $record): View => view(
                    'filament.pages.actions.observaciones',
                    ['record' => $record],
                ))
                ->label('Desactivar'),
            Actions\RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $slug = Str::slug(
            ($record->id ?? '').'-'.
                ($record->codigo ?? '').'-'.
                ($record->descripcion ?? '').'-'.
                (Marca::find($record->marca_id)->marca ?? '')/* .'-'.
                (Presentacion::find($record->presentacion_id)->presentacion ?? '') */
        );
        $record->update(['slug' => $slug]);
    }
}
