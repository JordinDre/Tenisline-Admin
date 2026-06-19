<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')
            ->where('name', 'diseñador')
            ->update(['name' => 'auxiliar']);
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'auxiliar')
            ->update(['name' => 'diseñador']);
    }
};
