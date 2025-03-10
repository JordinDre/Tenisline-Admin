<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Producto;
use App\Models\User;
use Spatie\Permission\Models\Role;

class ProductoController extends Controller
{
    public static function renderProductos(Producto $producto, $tipo = null, $bodega = 1): string
    {
        $userRoles = auth()->user()->roles->pluck('name');
        $escalasFiltradas = collect();

        /* if ($tipo) {
            $rolesValidos = $tipo === 'venta' ? User::VENTA_ROLES : User::ORDEN_ROLES;
            $roleIds = Role::whereIn('name', $rolesValidos)->pluck('id')->toArray();

            $escalasFiltradas = $producto->escalas->filter(
                fn ($escala) => in_array($escala->role_id, $roleIds) && $userRoles->contains($escala->role->name)
            );
        } */

        $existencia = $tipo === 'orden'
            ? $producto->inventario->whereIn('bodega_id', Bodega::EXISTENCIA_ORDENES)->sum('existencia')
            : optional($producto->inventario->where('bodega_id', $bodega)->first())->existencia ?? 0;

        $imagenUrl = isset($producto->imagenes[0])
            ? config('filesystems.disks.s3.url').$producto->imagenes[0]
            : asset('images/icono.png');

        /* $escalasHtml = $escalasFiltradas->isNotEmpty()
            ? $escalasFiltradas->map(fn ($escala) => "<div style='margin-right: 10px;'><strong>{$escala->escala}:</strong> Q{$escala->precio}</div>")
                ->implode('')
            : ''; */

        /* return "
        <div style='display: flex; align-items: flex-start;'> 
            <img src='{$imagenUrl}' alt='Imagen del producto' 
                 style='width: 100px; height: 100px; object-fit: cover; margin-right: 10px;' />
            <div>
                <div style='font-weight: bold; color: black;'> 
                    ID: {$producto->id} - <span style='color: black;'>{$producto->codigo}</span>
                </div>
                <div style='color: black;'> 
                    <span style='color: black;'>Descripción: </span>{$producto->descripcion}
                </div>
                <div style='color: black;'>
                    Marca: {$producto->marca->marca} - Presentación: {$producto->presentacion->presentacion}
                </div>
                <div style='color: black; font-weight: bold; margin-top: 5px;'>
                    Existencia: {$existencia}
                </div>
                <div>
                    <div style='display: flex; flex-wrap: wrap; margin-top: 5px;'>{$escalasHtml}</div>
                </div>
            </div>
        </div>"; */

        return "
        <div style='display: flex; align-items: flex-start;'> 
            <img src='{$imagenUrl}' alt='Imagen del producto' 
                 style='width: 100px; height: 100px; object-fit: cover; margin-right: 10px;' />
            <div>
                <div style='font-weight: bold; color: black;'> 
                    ID: {$producto->id} - <span style='color: black;'>{$producto->nombre}</span>
                </div>
                <div style='color: black;'> 
                    <span style='color: black;'>Descripción: </span>{$producto->descripcion}
                </div>
                <div style='color: black;'>
                    Marca: {$producto->marca->marca} 
                </div>
                <div style='color: black; font-weight: bold; margin-top: 5px;'>
                    Existencia: {$existencia}
                </div>
            </div>
        </div>";
    }

    public static function searchProductos(string $search, $tipo = null, $bodega = 1): array
    {
        $productos = Producto::query()
            ->with(['marca'/* , 'presentacion' */])
            ->where(function ($query) use ($search) {
                $query->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('id', 'LIKE', "%{$search}%")
                    ->orWhere('descripcion', 'LIKE', "%{$search}%")
                    ->orWhereHas('marca', fn ($q) => $q->where('marca', 'LIKE', "%{$search}%"))
                    /* ->orWhereHas('presentacion', fn ($q) => $q->where('presentacion', 'LIKE', "%{$search}%")) */;
            })
            ->limit(5)
            ->get();

        return $productos->mapWithKeys(fn ($producto) => [
            $producto->id => self::renderProductos($producto, $tipo, $bodega),
        ])->toArray();
    }
}
