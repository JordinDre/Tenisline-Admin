<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Escala;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Query\Builder;
use Spatie\Permission\Models\Role;

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

    public static function renderProductos(Producto $record, string $tipo, $bodega_id = null, $cliente_id = null): string
    {

        $inventario = $bodega_id ? Inventario::where('producto_id', $record->id)->where('bodega_id', $bodega_id)->first() : null;
        $stock = $inventario ? $inventario->existencia : 0;
        $precioVenta = $record->precio_venta;
        $precioMayorista = $record->precio_mayorista;

        $escala = self::getEscalaPrecio($record->id); // Usamos self:: para llamar a la función estática dentro de la misma clase
        $precioConDescuento = $precioVenta; // Precio por defecto (sin descuento)
        $diaDescuento = null; // Para almacenar el día del descuento si lo hay

        $imagenUrl = isset($record->imagenes[0])
            ? config('filesystems.disks.s3.url').$record->imagenes[0]
            : asset('images/icono.png');

        if ($escala) {
            $precioConDescuento = round($precioVenta * (1 - ($escala->porcentaje / 100)), 2); // Calcular precio con descuento
            $diaDescuento = ucfirst($escala->dia); // Obtener el día de la escala y poner la primera letra en mayúscula
        }

        $precioMostrar = ''; // Inicializamos vacío

        $clienteRol = null;
        if ($cliente_id) {
            $cliente = User::find($cliente_id);
            $clienteRol = $cliente?->getRoleNames()->first(); // Asumiendo que el cliente tiene solo un rol principal
        }

        $preciosHTML = ''; // Inicializamos para acumular los precios a mostrar

        if ($clienteRol === 'colaborador') {
            $preciosHTML = "<div style='margin-top: 5px;'>
                                <div style='font-weight: bold; color: blue;'>
                                    Precio Mayorista: Q. ".number_format($precioMayorista, 2)."
                                </div>
                                <div style='color: grey; text-decoration: line-through;'>
                                    Precio Venta: Q. ".number_format($precioVenta, 2).'
                                </div>
                            </div>';
        } else {
            if ($escala) {
                $preciosHTML = "<div style='margin-top: 5px;'>
                                    <div style='font-weight: bold; color: green;'>
                                        Precio con Descuento ({$escala->porcentaje}% - {$diaDescuento}): Q. ".number_format($precioConDescuento, 2)."
                                    </div>
                                    <div style='color: grey; text-decoration: line-through;'>
                                        Precio Venta: Q. ".number_format($precioVenta, 2)."
                                    </div>
                                    <div style='color: blue;'>
                                        Precio Mayorista: Q. ".number_format($precioMayorista, 2).'
                                    </div>
                                </div>';
            } else {
                $preciosHTML = "<div style='margin-top: 5px;'>
                                        <div style='font-weight: bold; color: black;'>
                                            Precio Venta: Q. ".number_format($precioVenta, 2)."
                                        </div>
                                        <div style='color: blue;'>
                                            Precio Mayorista: Q. ".number_format($precioMayorista, 2).'
                                        </div>
                                    </div>';
            }
        }
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
             : '';

         return "
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

        return
            "
        <div style='display: flex; align-items: flex-start;'> 
            <img src='{$imagenUrl}' alt='Imagen del producto' 
                 style='width: 100px; height: 100px; object-fit: cover; margin-right: 10px;' />
            <div>
                <div style='font-weight: bold; color: black;'> 
                    ID: {$record->id} - <span style='color: black;'>{$record->codigo}</span>
                </div>
                <div style='color: black;'> 
                    <span style='color: black;'>Descripción: </span>{$record->descripcion}
                </div>
                <div style='color: black;'>
                    Marca: {$record->marca->marca}, Talla: {$record->talla}, Estilo: {$record->genero}
                </div>
                <div style='color: black; font-weight: bold; margin-top: 5px;'>
                    Existencia: {$stock}
                </div>
                <div>
                    <div style='display: flex; flex-wrap: wrap; margin-top: 5px;'>{$preciosHTML}</div>
                </div>
            </div>
        </div>";

    }

    public static function renderProductosBasico(Producto $producto, string $tipo, $bodega_id = null, $cliente_id = null): string
    {
        $inventario = $bodega_id
        ? Inventario::where('producto_id', $producto->id)->where('bodega_id', $bodega_id)->first()
        : null;

        $stock = $inventario?->existencia ?? 0;
/* 
        $imagenUrl = isset($producto->imagenes[0])
            ? config('filesystems.disks.s3.url') . $producto->imagenes[0]
            : asset('images/icono.png'); */

        return view('components.producto-preview', compact('producto', 'stock'))->render();

    }

    public static function searchProductos(string $search, string $tipo, $bodega_id = null): array
    {

        $query = Producto::query();
        $terms = explode(' ', $search);
        $terms = array_filter($terms);

        foreach ($terms as $term) {
            $query->where(function ($q) use ($term) {
                $q->where('descripcion', 'like', "%{$term}%")
                    ->orWhere('codigo', 'like', "%{$term}%")
                    ->orWhere('modelo', 'like', "%{$term}%")
                    ->orWhere('talla', 'like', "%{$term}%")
                    ->orWhere('genero', 'like', "%{$term}%")
                    ->orWhereHas('marca', function ($query) use ($term) {
                        $query->where('marca', 'like', "%{$term}%");
                    });
            });
        }
        /* ->orWhereHas('presentacion', function ($query) use ($search) {
            $query->where('presentacion', 'like', "%{$search}%");
        }) */

        /* if ($bodega_id) {
            $query->whereHas('inventarios', function (Builder $query) use ($bodega_id) {
                $query->where('bodega_id', $bodega_id);
            });
        } */

        return $query->limit(10)
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
                    ->orWhereHas('presentacion', fn ($q) => $q->where('presentacion', 'LIKE', "%{$search}%"));
            })
            ->limit(5)
            ->get();

        return $productos->mapWithKeys(fn ($producto) => [
            $producto->id => self::renderProductos($producto, $tipo, $bodega),
        ])->toArray(); */
    }

    public static function searchProductosBasico(string $search, string $tipo, $bodega_id = null): array
    {
        $query = Producto::query()
            ->with('marca')
            ->when($bodega_id, fn($q) => $q->withCount(['inventario as stock' => fn($iq) => $iq->where('bodega_id', $bodega_id)]));

        $terms = array_filter(explode(' ', $search));
        foreach ($terms as $term) {
            $query->where(fn ($q) => $q
                ->where('descripcion', 'like', "%{$term}%")
                ->orWhere('codigo', 'like', "%{$term}%")
                ->orWhere('modelo', 'like', "%{$term}%")
                ->orWhere('talla', 'like', "%{$term}%")
                ->orWhere('genero', 'like', "%{$term}%")
                ->orWhereHas('marca', fn ($q2) => $q2->where('marca', 'like', "%{$term}%"))
            );
        }

        $productos = $query->limit(10)->get();

        return $productos->mapWithKeys(fn (Producto $producto) =>
            [$producto->id => self::renderProductosRow($producto)]
        )->toArray();
    }

    protected static function renderProductosRow(Producto $producto): string
    {
        $marca = $producto->marca->marca ?? '';
        $stock = $producto->stock ?? 0;
        return "
            <div class='producto-opcion'>
                <div><strong>ID:</strong> {$producto->id} - {$producto->codigo}</div>
                <div>Descripción: {$producto->descripcion}</div>
                <div>Marca: {$marca}, Talla: {$producto->talla}, Estilo: {$producto->genero}</div>
                <div><strong>Existencia:</strong> {$stock}</div>
            </div>
        ";
    }
}
