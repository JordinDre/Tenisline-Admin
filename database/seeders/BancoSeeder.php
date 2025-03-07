<?php

namespace Database\Seeders;

use App\Models\Banco;
use Illuminate\Database\Seeder;

class BancoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Banco::create([
            'banco' => 'Banco de Desarrollo Rural, S. A.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'Banco Industrial, S. A.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'Banco de los Trabajadores',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'Banco Agromercantil de Guatemala, S. A.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'Banco G&T Continental, S. A.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'Banco Azteca de Guatemala S. A.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'Banco Inmobiliario, S. A.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'Banco Internacional, S. A.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'Banco Promérica, S. A.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'Banco DE ANTIGUA, S. A.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'Banco DE América Central, S. A.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'Banco Ficohsa Guatemala S. A.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'Banco Crédito Hipotecario Nacional de Guatemala',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'CitiBank, N.A., Sucursal Guatemala',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'VIVIBanco, S. A.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'Banco INV, S. A. ',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Banco::create([
            'banco' => 'Banco CREDICORP, S. A.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
