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
        $this->call(ClienteAperturaSeeder::class);
    }
}
