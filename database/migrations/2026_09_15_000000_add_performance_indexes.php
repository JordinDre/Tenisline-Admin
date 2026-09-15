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
        $this->addIndexesSafe('ventas', [
            'ventas_created_at_index' => 'created_at',
            'ventas_estado_index' => 'estado',
            'ventas_bodega_id_created_at_index' => ['bodega_id', 'created_at'],
            'ventas_cliente_id_created_at_index' => ['cliente_id', 'created_at'],
        ]);

        $this->addIndexesSafe('venta_detalles', [
            'venta_detalles_devuelto_index' => 'devuelto',
            'venta_detalles_venta_id_devuelto_index' => ['venta_id', 'devuelto'],
        ]);

        $this->addIndexesSafe('inventarios', [
            'inventarios_bodega_id_existencia_index' => ['bodega_id', 'existencia'],
        ]);

        $this->addIndexesSafe('seguimientos', [
            'seguimientos_seguimientable_tipo_created_at_index' => ['seguimientable_id', 'seguimientable_type', 'tipo', 'created_at'],
        ]);

        $this->addIndexesSafe('caja_chicas', [
            'caja_chicas_bodega_id_created_at_index' => ['bodega_id', 'created_at'],
            'caja_chicas_aplicado_en_cierre_id_index' => 'aplicado_en_cierre_id',
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
                        $table->index($columns, $indexName);
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
        $this->dropIndexesSafe('ventas', [
            'ventas_created_at_index',
            'ventas_estado_index',
            'ventas_bodega_id_created_at_index',
            'ventas_cliente_id_created_at_index',
        ]);

        $this->dropIndexesSafe('venta_detalles', [
            'venta_detalles_devuelto_index',
            'venta_detalles_venta_id_devuelto_index',
        ]);

        $this->dropIndexesSafe('inventarios', [
            'inventarios_bodega_id_existencia_index',
        ]);

        $this->dropIndexesSafe('seguimientos', [
            'seguimientos_seguimientable_tipo_created_at_index',
        ]);

        $this->dropIndexesSafe('caja_chicas', [
            'caja_chicas_bodega_id_created_at_index',
            'caja_chicas_aplicado_en_cierre_id_index',
        ]);
    }

    /**
     * Helper to drop indexes safely by name.
     */
    private function dropIndexesSafe(string $tableName, array $indexNames): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $existingIndexes = collect(Schema::getIndexes($tableName))->pluck('name')->all();

        Schema::table($tableName, function (Blueprint $table) use ($existingIndexes, $indexNames) {
            foreach ($indexNames as $indexName) {
                if (in_array($indexName, $existingIndexes)) {
                    $table->dropIndex($indexName);
                }
            }
        });
    }
};
