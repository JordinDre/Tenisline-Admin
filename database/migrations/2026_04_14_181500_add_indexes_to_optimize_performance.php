<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->addIndexesSafe('productos', [
            'productos_marchamo_index' => 'marchamo',
            'productos_color_index' => 'color',
            'productos_talla_index' => 'talla',
            'productos_genero_index' => 'genero',
            'productos_marca_id_deleted_at_index' => ['marca_id', 'deleted_at'],
            'productos_proveedor_id_deleted_at_index' => ['proveedor_id', 'deleted_at'],
        ]);

        $this->addIndexesSafe('ordens', [
            'ordens_estado_index' => 'estado',
            'ordens_tipo_pago_id_index' => 'tipo_pago_id',
            'ordens_asesor_id_index' => 'asesor_id',
            'ordens_bodega_id_index' => 'bodega_id',
            'ordens_created_at_index' => 'created_at',
        ]);

        $this->addIndexesSafe('inventarios', [
            'inventarios_producto_id_bodega_id_existencia_index' => ['producto_id', 'bodega_id', 'existencia'],
            'inventarios_existencia_index' => 'existencia',
        ]);
    }

    /**
     * Helper to add indexes safely by checking existence and catching duplicate errors.
     */
    private function addIndexesSafe(string $tableName, array $indexes): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $existingIndexes = collect(Schema::getIndexes($tableName))->pluck('name')->all();

        Schema::table($tableName, function (Blueprint $table) use ($existingIndexes, $indexes) {
            foreach ($indexes as $indexName => $columns) {
                if (!in_array($indexName, $existingIndexes)) {
                    try {
                        $table->index($columns);
                    } catch (\Exception $e) {
                        // Check if the error is "Duplicate key name" (MySQL error 1061)
                        if (!str_contains($e->getMessage(), '1061') && !str_contains($e->getMessage(), 'Duplicate key name')) {
                            throw $e;
                        }
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexesSafe('productos', [
            'productos_marchamo_index' => 'marchamo',
            'productos_color_index' => 'color',
            'productos_talla_index' => 'talla',
            'productos_genero_index' => 'genero',
            'productos_marca_id_deleted_at_index' => ['marca_id', 'deleted_at'],
            'productos_proveedor_id_deleted_at_index' => ['proveedor_id', 'deleted_at'],
        ]);

        $this->dropIndexesSafe('ordens', [
            'ordens_estado_index' => 'estado',
            'ordens_tipo_pago_id_index' => 'tipo_pago_id',
            'ordens_asesor_id_index' => 'asesor_id',
            'ordens_bodega_id_index' => 'bodega_id',
            'ordens_created_at_index' => 'created_at',
        ]);

        $this->dropIndexesSafe('inventarios', [
            'inventarios_producto_id_bodega_id_existencia_index' => ['producto_id', 'bodega_id', 'existencia'],
            'inventarios_existencia_index' => 'existencia',
        ]);
    }

    /**
     * Helper to drop indexes safely by checking existence.
     */
    private function dropIndexesSafe(string $tableName, array $indexes): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $existingIndexes = collect(Schema::getIndexes($tableName))->pluck('name')->all();

        Schema::table($tableName, function (Blueprint $table) use ($existingIndexes, $indexes) {
            foreach ($indexes as $indexName => $columns) {
                if (in_array($indexName, $existingIndexes)) {
                    $table->dropIndex($columns);
                }
            }
        });
    }
};
