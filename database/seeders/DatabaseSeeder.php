<?php

namespace Database\Seeders;

use App\Models\Bodega;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(TipoPagoSeeder::class);
        $this->call(PaisSeeder::class);
        $this->call(DepartamentoSeeder::class);
        $this->call(MunicipioSeeder::class);
        $this->call(BancoSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(ClienteAperturaSeeder::class);

        Bodega::create([
            'bodega' => 'Zacapa',
            'direccion' => 'Zacapa',
            'municipio_id' => 290,
            'departamento_id' => 19,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Bodega::create([
            'bodega' => 'Capital',
            'direccion' => 'Capital',
            'municipio_id' => 8,
            'departamento_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Bodega::create([
            'bodega' => 'Mal estado',
            'direccion' => 'Zacapa',
            'municipio_id' => 290,
            'departamento_id' => 19,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Bodega::create([
            'bodega' => 'Traslado',
            'direccion' => 'Zacapa',
            'municipio_id' => 290,
            'departamento_id' => 19,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        /* Bodega::create([
            'bodega' => 'Abura',
            'direccion' => 'Capital',
            'municipio_id' => 8,
            'departamento_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Bodega::create([
            'bodega' => 'Jose Muralles',
            'direccion' => 'Guatemala',
            'municipio_id' => 8,
            'departamento_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]); */
    }
}
