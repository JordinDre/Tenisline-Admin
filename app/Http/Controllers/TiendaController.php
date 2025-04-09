<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\Guia;
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
    
        $productos = Producto::query()
            ->with(['marca', 'stock'])
            ->whereHas('stock', function ($query) {
                $query->where('existencia', '>', 0); // Solo productos con stock en bodega_id = 1
            });
    
        if ($search) {
            $productos->where(function ($query) use ($search) {
                $query->where('productos.codigo', 'LIKE', "%{$search}%")
                    ->orWhere('productos.id', 'LIKE', "%{$search}%")
                    ->orWhere('productos.descripcion', 'LIKE', "%{$search}%")
                    ->orWhere('productos.modelo', 'like', "%{$search}%")
                    ->orWhere('productos.talla', 'like', "%{$search}%")
                    ->orWhere('productos.genero', 'like', "%{$search}%")
                    ->orWhereHas('marca', fn ($q) => $q->where('marca', 'LIKE', "%{$search}%"));
            });
        }
    
        $productos = $productos
            ->orderBy('descripcion') // o cualquier orden que prefieras
            ->paginate(20)
            ->through(function ($producto) {
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
                    'stock' => $producto->stock->existencia ?? 0,
                    'imagen' => isset($producto->imagenes[0])
                        ? config('filesystems.disks.s3.url').$producto->imagenes[0]
                        : asset('images/icono.png'),
                    'marca' => $producto->marca->marca ?? null,
                ];
            });
    
        return Inertia::render('Catalogo', [
            'productos' => $productos,
        ]);
    }    

    public function producto($slug)
    {
        $producto = Producto::where('slug', $slug)->first();

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
                'stock' => $producto->stock->existencia ?? 0,
                'imagen' => isset($producto->imagenes[0])
                    ? config('filesystems.disks.s3.url').$producto->imagenes[0]
                    : asset('images/icono.png'),
                'marca' => $producto->marca->marca ?? null,
            ],
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
