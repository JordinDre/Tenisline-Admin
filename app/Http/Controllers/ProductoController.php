<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Bodega;
use App\Models\Escala;
use App\Models\Producto;
use App\Models\Inventario;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Database\Query\Builder;

class ProductoController extends Controller
{
    public static function getEscalaPrecio(int $productoId): ?Escala
    {
        Carbon::setLocale('es');
        $diaSemana = strtolower(Carbon::now()->dayName);

        
        $escala = Escala::where('producto_id', $productoId)
            ->where('dia', $diaSemana)
            ->first();

        return $escala; 
    }
    
    public static function renderProductos(Producto $record, string $tipo, $bodega_id = null): string
    {
        Log::info('Renderizando Producto ID: ' . $record->id); // LOG #1: ID del producto que se está renderizando
        Log::info('Relación Presentacion: ', [$record->presentacion]);

        $inventario = $bodega_id ? Inventario::where('producto_id', $record->id)->where('bodega_id', $bodega_id)->first() : null;
        $stock = $inventario ? $inventario->existencia : 0;
        $precioVenta = $record->precio_venta;

        $escala = self::getEscalaPrecio($record->id); // Usamos self:: para llamar a la función estática dentro de la misma clase
        $precioConDescuento = $precioVenta; // Precio por defecto (sin descuento)
        $diaDescuento = null; // Para almacenar el día del descuento si lo hay

        if ($escala) {
            $precioConDescuento = round($precioVenta * (1 - ($escala->porcentaje / 100)), 2); // Calcular precio con descuento
            $diaDescuento = ucfirst($escala->dia); // Obtener el día de la escala y poner la primera letra en mayúscula
        }
        
        $userRoles = auth()->user()->roles->pluck('name');
        $escalasFiltradas = collect();

        /* if ($tipo) {
            $rolesValidos = $tipo === 'venta' ? User::VENTA_ROLES : User::ORDEN_ROLES;
            $roleIds = Role::whereIn('name', $rolesValidos)->pluck('id')->toArray();

            $escalasFiltradas = $producto->escalas->filter(
                fn ($escala) => in_array($escala->role_id, $roleIds) && $userRoles->contains($escala->role->name)
            );
        } */

        /* $existencia = $tipo === 'orden'
            ? $producto->inventario->whereIn('bodega_id', Bodega::EXISTENCIA_ORDENES)->sum('existencia')
            : optional($producto->inventario->where('bodega_id', $bodega)->first())->existencia ?? 0;

        $imagenUrl = isset($producto->imagenes[0])
            ? config('filesystems.disks.s3.url').$producto->imagenes[0]
            : asset('images/icono.png'); */

       /*  $escalasHtml = $escalasFiltradas->isNotEmpty()
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
        /* return "
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
        </div>"; */

        $precioMostrar = $escala ? "<s>Q. " . number_format($precioVenta, 2) . "</s>  <b>Q. " . number_format($precioConDescuento, 2) . "</b> <small style='color: green;'> (Descuento de {$escala->porcentaje}% - {$diaDescuento})</small>" : "<b>Q. " . number_format($precioVenta, 2) . "</b>";

        return  nl2br(
            "<b>{$record->descripcion}</b>\n".
            "<b>{$record->marca->marca}</b> ".
            "<b>{$record->talla}</b> ".
            "<b>{$record->modelo}</b> ".
            "<b>{$record->genero}</b> ".
            "<b>{$record->color}</b> ".
            "Precio: ". $precioMostrar . " ".
            /* "<b>{$record->presentacion->presentacion}</b>\n". */
            "Stock: <b>{$stock}</b>"
        );
    }

    public static function searchProductos(string $search, string $tipo, $bodega_id = null): array
    {
        $query = Producto::query()
            ->where('descripcion', 'like', "%{$search}%")
            ->orWhere('nombre', 'like', "%{$search}%") 
            ->orWhere('modelo', 'like', "%{$search}%") 
            ->orWhereHas('marca', function ($query) use ($search) {
                $query->where('marca', 'like', "%{$search}%");
            })
            /* ->orWhereHas('presentacion', function ($query) use ($search) {
                $query->where('presentacion', 'like', "%{$search}%");
            }) */;

        /* if ($bodega_id) {
            $query->whereHas('inventarios', function (Builder $query) use ($bodega_id) {
                $query->where('bodega_id', $bodega_id);
            });
        } */


        return $query->limit(50)
            ->get()
            ->mapWithKeys(function (Producto $record) use ($tipo, $bodega_id) {
                return [$record->id => ProductoController::renderProductos($record, $tipo, $bodega_id)];
            })
            ->toArray();
            
        /* $productos = Producto::query()
            ->with(['marca', 'presentacion'])
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
        ])->toArray(); */
    }
}
