<?php

namespace App\Services;

use App\Models\Bodega;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Consultas del sitio público. Los productos se agrupan por modelo
 * (marca + descripción + color + género) para mostrar una tarjeta por
 * modelo con todas sus tallas disponibles, en lugar de una por talla.
 */
class CatalogoTienda
{
    /** Bodegas que nunca se muestran al público. */
    public const BODEGAS_OCULTAS = ['Central Bodega', 'Traslado', 'Mal estado'];

    public const SUCURSALES = ['zacapa', 'chiquimula', 'esquipulas'];

    public static function esAdmin(): bool
    {
        $user = Auth::user();

        return $user && $user->hasAnyRole(['administrador', 'super_admin']);
    }

    /** Productos con existencia visible, con los filtros del catálogo aplicados. */
    public static function consulta(array $f = []): Builder
    {
        $esAdmin = self::esAdmin();

        $q = Producto::query()->whereHas('inventario', function ($inv) use ($esAdmin, $f) {
            $inv->where('existencia', '>', 0)
                ->whereHas('bodega', function ($b) use ($esAdmin, $f) {
                    if (! $esAdmin) {
                        $b->whereNotIn('bodega', self::BODEGAS_OCULTAS);
                    }
                    if (! empty($f['bodega'])) {
                        $bodega = Bodega::with('municipio')->find($f['bodega']);
                        $municipio = strtolower($bodega?->municipio?->municipio ?? '');
                        $municipio
                            ? $b->whereRaw('LOWER(bodega) LIKE ?', ["%{$municipio}%"])
                            : $b->where('id', $f['bodega']);
                    }
                });
        });

        if (! empty($f['search'])) {
            foreach (preg_split('/\s+/', trim($f['search'])) as $term) {
                $q->where(fn ($w) => $w->where('productos.codigo', 'like', "%{$term}%")
                    ->orWhere('productos.descripcion', 'like', "%{$term}%")
                    ->orWhere('productos.modelo', 'like', "%{$term}%")
                    ->orWhere('productos.color', 'like', "%{$term}%")
                    ->orWhereHas('marca', fn ($m) => $m->where('marca', 'like', "%{$term}%")));
            }
        }

        if (! empty($f['con_imagen'])) {
            $q->whereNotNull('productos.imagenes')->whereNotIn('productos.imagenes', ['[]', 'null', '']);
        }

        if (! empty($f['marca'])) {
            $q->whereHas('marca', fn ($m) => $m->where('marca', $f['marca']));
        }

        $categoria = config('tienda.categorias.'.($f['categoria'] ?? ''));
        if ($categoria) {
            isset($categoria['genero']) && $q->where('genero', $categoria['genero']);
            ! empty($categoria['ofertas']) && $q->where('precio_oferta', '>', 0);
        }
        if (! empty($f['genero'])) {
            $q->where('genero', $f['genero']);
        }
        if (! empty($f['ofertas'])) {
            $q->where('precio_oferta', '>', 0);
        }

        if (! empty($f['tallas'])) {
            $tallas = collect((array) $f['tallas'])->map(fn ($t) => rtrim(rtrim((string) $t, '0'), '.'))->unique()->values()->all();
            $q->whereIn(DB::raw("REPLACE(REPLACE(productos.talla, '.00', ''), '.0', '')"), $tallas);
        }

        if (! empty($f['precioMin'])) {
            $q->where('precio_venta', '>=', (float) $f['precioMin']);
        }
        if (! empty($f['precioMax'])) {
            $q->where('precio_venta', '<=', (float) $f['precioMax']);
        }

        if ($esAdmin && ! empty($f['marchamo']) && in_array($f['marchamo'], ['rojo', 'naranja', 'celeste', 'amarillo', 'blanco'], true)) {
            $q->where('marchamo', $f['marchamo']);
        }

        return $q;
    }

    /** Agrupa la consulta por modelo y la pagina (o la limita si $limite > 0). */
    public static function modelos(Builder $q, string $orden = 'recientes', int $porPagina = 24, int $limite = 0)
    {
        $q->select(DB::raw("
                MIN(productos.id) AS id,
                MIN(productos.codigo) AS codigo,
                productos.marca_id, productos.descripcion, productos.color, productos.genero,
                MIN(productos.precio_venta) AS precio,
                MIN(NULLIF(productos.precio_oferta, 0)) AS precio_oferta,
                GROUP_CONCAT(DISTINCT REPLACE(REPLACE(productos.talla, '.00', ''), '.0', '') ORDER BY productos.talla + 0 SEPARATOR ',') AS tallas,
                MAX(NULLIF(NULLIF(productos.imagenes, '[]'), 'null')) AS imagenes,
                MAX(productos.created_at) AS creado
            "))
            ->groupBy('productos.marca_id', 'productos.descripcion', 'productos.color', 'productos.genero');

        match ($orden) {
            'precio_asc' => $q->orderByRaw('COALESCE(MIN(NULLIF(productos.precio_oferta, 0)), MIN(productos.precio_venta)) ASC'),
            'precio_desc' => $q->orderByRaw('COALESCE(MIN(NULLIF(productos.precio_oferta, 0)), MIN(productos.precio_venta)) DESC'),
            'nombre' => $q->orderBy('productos.descripcion'),
            default => $q->orderByDesc('creado'),
        };

        if ($limite > 0) {
            return self::formatear($q->limit($limite)->get());
        }

        $pagina = $q->paginate($porPagina)->withQueryString();
        $pagina->setCollection(self::formatear($pagina->getCollection()));

        return $pagina;
    }

    /** Convierte filas agrupadas al formato que usa el frontend. */
    public static function formatear(Collection $filas): Collection
    {
        $marcas = Marca::withTrashed()->pluck('marca', 'id');
        $slugs = Producto::whereIn('id', $filas->pluck('id'))->pluck('slug', 'id');

        return $filas->map(fn ($p) => [
            'id' => $p->id,
            'slug' => $slugs[$p->id] ?? null,
            'codigo' => $p->codigo,
            'descripcion' => trim($p->descripcion),
            'color' => $p->color,
            'genero' => $p->genero,
            'marca' => $marcas[$p->marca_id] ?? null,
            'precio' => (float) $p->precio,
            'precio_oferta' => $p->precio_oferta ? (float) $p->precio_oferta : null,
            'tallas' => self::normalizarTallas(explode(',', (string) $p->tallas))->all(),
            'imagen' => self::urlImagen($p->imagenes),
        ])->values();
    }

    /** URL pública de la primera imagen, o null para mostrar el logo de Tenisline. */
    public static function urlImagen($imagenes): ?string
    {
        $lista = is_array($imagenes) ? $imagenes : (json_decode((string) $imagenes, true) ?: []);

        return isset($lista[0]) ? rtrim(config('filesystems.disks.s3.url'), '/').'/'.ltrim($lista[0], '/') : null;
    }

    /** Marcas con existencia, con su conteo de modelos y logo. */
    public static function marcas(): Collection
    {
        return Cache::remember('tienda:marcas', 300, function () {
            $logos = config('tienda.logos_marcas');

            return self::consulta()
                ->join('marcas', 'marcas.id', '=', 'productos.marca_id')
                ->whereNull('marcas.deleted_at')
                ->whereNotIn('marcas.marca', config('tienda.marcas_ocultas', []))
                ->select('marcas.marca', DB::raw("COUNT(DISTINCT productos.descripcion, IFNULL(productos.color, '')) AS modelos"))
                ->groupBy('marcas.marca')
                ->orderByDesc('modelos')
                ->get()
                ->map(fn ($m) => [
                    'marca' => $m->marca,
                    'modelos' => (int) $m->modelos,
                    'logo' => isset($logos[$m->marca]) ? asset('images/marcas/'.$logos[$m->marca]) : null,
                ]);
        });
    }

    /** Categorías con su conteo de modelos. */
    public static function categorias(): Collection
    {
        return Cache::remember('tienda:categorias', 300, fn () => collect(config('tienda.categorias'))
            ->map(fn ($c, $clave) => [
                'clave' => $clave,
                'label' => $c['label'],
                'modelos' => self::consulta(['categoria' => $clave])
                    ->distinct()->count(DB::raw('CONCAT(productos.marca_id, productos.descripcion, IFNULL(productos.color, ""))')),
            ])
            ->filter(fn ($c) => $c['modelos'] > 0)
            ->values());
    }

    /** Sucursales para el filtro de disponibilidad. */
    public static function bodegas(): Collection
    {
        return Cache::remember('tienda:bodegas', 300, fn () => Bodega::with('municipio')
            ->whereNotIn('bodega', self::BODEGAS_OCULTAS)
            ->get()
            ->filter(fn ($b) => in_array(strtolower($b->municipio?->municipio ?? ''), self::SUCURSALES))
            ->groupBy(fn ($b) => $b->municipio->municipio)
            ->map(fn ($grupo, $municipio) => ['id' => $grupo->first()->id, 'bodega' => $municipio])
            ->values());
    }

    /** Tallas disponibles (para el filtro). */
    public static function tallasDisponibles(): Collection
    {
        return Cache::remember('tienda:tallas', 300, fn () => self::consulta()
            ->select(DB::raw("DISTINCT REPLACE(REPLACE(productos.talla, '.00', ''), '.0', '') AS t"))
            ->pluck('t')
            ->pipe(fn ($t) => self::normalizarTallas($t->all())));
    }

    /** "7.0", " 7" y "7" son la misma talla; descarta valores que no son tallas (ej. "650ML"). */
    public static function normalizarTallas(array $tallas): Collection
    {
        return collect($tallas)
            ->map(fn ($t) => trim((string) $t))
            ->filter(fn ($t) => preg_match('/^\d+(\.\d+)?$/', $t))
            ->map(fn ($t) => (string) (float) $t)
            ->unique()
            ->sortBy(fn ($t) => (float) $t)
            ->values();
    }
}
