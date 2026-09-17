<?php

declare(strict_types=1);

use App\Enums\EstadoVentaStatus;
use App\Filament\Ventas\Resources\VentaResource\Pages\ListVentas;
use App\Models\Bodega;
use App\Models\Cierre;
use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Pais;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('ventas'));
});

function crearEntornoVentaApertura(): array
{
    Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'cliente_apertura', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gerente', 'guard_name' => 'web']);

    $pais = Pais::create(['pais' => 'Guatemala']);
    $departamento = Departamento::create(['departamento' => 'Guatemala', 'pais_id' => $pais->id]);
    $municipio = Municipio::create(['municipio' => 'Guatemala', 'departamento_id' => $departamento->id, 'pais_id' => $pais->id]);

    $bodega = Bodega::create([
        'bodega' => 'QA Bodega',
        'direccion' => 'Direccion QA',
        'municipio_id' => $municipio->id,
        'departamento_id' => $departamento->id,
        'pais_id' => $pais->id,
    ]);

    $producto = Producto::create([
        'descripcion' => 'QA Producto',
        'genero' => 'CABALLERO',
        'precio_venta' => 1000,
    ]);

    $asesor = User::factory()->create(['name' => 'QA Asesor']);
    $asesor->assignRole('vendedor');
    $asesor->givePermissionTo(['view_any_venta', 'view_venta', 'create_venta']);
    $bodega->user()->attach($asesor->id);

    Cierre::create([
        'bodega_id' => $bodega->id,
        'user_id' => $asesor->id,
        'apertura' => now(),
        'cierre' => null,
    ]);

    $clienteApertura = User::factory()->create(['name' => 'QA Cliente Apertura']);
    $clienteApertura->assignRole('cliente_apertura');

    $administrador = User::factory()->create(['name' => 'QA Administrador']);
    $administrador->assignRole('administrador');
    $administrador->givePermissionTo(['view_any_venta', 'view_venta']);

    $gerente = User::factory()->create(['name' => 'QA Gerente']);
    $gerente->assignRole('gerente');
    $gerente->givePermissionTo(['view_any_venta', 'view_venta']);

    return compact('bodega', 'producto', 'asesor', 'clienteApertura', 'administrador', 'gerente');
}

function crearVentaPendienteApertura(array $entorno, array $overrides = []): Venta
{
    $venta = Venta::create(array_merge([
        'bodega_id' => $entorno['bodega']->id,
        'cliente_id' => $entorno['clienteApertura']->id,
        'asesor_id' => $entorno['asesor']->id,
        'estado' => EstadoVentaStatus::ValidacionPago->value,
        'requiere_validacion_pago' => false,
        'requiere_evidencia_oferta20' => true,
        'requiere_codigo_confirmacion' => true,
        'subtotal' => 800,
        'total' => 800,
    ], $overrides));

    VentaDetalle::create([
        'venta_id' => $venta->id,
        'producto_id' => $entorno['producto']->id,
        'cantidad' => 1,
        'precio' => 800,
        'subtotal' => 800,
        'oferta_cliente_20' => true,
    ]);

    return $venta->fresh();
}

it('oculta Enviar Código hasta que se sube la foto de evidencia, y luego genera un código de 6 dígitos', function () {
    $entorno = crearEntornoVentaApertura();
    $venta = crearVentaPendienteApertura($entorno);

    $this->actingAs($entorno['asesor']);

    Livewire::test(ListVentas::class)
        ->assertTableActionHidden('enviarCodigo', $venta);

    $venta->update(['foto_evidencia_oferta20' => 'evidencias/qa-test.jpg']);

    Livewire::test(ListVentas::class)
        ->assertTableActionVisible('enviarCodigo', $venta)
        ->callTableAction('enviarCodigo', $venta);

    $venta->refresh();

    expect($venta->codigo_confirmacion)->toHaveLength(6);
    expect($venta->codigo_generado_en)->not->toBeNull();
    expect($venta->codigo_confirmado_en)->toBeNull();
});

it('solo super_admin y administrador pueden ver el código; el asesor y un gerente no', function () {
    $entorno = crearEntornoVentaApertura();
    $venta = crearVentaPendienteApertura($entorno, [
        'foto_evidencia_oferta20' => 'evidencias/qa-test.jpg',
    ]);
    $venta->generarCodigoConfirmacion();
    $venta->save();

    $this->actingAs($entorno['administrador']);
    Livewire::test(ListVentas::class)->assertTableActionVisible('verCodigoConfirmacion', $venta);

    $this->actingAs($entorno['gerente']);
    Livewire::test(ListVentas::class)->assertTableActionHidden('verCodigoConfirmacion', $venta);

    $this->actingAs($entorno['asesor']);
    Livewire::test(ListVentas::class)->assertTableActionHidden('verCodigoConfirmacion', $venta);
});

it('rechaza un código incorrecto sin confirmar la venta', function () {
    $entorno = crearEntornoVentaApertura();
    $venta = crearVentaPendienteApertura($entorno, [
        'foto_evidencia_oferta20' => 'evidencias/qa-test.jpg',
    ]);
    $venta->generarCodigoConfirmacion();
    $venta->save();

    $this->actingAs($entorno['asesor']);

    Livewire::test(ListVentas::class)
        ->callTableAction('ingresarCodigo', $venta, data: ['codigo' => '000000']);

    $venta->refresh();

    expect($venta->codigo_confirmado_en)->toBeNull();
    expect($venta->estado)->toBe(EstadoVentaStatus::ValidacionPago);
});

it('rechaza un código expirado (más de 30 minutos)', function () {
    $entorno = crearEntornoVentaApertura();
    $venta = crearVentaPendienteApertura($entorno, [
        'foto_evidencia_oferta20' => 'evidencias/qa-test.jpg',
    ]);
    $venta->generarCodigoConfirmacion();
    $venta->codigo_generado_en = now()->subMinutes(31);
    $venta->save();

    $this->actingAs($entorno['asesor']);

    Livewire::test(ListVentas::class)
        ->callTableAction('ingresarCodigo', $venta, data: ['codigo' => $venta->codigo_confirmacion]);

    $venta->refresh();

    expect($venta->codigo_confirmado_en)->toBeNull();
});

it('con código correcto pero pago aún pendiente de validar, solo marca el código como confirmado (no factura todavía)', function () {
    $entorno = crearEntornoVentaApertura();
    $venta = crearVentaPendienteApertura($entorno, [
        'foto_evidencia_oferta20' => 'evidencias/qa-test.jpg',
        'requiere_validacion_pago' => true,
    ]);
    $venta->generarCodigoConfirmacion();
    $venta->save();

    $this->actingAs($entorno['asesor']);

    Livewire::test(ListVentas::class)
        ->callTableAction('ingresarCodigo', $venta, data: ['codigo' => $venta->codigo_confirmacion]);

    $venta->refresh();

    expect($venta->codigo_confirmado_en)->not->toBeNull();
    // Sigue pendiente porque falta que el admin valide el pago (requiere_validacion_pago).
    expect($venta->estado)->toBe(EstadoVentaStatus::ValidacionPago);
});

it('validarPago exige que el código esté confirmado antes de poder validar la venta', function () {
    $entorno = crearEntornoVentaApertura();
    $venta = crearVentaPendienteApertura($entorno, [
        'foto_evidencia_oferta20' => 'evidencias/qa-test.jpg',
        'requiere_validacion_pago' => true,
    ]);
    $venta->generarCodigoConfirmacion();
    $venta->save();

    $this->actingAs($entorno['administrador']);

    Livewire::test(ListVentas::class)
        ->callTableAction('validarPago', $venta);

    $venta->refresh();

    expect($venta->estado)->toBe(EstadoVentaStatus::ValidacionPago);
});
