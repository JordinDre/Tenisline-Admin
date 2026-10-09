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
use App\Services\CatalogoTienda;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Support\Seo;

class TiendaController extends Controller
{
    public function index()
    {
        $tienda = Tienda::first()?->contenido ?? [];
        $hoy = now()->toDateString();

        // Promociones activas y vigentes (se administran en Filament > Tienda)
        $promociones = collect($tienda)
            ->where('type', 'promocion')
            ->pluck('data')
            ->filter(fn ($p) => ($p['activo'] ?? true)
                && (empty($p['desde']) || $p['desde'] <= $hoy)
                && (empty($p['hasta']) || $p['hasta'] >= $hoy))
            ->map(fn ($p) => [
                'titulo' => $p['titulo'] ?? null,
                'subtitulo' => $p['subtitulo'] ?? null,
                'boton' => $p['boton'] ?? null,
                'enlace' => $p['enlace'] ?? null,
                'imagen' => CatalogoTienda::urlImagen([$p['imagen'] ?? null]),
                'imagen_movil' => CatalogoTienda::urlImagen([$p['imagen_movil'] ?? null]),
            ])
            ->filter(fn ($p) => $p['imagen'] || $p['titulo'])
            ->values();

        $destacados = CatalogoTienda::modelos(CatalogoTienda::consulta(['con_imagen' => true]), 'recientes', 0, 8);

        return Inertia::render('Inicio', [
            'seo' => Seo::make(
                'Tenisline | Tenis de marca en Zacapa, Chiquimula y Esquipulas',
                'Nike, adidas, Puma, New Balance, On, Hoka y más a precios bajos. No box, sí precio. Compra por WhatsApp en Zacapa, Chiquimula y Esquipulas, Guatemala.',
                $destacados[0]['imagen'] ?? null,
                url('/'),
                'website',
                true,
                Seo::negocio(),
            ),
            'promociones' => $promociones,
            'categorias' => CatalogoTienda::categorias(),
            'marcas' => CatalogoTienda::marcas(),
            'ofertas' => CatalogoTienda::modelos(CatalogoTienda::consulta(['ofertas' => true]), 'recientes', 0, 12),
            'novedades' => CatalogoTienda::modelos(CatalogoTienda::consulta(), 'recientes', 0, 12),
            // Modelos con foto real, para la portada y la sección destacada
            'destacados' => $destacados,
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
        $filtros = $request->only(['search', 'marca', 'categoria', 'genero', 'bodega', 'tallas', 'precioMin', 'precioMax', 'ofertas', 'marchamo']);
        $orden = $request->input('orden', 'recientes');

        $categorias = config('tienda.categorias');
        $etiqueta = $categorias[$filtros['categoria'] ?? '']['label'] ?? null;
        $marca = $filtros['marca'] ?? null;
        $titulo = ($etiqueta || $marca)
            ? trim('Tenis '.($etiqueta ? 'de '.$etiqueta.' ' : '').($marca ? $marca.' ' : '')).' en Guatemala'
            : 'Catálogo de tenis en Guatemala';
        $titulo = ($etiqueta === 'Ofertas' ? 'Ofertas en tenis' : $titulo).' | Tenisline';
        // Las búsquedas y los filtros sueltos no se indexan; la categoría y la marca sí
        $soloCategoria = collect($filtros)->except(['categoria', 'marca'])->filter()->isEmpty();
        $canonical = url('/catalogo').($soloCategoria && ($etiqueta || $marca) ? '?'.http_build_query(array_filter(['categoria' => $filtros['categoria'] ?? null, 'marca' => $marca])) : '');

        return Inertia::render('Catalogo', [
            'seo' => Seo::make(
                $titulo,
                'Catálogo de tenis '.($marca ? $marca.' ' : '').($etiqueta ? 'para '.strtolower($etiqueta).' ' : '').'en Zacapa, Chiquimula y Esquipulas. Modelos originales sin caja a precios que no encuentras en otro lado.',
                null, $canonical, 'website', $soloCategoria,
            ),
            'productos' => CatalogoTienda::modelos(CatalogoTienda::consulta($filtros), $orden),
            'filtros' => [...$filtros, 'orden' => $orden, 'tallas' => array_values((array) ($filtros['tallas'] ?? []))],
            'marcas' => CatalogoTienda::marcas(),
            'categorias' => CatalogoTienda::categorias(),
            'bodegas' => CatalogoTienda::bodegas(),
            'tallas' => CatalogoTienda::tallasDisponibles(),
            'puedeVerMarchamo' => CatalogoTienda::esAdmin(),
        ]);
    }

    /** Mapa del sitio para Google: páginas fijas, categorías, marcas y cada modelo con existencia. */
    public function sitemap()
    {
        $xml = \Illuminate\Support\Facades\Cache::remember('tienda:sitemap', 3600, function () {
            $urls = collect(['/', '/catalogo', '/marcas'])
                ->merge(collect(array_keys(config('tienda.categorias')))->map(fn ($c) => '/catalogo?categoria='.$c))
                ->merge(CatalogoTienda::marcas()->map(fn ($m) => '/catalogo?marca='.rawurlencode($m['marca'])))
                ->map(fn ($u) => ['loc' => url($u), 'prio' => $u === '/' ? '1.0' : '0.7', 'mod' => null, 'img' => null]);

            $modelos = CatalogoTienda::modelos(CatalogoTienda::consulta(), 'recientes', 0, 5000)
                ->filter(fn ($m) => ! empty($m['slug']))
                ->map(fn ($m) => ['loc' => url('/producto/'.$m['slug']), 'prio' => '0.8', 'mod' => null, 'img' => $m['imagen'] ?? null]);

            $filas = $urls->merge($modelos)->map(fn ($u) => '<url><loc>'.e($u['loc']).'</loc><priority>'.$u['prio'].'</priority>'
                .($u['img'] ? '<image:image><image:loc>'.e($u['img']).'</image:loc></image:image>' : '').'</url>')->implode('');

            return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'.$filas.'</urlset>';
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function marcas()
    {
        return Inertia::render('Marcas', [
            'seo' => Seo::make('Marcas de tenis | Tenisline', 'Nike, adidas, Puma, New Balance, On, Hoka, Saucony, Skechers, Reebok y más marcas en Tenisline, con envío y entrega en Zacapa, Chiquimula y Esquipulas.'),
            'marcas' => CatalogoTienda::marcas(),
        ]);
    }

    public function producto($slug)
    {
        $esAdmin = CatalogoTienda::esAdmin();
        $producto = Producto::with('marca')->where('slug', $slug)->firstOrFail();

        // Todas las tallas del mismo modelo con existencia visible, cada una con su existencia por sucursal
        $variantes = CatalogoTienda::consulta()
            ->with(['inventario' => fn ($q) => $q->where('existencia', '>', 0)
                ->whereHas('bodega', fn ($b) => $esAdmin ? $b : $b->whereNotIn('bodega', CatalogoTienda::BODEGAS_OCULTAS)),
                'inventario.bodega.municipio'])
            ->where('marca_id', $producto->marca_id)
            ->where('descripcion', $producto->descripcion)
            ->where(fn ($q) => $producto->color === null ? $q->whereNull('color') : $q->where('color', $producto->color))
            ->where('genero', $producto->genero)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'slug' => $v->slug,
                'codigo' => $v->codigo,
                'talla' => rtrim(rtrim((string) $v->talla, '0'), '.') ?: $v->talla,
                'precio' => (float) $v->precio_venta,
                'precio_oferta' => $v->precio_oferta > 0 ? (float) $v->precio_oferta : null,
                'imagen' => CatalogoTienda::urlImagen($v->imagenes),
                'sucursales' => $v->inventario
                    ->groupBy(fn ($i) => $i->bodega?->municipio?->municipio ?? 'Otra')
                    ->filter(fn ($g, $m) => $esAdmin || in_array(strtolower($m), CatalogoTienda::SUCURSALES))
                    ->map(fn ($g, $m) => ['sucursal' => $m, 'existencia' => (int) $g->sum('existencia')])
                    ->values(),
            ])
            ->sortBy(fn ($v) => (float) $v['talla'])
            ->values();

        $imagenes = collect($producto->imagenes ?? [])->map(fn ($i) => CatalogoTienda::urlImagen([$i]))->filter()->values();
        if ($imagenes->isEmpty()) {
            $imagenes = $variantes->pluck('imagen')->filter()->unique()->values();
        }

        $nombre = trim(($producto->marca?->marca ? ucfirst(strtolower($producto->marca->marca)).' ' : '').ucwords(strtolower(trim($producto->descripcion))).($producto->color ? ' '.ucfirst(strtolower($producto->color)) : ''));
        $precios = $variantes->map(fn ($v) => $v['precio_oferta'] ?? $v['precio'])->filter();
        $precio = $precios->min();
        $enlace = url('/producto/'.$producto->slug);
        $fotosSeo = $imagenes->values();
        $foto = $fotosSeo->first();

        return Inertia::render('Producto', [
            'seo' => Seo::make(
                $nombre.' | Tenisline',
                $nombre.($precio ? ' desde Q'.number_format($precio, 2) : '').'. Tallas disponibles en Zacapa, Chiquimula y Esquipulas. No box, sí precio. Pide por WhatsApp.',
                $foto, $enlace, 'product', true,
                array_values(array_filter([
                    $precio ? [
                        '@context' => 'https://schema.org', '@type' => 'Product',
                        'name' => $nombre, 'sku' => $producto->codigo,
                        'image' => $fotosSeo->isNotEmpty() ? $fotosSeo->all() : [url('/images/logo.png')], 'color' => $producto->color,
                        'brand' => ['@type' => 'Brand', 'name' => $producto->marca?->marca],
                        'description' => $nombre.'. Tenis disponibles en tiendas Tenisline.',
                        'offers' => [
                            '@type' => 'AggregateOffer', 'priceCurrency' => 'GTQ',
                            'lowPrice' => $precio, 'highPrice' => $precios->max(), 'offerCount' => $variantes->count(),
                            'availability' => $variantes->isNotEmpty() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                            'url' => $enlace, 'itemCondition' => 'https://schema.org/NewCondition',
                        ],
                    ] : null,
                    Seo::migas([['Inicio', url('/')], ['Catálogo', url('/catalogo')], [$nombre, $enlace]]),
                ])),
            ),
            'producto' => [
                'id' => $producto->id,
                'slug' => $producto->slug,
                'codigo' => $producto->codigo,
                'descripcion' => trim($producto->descripcion),
                'marca' => $producto->marca?->marca,
                'color' => $producto->color,
                'genero' => $producto->genero,
                'imagenes' => $imagenes,
            ],
            'variantes' => $variantes,
            'mostrarExistencia' => Auth::check(),
            'relacionados' => CatalogoTienda::modelos(
                CatalogoTienda::consulta(['marca' => $producto->marca?->marca])
                    ->where('descripcion', '!=', $producto->descripcion),
                'recientes', 0, 8
            ),
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

        $productos = Producto::with([
            'marca',
            'inventario' => function ($query) use ($esAdmin) {
                $query->where('existencia', '>', 0);
                if (!$esAdmin) {
                    $query->whereHas('bodega', function ($q) {
                        $q->whereNotIn('bodega', ['Central Bodega', 'Traslado']);
                    });
                }
            }
        ])
            ->whereHas('inventario', function ($query) use ($bodega, $esAdmin) {
                $query->where('existencia', '>', 0);
                if (!$esAdmin) {
                    $query->whereHas('bodega', function ($q) {
                        $q->whereNotIn('bodega', ['Central Bodega', 'Traslado']);
                    });
                }

                if ($bodega) {
                    // Si se selecciona una bodega, buscar en todas las bodegas que contengan el nombre del municipio
                    $bodegaSeleccionada = Bodega::with('municipio')->find($bodega);
                    if ($bodegaSeleccionada && $bodegaSeleccionada->municipio) {
                        $nombreMunicipio = strtolower($bodegaSeleccionada->municipio->municipio);
                        $query->whereHas('bodega', function ($q) use ($nombreMunicipio, $esAdmin) {
                            if (!$esAdmin) {
                                $q->whereNotIn('bodega', ['Mal estado', 'Traslado', 'Central Bodega']);
                            }
                            $q->where(function ($subQuery) use ($nombreMunicipio) {
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
