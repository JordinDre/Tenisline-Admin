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
use App\Models\VentaDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'direcciones' => Auth::user()->direcciones()->with(['municipio', 'departamento'])->get(),
            'tipoPagos' => Auth::user()->tipo_pagos,
        ]);
    }

    public function storeOrden(Request $request)
    {
        DB::transaction(function () use ($request) {
            $request->validate([
                'direccion' => 'required',
                'tipoPago' => 'required',
            ]);

            $carrito = Carrito::where('user_id', Auth::user()->id)->get();

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
                'asesor_id' => Auth::user()->id,
                'cliente_id' => Auth::user()->id,
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
            activity()->performedOn($orden)->causedBy(Auth::user())->withProperties($orden)->event('created')->log('Orden creada en línea');

            foreach ($carrito as $item) {
                $detalle = OrdenDetalle::create([
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'orden_id' => $orden->id,
                    'producto_id' => $item->producto_id,
                ]);
                activity()->performedOn($detalle)->causedBy(Auth::user())->withProperties($detalle)->event('created')->log('Detalle de Orden en línea');
            }
            Carrito::where('user_id', Auth::user()->id)->delete();
            activity()->performedOn($orden)->causedBy(Auth::user())->withProperties($orden)->event('deleted')->log('Carrito Eliminado');
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

        $user = Auth::user();
        $esAdmin = $user && $user->hasAnyRole(['administrador', 'super_admin']);

        $productos = Producto::with('marca', 'inventario')
            ->whereHas('inventario', function ($query) use ($bodega) {
                $query->where('existencia', '>', 0)
                    ->whereNotIn('bodega_id', [6, 7]);

                if ($bodega) {
                    // Si se selecciona una bodega, buscar en todas las bodegas que contengan el nombre del municipio
                    $bodegaSeleccionada = Bodega::with('municipio')->find($bodega);
                    if ($bodegaSeleccionada && $bodegaSeleccionada->municipio) {
                        $nombreMunicipio = strtolower($bodegaSeleccionada->municipio->municipio);
                        $query->whereHas('bodega', function ($q) use ($nombreMunicipio) {
                            $q->whereNotIn('bodega', ['Mal estado', 'Traslado', 'Central Bodega'])
                                ->whereNotIn('id', [6, 7])
                                ->where(function ($subQuery) use ($nombreMunicipio) {
                                    $subQuery->whereRaw('LOWER(bodega) LIKE ?', ["%{$nombreMunicipio}%"])
                                        ->orWhereRaw('LOWER(bodega) LIKE ?', ["%{$nombreMunicipio} bodega%"]);
                                });
                        });
                    } else {
                        $query->where('bodega_id', $bodega);
                    }
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

        $marchamo = $request->marchamo ? mb_strtolower($request->marchamo) : null;

        if ($esAdmin && $marchamo && in_array($marchamo, ['rojo', 'naranja', 'celeste', 'amarillo'], true)) {
            $productos->where('marchamo', $marchamo);
        }

        // Filtro para productos ofertados
        $ofertados = $request->ofertados;
        if ($ofertados !== null) {
            if ($ofertados === 'con_oferta') {
                $productos->where('precio_oferta', '>', 0);
            } elseif ($ofertados === 'sin_oferta') {
                $productos->where(function ($query) {
                    $query->whereNull('precio_oferta')
                        ->orWhere('precio_oferta', '<=', 0);
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
                $user = Auth::user();

                return [
                    'id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'slug' => $producto->slug,
                    'descripcion' => $producto->descripcion,
                    'precio' => $producto->precio_venta ?? null,
                    'precio_oferta' => $producto->precio_oferta && $producto->precio_oferta > 0 ? $producto->precio_oferta : null,
                    'modelo' => $producto->modelo ?? null,
                    'talla' => $producto->talla ?? null,
                    'color' => $producto->color ?? null,
                    'genero' => $producto->genero ?? null,
                    'stock' => $producto->inventario ? $producto->inventario->sum('existencia') : 0,
                    'imagen' => isset($producto->imagenes[0])
                        ? config('filesystems.disks.s3.url').$producto->imagenes[0]
                        : asset('images/icono.png'),
                    'marca' => $producto->marca->marca ?? null,

                    // ✅ Agregar detalle de bodegas solo si está logueado, agrupadas por municipio
                    'bodegas' => $user
                        ? ($producto->inventario
                            ? $producto->inventario->filter(function ($inv) {
                                $bodega = $inv->bodega;
                                if (! $bodega) {
                                    return false;
                                }

                                // Excluir bodegas específicas que no deben mostrar existencia
                                if (in_array($bodega->bodega, ['Mal estado', 'Traslado']) || in_array($bodega->id, [6, 7])) {
                                    return false;
                                }

                                // Incluir Central Bodega o bodegas que estén en Zacapa, Chiquimula o Esquipulas
                                if ($bodega->bodega === 'Central Bodega') {
                                    return true;
                                }

                                $municipio = $bodega->municipio;
                                if (! $municipio) {
                                    return false;
                                }

                                return in_array(strtolower($municipio->municipio), ['zacapa', 'chiquimula', 'esquipulas']);
                            })
                                ->groupBy(function ($inv) {
                                    // Si es Central Bodega, agrupar con "*", sino usar el municipio
                                    if ($inv->bodega->bodega === 'Central Bodega') {
                                        return '*';
                                    }

                                    return $inv->bodega->municipio->municipio ?? 'Desconocida';
                                })
                                ->map(function ($inventarios, $municipio) {
                                    $totalExistencia = $inventarios->sum('existencia');

                                    return [
                                        'bodega' => $municipio,
                                        'existencia' => $totalExistencia,
                                    ];
                                })
                                ->values()
                                ->toArray()
                            : null)
                        : null,
                ];
            });

        // Obtener bodegas agrupadas por municipio, excluyendo las que no deben mostrar existencia
        $bodegas = Bodega::with('municipio')
            ->whereNotIn('bodega', ['Mal estado', 'Traslado'])
            ->whereNotIn('id', [6, 7])
            ->whereHas('municipio', function ($query) {
                $query->whereIn('municipio', ['Zacapa', 'Chiquimula', 'Esquipulas']);
            })
            ->get(['id', 'bodega', 'municipio_id'])
            ->groupBy('municipio.municipio')
            ->map(function ($bodegasDelMunicipio, $municipio) {
                // Tomar la primera bodega del municipio como representante
                $primeraBodega = $bodegasDelMunicipio->first();

                return [
                    'id' => $primeraBodega->id,
                    'bodega' => $municipio,
                    'municipio_id' => $primeraBodega->municipio_id,
                ];
            })
            ->values();

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
            'ofertados' => $ofertados,
            'tallasDisponibles' => $tallasDisponibles,
            'marcasDisponibles' => $marcasDisponibles,
            'coloresDisponibles' => $colores,
            'generosDisponibles' => $generosDisponibles,
            'marchamo' => $marchamo,
            'puedeVerMarchamo' => $esAdmin,
            'marchamosDisponibles' => ['rojo', 'naranja', 'celeste', 'amarillo'],
        ]);
    }

    public function producto($slug)
    {
        $producto = Producto::where('slug', $slug)->first();

        // Verificar si el producto existe, si no existe devolver 404
        if (! $producto) {
            abort(404, 'Producto no encontrado');
        }

        $marcas = Marca::whereHas('productos', function ($q) {
            $q->whereHas('inventario', function ($q2) {
                $q2->where('existencia', '>', 0);
            });
        })
            ->orderBy('marca')
            ->pluck('marca');

        return Inertia::render('Producto', [
            'producto' => [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
                'slug' => $producto->slug,
                'descripcion' => $producto->descripcion,
                'precio' => $producto->precio_venta,
                'precio_oferta' => $producto->precio_oferta && $producto->precio_oferta > 0 ? $producto->precio_oferta : null,
                'genero' => $producto->genero,
                'modelo' => $producto->modelo,
                'talla' => $producto->talla,
                'stock' => $producto->inventario ? $producto->inventario->sum('existencia') : 0,
                'imagen' => isset($producto->imagenes[0])
                    ? config('filesystems.disks.s3.url').$producto->imagenes[0]
                    : asset('images/icono.png'),
                'marca' => $producto->marca?->marca,

                // Mostrar todas las bodegas solo si está logueado, agrupadas por municipio
                'bodegas' => Auth::check()
                    ? ($producto->inventario
                        ? $producto->inventario->filter(function ($inv) {
                            $bodega = $inv->bodega;
                            if (! $bodega) {
                                return false;
                            }

                            // Excluir bodegas específicas que no deben mostrar existencia
                            if (in_array($bodega->bodega, ['Mal estado', 'Traslado'])) {
                                return false;
                            }

                            // Incluir Central Bodega o bodegas que estén en Zacapa, Chiquimula o Esquipulas
                            if ($bodega->bodega === 'Central Bodega') {
                                return true;
                            }

                            $municipio = $bodega->municipio;
                            if (! $municipio) {
                                return false;
                            }

                            return in_array(strtolower($municipio->municipio), ['zacapa', 'chiquimula', 'esquipulas']);
                        })
                            ->groupBy(function ($inv) {
                                // Si es Central Bodega, agrupar con "*", sino usar el municipio
                                if ($inv->bodega->bodega === 'Central Bodega') {
                                    return '*';
                                }

                                return $inv->bodega->municipio->municipio ?? 'Desconocida';
                            })
                            ->map(function ($inventarios, $municipio) {
                                $totalExistencia = $inventarios->sum('existencia');

                                return [
                                    'bodega' => $municipio,
                                    'existencia' => $totalExistencia,
                                ];
                            })
                            ->values()
                            ->toArray()
                        : null)
                    : null,

                // Siempre enviar la bodega con más stock (Zacapa, Chiquimula y Esquipulas, excluyendo Mal estado y Traslado)
                'bodega_destacada' => $producto->inventario
                    ? $producto->inventario->filter(function ($inv) {
                        $bodega = $inv->bodega;
                        if (! $bodega) {
                            return false;
                        }

                        // Excluir bodegas específicas que no deben mostrar existencia
                        if (in_array($bodega->bodega, ['Mal estado', 'Traslado']) || in_array($bodega->id, [6, 7])) {
                            return false;
                        }

                        // Incluir Central Bodega o bodegas que estén en Zacapa, Chiquimula o Esquipulas
                        if ($bodega->bodega === 'Central Bodega') {
                            return true;
                        }

                        $municipio = $bodega->municipio;
                        if (! $municipio) {
                            return false;
                        }

                        return in_array(strtolower($municipio->municipio), ['zacapa', 'chiquimula', 'esquipulas']);
                    })
                        ->sortByDesc('existencia')
                        ->map(fn ($inv) => [
                            'bodega' => $inv->bodega->bodega === 'Central Bodega' ? '*' : ($inv->bodega->municipio->municipio ?? 'Desconocida'), // Mostrar "*" para Central Bodega, sino el municipio
                            'existencia' => $inv->existencia,
                        ])
                        ->first()
                    : null,
            ],
            'marcas' => $marcas,
        ]);

    }

    public function agregarCarrito(Request $request)
    {
        $producto = Producto::findOrFail($request->producto_id);
        $carritoItem = Carrito::where('user_id', Auth::user()->id)
            ->where('producto_id', $producto->id)
            ->first();

        if ($carritoItem) {
            $carritoItem->cantidad += 1;
            $carritoItem->save();
        } else {
            Carrito::create([
                'user_id' => Auth::user()->id,
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
        $carritoItem = Carrito::where('user_id', Auth::user()->id)
            ->where('producto_id', $id)
            ->first();

        if ($carritoItem) {
            $carritoItem->cantidad += 1;
            $carritoItem->save();
        } else {
            $producto = Producto::findOrFail($id);
            Carrito::create([
                'user_id' => Auth::user()->id,
                'producto_id' => $producto->id,
                'cantidad' => 1,
            ]);
        }
    }

    public function restarCarrito($id)
    {
        $carritoItem = Carrito::where('user_id', Auth::user()->id)
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
        $carritoItem = Carrito::where('user_id', Auth::user()->id)
            ->where('producto_id', $id)
            ->first();

        if ($carritoItem) {
            $carritoItem->delete();
        }
    }

    public function exportarPdf(Request $request)
    {
        $search = $request->search;
        $marca = $request->marca;
        $bodega = $request->bodega;
        $tallas = $request->tallas ?? [];
        $genero = $request->genero;

        $user = Auth::user();
        $esAdmin = $user && $user->hasAnyRole(['administrador', 'super_admin']);

        $marchamo = $request->marchamo;

        $productos = Producto::with('marca', 'inventario')
            ->whereHas('inventario', function ($query) use ($bodega) {
                $query->where('existencia', '>', 0)
                    ->whereNotIn('bodega_id', [6, 7]);

                if ($bodega) {
                    // Si se selecciona una bodega, buscar en todas las bodegas que contengan el nombre del municipio
                    $bodegaSeleccionada = Bodega::with('municipio')->find($bodega);
                    if ($bodegaSeleccionada && $bodegaSeleccionada->municipio) {
                        $nombreMunicipio = strtolower($bodegaSeleccionada->municipio->municipio);
                        $query->whereHas('bodega', function ($q) use ($nombreMunicipio) {
                            $q->whereNotIn('bodega', ['Mal estado', 'Traslado', 'Central Bodega'])
                                ->whereNotIn('id', [6, 7])
                                ->where(function ($subQuery) use ($nombreMunicipio) {
                                    $subQuery->whereRaw('LOWER(bodega) LIKE ?', ["%{$nombreMunicipio}%"])
                                        ->orWhereRaw('LOWER(bodega) LIKE ?', ["%{$nombreMunicipio} bodega%"]);
                                });
                        });
                    } else {
                        $query->where('bodega_id', $bodega);
                    }
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

        $marchamo = $request->marchamo ? mb_strtolower($request->marchamo) : null;

        if ($esAdmin && $marchamo && in_array($marchamo, ['rojo', 'naranja', 'celeste', 'amarillo'], true)) {
            $productos->where('marchamo', $marchamo);
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

        if ($genero) {
            $productos->where('genero', $genero);
        }

        // Filtro para productos ofertados
        $ofertados = $request->ofertados;
        if ($ofertados !== null) {
            if ($ofertados === 'con_oferta') {
                $productos->where('precio_oferta', '>', 0);
            } elseif ($ofertados === 'sin_oferta') {
                $productos->where(function ($query) {
                    $query->whereNull('precio_oferta')
                        ->orWhere('precio_oferta', '<=', 0);
                });
            }
        }

        $productos = $productos
            ->with('marca:id,marca')
            ->limit(150)
            ->get();

        $pdf = Pdf::loadView('pdf.catalogo-filtro', compact('productos'))
            ->setPaper([0, 0, 227, 842], 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'enable-javascript' => false,
                'debugCss' => false,
                'dpi' => 96,
            ]);

        // Abrir en navegador
        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Catalogo.pdf"')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function HistorialVendidosPdf(Request $request)
    {
        $search = $request->search;
        $marca = $request->marca;
        $bodega = $request->bodega;
        $tallas = $request->tallas ?? [];
        $genero = $request->genero;

        $user = Auth::user();
        $esAdmin = $user && $user->hasAnyRole(['administrador', 'super_admin']);

        $marchamo = $request->marchamo
            ? mb_strtolower($request->marchamo)
            : null;

        $vendidos = VentaDetalle::query()
            ->with([
                'producto.marca:id,marca',
                'venta.bodega:id,bodega',
            ]);

        /* 🔍 FILTRO BODEGA (DESDE LA VENTA) */
        if ($bodega) {
            $vendidos->whereHas('venta', function ($q) use ($bodega) {
                $q->where('bodega_id', $bodega);
            });
        }

        /* 🔍 BÚSQUEDA GENERAL */
        if ($search) {
            $terms = explode(' ', $search);

            foreach ($terms as $term) {
                $vendidos->whereHas('producto', function ($q) use ($term) {
                    $q->where('codigo', 'LIKE', "%{$term}%")
                        ->orWhere('descripcion', 'LIKE', "%{$term}%")
                        ->orWhere('modelo', 'LIKE', "%{$term}%")
                        ->orWhere('talla', 'LIKE', "%{$term}%")
                        ->orWhere('genero', 'LIKE', "%{$term}%")
                        ->orWhereHas('marca', fn ($m) => $m->where('marca', 'LIKE', "%{$term}%")
                        );
                });
            }
        }

        /* 🔍 MARCA */
        if ($marca) {
            $vendidos->whereHas('producto.marca', function ($q) use ($marca) {
                $q->where('marca', $marca);
            });
        }

        /* 🔍 TALLAS */
        if (! empty($tallas)) {
            $tallasNormalizadas = collect($tallas)
                ->map(fn ($t) => rtrim(rtrim($t, '0'), '.'))
                ->unique()
                ->toArray();

            $vendidos->whereHas('producto', function ($q) use ($tallasNormalizadas) {
                $q->whereIn(
                    DB::raw("REPLACE(REPLACE(talla, '.0', ''), '.00', '')"),
                    $tallasNormalizadas
                );
            });
        }

        /* 🔍 GÉNERO */
        if ($genero) {
            $vendidos->whereHas('producto', function ($q) use ($genero) {
                $q->where('genero', $genero);
            });
        }

        /* 🔍 MARCHAMO (SOLO ADMIN) */
        if ($esAdmin && $marchamo && in_array($marchamo, ['rojo', 'naranja', 'celeste', 'amarillo'], true)) {
            $vendidos->whereHas('producto', function ($q) use ($marchamo) {
                $q->where('marchamo', $marchamo);
            });
        }

        $vendidos = $vendidos
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        /* dd($vendidos); */

        $pdf = Pdf::loadView('pdf.historial-productos', compact('vendidos'))
            ->setPaper([0, 0, 227, 842], 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 96,
            ]);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Historial_Vendidos.pdf"');
    }
}
