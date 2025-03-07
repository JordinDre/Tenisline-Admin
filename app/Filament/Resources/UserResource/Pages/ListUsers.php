<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
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

        $userCountAll = User::count();
        $tabs['Todos'] = Tab::make()
            ->badge($userCountAll)
            ->modifyQueryUsing(fn (Builder $query) => $query);

        foreach ($roles as $role) {
            $userCount = User::role($role->name)->count();
            $tabs[$role->name] = Tab::make()
                ->badge($userCount)
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
