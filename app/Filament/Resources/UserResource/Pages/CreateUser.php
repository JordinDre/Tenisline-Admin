<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function beforeSave(): void
    {
        $rolAsesorPreventa = 9;
        $rolAsesorTelemarketing = 11;

        $roles = $this->data['roles'] ?? [];
        if (in_array($rolAsesorPreventa, $roles) && in_array($rolAsesorTelemarketing, $roles)) {
            Notification::make()
                ->title('Advertencia: No puedes tener asignados "Asesor Preventa" y "Asesor Telemarketing" al mismo tiempo.')
                ->warning()
                ->color('warning')
                ->send();
            $this->halt();
        }
    }
}
