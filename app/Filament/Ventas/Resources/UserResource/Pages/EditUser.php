<?php

namespace App\Filament\Ventas\Resources\UserResource\Pages;

use App\Filament\Ventas\Resources\UserResource;
use App\Models\Observacion;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Desactivar')
                ->visible(fn ($record) => auth()->user()->can('delete', $record))
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->modalWidth(MaxWidth::SevenExtraLarge)
                ->form([
                    Textarea::make('observacion')
                        ->label('Observación')
                        ->minLength(5)
                        ->required(),
                ])
                ->action(function (array $data, User $record): void {
                    $observacion = new Observacion;
                    $observacion->observacion = $data['observacion'];
                    $observacion->user_id = auth()->user()->id;
                    $record->observaciones()->save($observacion);
                    $record->delete();
                    Notification::make()
                        ->title('Usuario desactivado')
                        ->color('success')
                        ->success()
                        ->send();
                })
                ->modalContent(fn (User $record): View => view(
                    'filament.pages.actions.observaciones',
                    ['record' => $record],
                ))
                ->label('Desactivar'),
        ];
    }
}
