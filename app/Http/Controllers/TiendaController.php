<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Carrito;
use App\Models\Guia;
use App\Models\Marca;
use App\Models\Orden;
use App\Models\OrdenDetalle;
use App\Models\Producto;
use App\Models\Tienda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TiendaController extends Controller
{
    public function index()
    {
        return Inertia::render('Inicio', [
            'contenido' => Tienda::first(),
            'url' => config('filesystems.disks.s3.url'),
        ]);
    }

    public function orden()
    {
        return Inertia::render('CrearOrden', [
            'direcciones' => auth()->user()->direcciones()->with(['municipio', 'departamento'])->get(),
            'tipoPagos' => auth()->user()->tipo_pagos,
        ]);
    }

    public function storeOrden(Request $request)
    {
        DB::transaction(function () use ($request) {
            $request->validate([
                'direccion' => 'required',
                'tipoPago' => 'required',
            ]);

            $carrito = Carrito::where('user_id', auth()->user()->id)->get();

            if ($carrito->isEmpty()) {
                throw new \Exception('El carrito está vacío.');
            }

            $subtotal = $carrito->sum(function ($item) {
                return $item->precio * $item->cantidad;
            });
            $envioGratisMinimo = Guia::ENVIO_GRATIS;
            $envio = 0;

            if ($subtotal < $envioGratisMinimo) {
                $envio = Guia::ENVIO;
            }

            $orden = Orden::create([
                'asesor_id' => auth()->user()->id,
                'cliente_id' => auth()->user()->id,
                'direccion_id' => $request->direccion,
                'tipo_pago_id' => $request->tipoPago,
                'subtotal' => $subtotal,
                'envio' => $envio,
                'total' => $subtotal + $envio,
                'tipo_envio' => 'guatex',
                'estado' => 'creada',
                'facturar_cf' => $request->facturar_cf ? true : false,
                'enlinea' => 1,
            ]);
            activity()->performedOn($orden)->causedBy(auth()->user())->withProperties($orden)->event('created')->log('Orden creada en línea');

            foreach ($carrito as $item) {
                $detalle = OrdenDetalle::create([
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'orden_id' => $orden->id,
                    'producto_id' => $item->producto_id,
                ]);
                activity()->performedOn($detalle)->causedBy(auth()->user())->withProperties($detalle)->event('created')->log('Detalle de Orden en línea');
            }
            Carrito::where('user_id', auth()->user()->id)->delete();
            activity()->performedOn($orden)->causedBy(auth()->user())->withProperties($orden)->event('deleted')->log('Carrito Eliminado');
        });
    }

    public function catalogo(Request $request)
    {
        $search = $request->search;
        $marca = $request->marca;
        $bodega = $request->bodega;
        $tallas = $request->tallas ?? [];
        $precioMin = $request->precioMin;
        $precioMax = $request->precioMax;
        $color = $request->color;
        $genero = $request->genero;

        $productos = Producto::with('marca', 'inventario')
            ->whereHas('inventario', function ($query) use ($bodega) {
                $query->where('existencia', '>', 0);

                if ($bodega) {
                    $query->where('bodega_id', $bodega);
                }
            });

        if ($search) {
            $searchTerms = explode(' ', $search);

            foreach ($searchTerms as $term) {
                $productos->where(function ($query) use ($term) {
                    $query->where('productos.codigo', 'LIKE', "%{$term}%")
                        ->orWhere('productos.id', 'LIKE', "%{$term}%")
                        ->orWhere('productos.descripcion', 'LIKE', "%{$term}%")
                        ->orWhere('productos.modelo', 'like', "%{$term}%")
                        ->orWhere('productos.talla', 'like', "%{$term}%")
                        ->orWhere('productos.genero', 'like', "%{$term}%")
                        ->orWhere('productos.color', 'like', "%{$term}%")
                        ->orWhereHas('marca', fn ($q) => $q->where('marca', 'LIKE', "%{$term}%"));
                });
            }
        }

        if ($marca) {
            $productos->whereHas('marca', function ($query) use ($marca) {
                $query->where('marca', '=', $marca);
            });
        }

        if (! empty($tallas)) {
            // Normaliza las tallas ingresadas (ej. "8.0" → "8")
            $tallasNormalizadas = collect($tallas)
                ->map(fn ($t) => rtrim(rtrim($t, '0'), '.')) // elimina .0 o .00
                ->unique()
                ->toArray();

            // Aplica comparación también normalizada en SQL
            $productos->whereIn(
                DB::raw("REPLACE(REPLACE(productos.talla, '.0', ''), '.00', '')"),
                $tallasNormalizadas
            );
        }

        if ($color) {
            $productos->where('color', $color);
        }

        if ($genero) {
            $productos->where('genero', $genero); // ✅ NUEVO filtro
        }

        /* if (!$search && !$marca ) {
            $productos->inRandomOrder();
        } */

        if ($precioMin !== null && $precioMax !== null) {
            $productos->whereBetween('precio_venta', [$precioMin, $precioMax]);
        }

        $productos = $productos
            ->paginate(20)
            ->withQueryString()
            ->through(function ($producto) {
                $user = auth()->user();

                return [
                    'id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'slug' => $producto->slug,
                    'descripcion' => $producto->descripcion,
                    'precio' => $producto->precio_venta ?? null,
                    'modelo' => $producto->modelo ?? null,
                    'talla' => $producto->talla ?? null,
                    'color' => $producto->color ?? null,
                    'genero' => $producto->genero ?? null,
                    'stock' => $producto->inventario->sum('existencia'),
                    'imagen' => isset($producto->imagenes[0])
                        ? config('filesystems.disks.s3.url').$producto->imagenes[0]
                        : asset('images/icono.png'),
                    'marca' => $producto->marca->marca ?? null,

                    // ✅ Agregar detalle de bodegas solo si está logueado
                    'bodegas' => $user
                        ? $producto->inventario
                            ->map(fn ($inv) => [
                                'bodega' => $inv->bodega->bodega ?? 'Desconocida',
                                'existencia' => $inv->existencia,
                            ])
                            ->toArray()
                        : null,
                ];
            });

        $bodegas = Bodega::whereNotIn('bodega', ['Mal estado', 'Traslado'])
            ->where('bodega', 'not like', '%bodega%')
            ->get(['id', 'bodega']);

        $tallasDisponibles = Producto::select('talla')->distinct()->pluck('talla');
        $marcasDisponibles = Marca::select('marca')->distinct()->pluck('marca');
        $colores = Producto::select('color')->whereNotNull('color')->distinct()->pluck('color');
        $generosDisponibles = Producto::select('genero')->distinct()->pluck('genero')->filter()->values();

        return Inertia::render('Catalogo', [
            'productos' => $productos,
            'search' => $search,
            'bodega' => $bodega,
            'bodegas' => $bodegas,
            'marca' => $marca,
            'color' => $color,
            'tallas' => $tallas,
            'genero' => $genero,
            'precioMin' => $precioMin,
            'precioMax' => $precioMax,
            'tallasDisponibles' => $tallasDisponibles,
            'marcasDisponibles' => $marcasDisponibles,
            'coloresDisponibles' => $colores,
            'generosDisponibles' => $generosDisponibles,
        ]);
    }

    public function producto($slug)
    {
        $producto = Producto::where('slug', $slug)->first();

        $marcas = Marca::whereHas('productos', function ($q) {
            $q->whereHas('inventario', function ($q2) {
                $q2->where('existencia', '>', 0);
            });
        })
            ->orderBy('marca')
            ->pluck('marca');

        return Inertia::render('Producto', [
            'producto' => [
                'id' => $producto->id ?? null,
                'codigo' => $producto->codigo ?? null,
                'slug' => $producto->slug ?? null,
                'descripcion' => $producto->descripcion ?? null,
                'precio' => $producto->precio_venta ?? null,
                'genero' => $producto->genero ?? null,
                'modelo' => $producto->modelo ?? null,
                'talla' => $producto->talla ?? null,
                'stock' => $producto->inventario->sum('existencia'),
                'imagen' => isset($producto->imagenes[0])
                    ? config('filesystems.disks.s3.url').$producto->imagenes[0]
                    : asset('images/icono.png'),
                'marca' => $producto->marca->marca ?? null,
                'bodegas' => auth()->check()
                    ? $producto->inventario
                        ->map(fn ($inv) => [
                            'bodega' => $inv->bodega->bodega ?? 'Desconocida',
                            'existencia' => $inv->existencia,
                        ])
                        ->toArray()
                    : null,

            ],
            'marcas' => $marcas,
        ]);
    }

    public function agregarCarrito(Request $request)
    {
        $producto = Producto::findOrFail($request->producto_id);
        $carritoItem = Carrito::where('user_id', auth()->user()->id)
            ->where('producto_id', $producto->id)
            ->first();

        if ($carritoItem) {
            $carritoItem->cantidad += 1;
            $carritoItem->save();
        } else {
            Carrito::create([
                'user_id' => auth()->user()->id,
                'producto_id' => $producto->id,
                'precio' => $producto->enlinea->precio ?? null,
                'cantidad' => 1,
            ]);
        }

        session()->flash('success', 'Producto agregado al carrito');

        return back();
    }

    public function sumarCarrito($id)
    {
        $carritoItem = Carrito::where('user_id', auth()->user()->id)
            ->where('producto_id', $id)
            ->first();

        if ($carritoItem) {
            $carritoItem->cantidad += 1;
            $carritoItem->save();
        } else {
            $producto = Producto::findOrFail($id);
            Carrito::create([
                'user_id' => auth()->user()->id,
                'producto_id' => $producto->id,
                'cantidad' => 1,
            ]);
        }
    }

    public function restarCarrito($id)
    {
        $carritoItem = Carrito::where('user_id', auth()->user()->id)
            ->where('producto_id', $id)
            ->first();

        if ($carritoItem && $carritoItem->cantidad > 1) {
            $carritoItem->cantidad -= 1;
            $carritoItem->save();
        } elseif ($carritoItem) {
            $carritoItem->delete();
        }
    }

    public function eliminarCarrito($id)
    {
        $carritoItem = Carrito::where('user_id', auth()->user()->id)
            ->where('producto_id', $id)
            ->first();

        if ($carritoItem) {
            $carritoItem->delete();
        }
    }
}
