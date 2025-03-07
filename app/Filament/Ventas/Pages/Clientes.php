<?php

namespace App\Filament\Ventas\Pages;

use App\Filament\Ventas\Resources\ClientesPageResource\Widgets\Cartera;
use Filament\Pages\Page;

class Clientes extends Page
{
    protected static ?string $navigationIcon = 'tabler-users-plus';

    protected static string $view = 'filament.ventas.pages.clientes';

    protected function getHeaderWidgets(): array
    {
        return [
            Cartera::class,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_any_clients');
    }

    /* protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
            ->url(route('posts.edit', ['post' => $this->post])),
            Action::make('delete')
                ->requiresConfirmation()
                ->action(fn() => $this->post->delete()),
        ];
    } */

    /* public $defaultAction = 'onboarding';

    public function onboardingAction(): Action
    {
        return Action::make('onboarding')
            ->modalHeading('Welcome')
            ->visible(fn(): bool => ! auth()->user()->isOnBoarded());
    } */
}
