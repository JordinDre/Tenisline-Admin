<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'administrador',
            'proveedor',
            'cliente',
            'vendedor',

           /*  'administrador',
            'facturador',
            'supervisor preventa',
            'supervisor venta directa',
            'supervisor telemarketing',
            'asesor preventa',
            'asesor venta directa',
            'asesor telemarketing',
            'empaquetador',
            'creditos',
            'diseñador',
            'bodeguero',
            'piloto',
            'cliente',
            'proveedor',
            'recolector', */
        ];

        User::factory(100)->create()->each(function ($user) use ($roles) {
            $randomRole = Role::where('name', $roles[array_rand($roles)])->first();
            $user->syncRoles($randomRole);
        });
    }
}
