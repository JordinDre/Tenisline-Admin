<?php

declare(strict_types=1);

use App\Filament\Inventario\Widgets\ExistenciaPorBodega;
use App\Filament\Inventario\Widgets\VentasPorBodega;
use App\Models\Bodega;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Livewire\Livewire;

it('can render existencia por bodega widget', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('widget_ExistenciaPorBodega');

    $this->actingAs($user);

    Livewire::test(ExistenciaPorBodega::class)
        ->assertSuccessful();
});

it('shows bodega existence data correctly', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('widget_ExistenciaPorBodega');

    // Crear bodega
    $bodega = Bodega::factory()->create([
        'bodega' => 'Test Bodega',
    ]);

    // Crear producto
    $producto = Producto::factory()->create();

    // Crear inventario
    Inventario::factory()->create([
        'bodega_id' => $bodega->id,
        'producto_id' => $producto->id,
        'existencia' => 100,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ExistenciaPorBodega::class);

    $stats = $component->get('getStats');

    expect($stats)->toHaveCount(2); // Una bodega + total
    expect($stats[0]->getLabel())->toBe('Test Bodega');
    expect($stats[0]->getValue())->toContain('100 pares');
});

it('can render ventas por bodega widget', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('widget_VentasPorBodega');

    $this->actingAs($user);

    Livewire::test(VentasPorBodega::class)
        ->assertSuccessful();
});

it('shows bodega sales data correctly', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('widget_VentasPorBodega');

    // Crear bodega
    $bodega = Bodega::factory()->create([
        'bodega' => 'Test Bodega',
    ]);

    // Crear producto
    $producto = Producto::factory()->create();

    // Crear venta
    $venta = Venta::factory()->create([
        'bodega_id' => $bodega->id,
        'estado' => 'creada',
        'created_at' => now(),
    ]);

    // Crear detalle de venta
    VentaDetalle::factory()->create([
        'venta_id' => $venta->id,
        'producto_id' => $producto->id,
        'cantidad' => 5,
        'precio' => 100.00,
        'devuelto' => 0,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VentasPorBodega::class);

    $stats = $component->get('getStats');

    expect($stats)->toHaveCount(2); // Una bodega + total
    expect($stats[0]->getLabel())->toBe('Test Bodega');
    expect($stats[0]->getValue())->toContain('5 pares');
    expect($stats[0]->getDescription())->toContain('Q100.00');
});

it('respects user permissions for existencia widget', function () {
    $user = User::factory()->create();
    // No dar permiso al usuario

    $this->actingAs($user);

    expect(ExistenciaPorBodega::canView())->toBeFalse();
});

it('respects user permissions for ventas widget', function () {
    $user = User::factory()->create();
    // No dar permiso al usuario

    $this->actingAs($user);

    expect(VentasPorBodega::canView())->toBeFalse();
});
