<?php

namespace App\Filament\Ventas\Resources\UserResource\Pages;

use App\Filament\Ventas\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getTabs(): array
    {
        $tabs = [];

        $roles = Role::all();

        $tabs['Todos'] = Tab::make()
            ->modifyQueryUsing(fn (Builder $query) => $query);

        foreach ($roles as $role) {
            $tabs[$role->name] = Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->role($role->name));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
