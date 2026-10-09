import Logo from '@/Components/tienda/Logo';
import ProductoCard from '@/Components/tienda/ProductoCard';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import { Switch } from '@/Components/ui/switch';
import Layout from '@/Layouts/Layout';
import { capitalizar } from '@/lib/tienda';
import { cn } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { ArrowUpDown, ChevronLeft, ChevronRight, SlidersHorizontal, X } from 'lucide-react';
import { useEffect, useState } from 'react';

const contenedor = 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8';

const ORDENES = {
    recientes: 'Más recientes',
    precio_asc: 'Precio: menor a mayor',
    precio_desc: 'Precio: mayor a menor',
    nombre: 'Nombre A-Z',
};

const RANGOS = [
    { label: 'Hasta Q500', min: null, max: 500 },
    { label: 'Q500 – Q800', min: 500, max: 800 },
    { label: 'Q800 – Q1,200', min: 800, max: 1200 },
    { label: 'Más de Q1,200', min: 1200, max: null },
];

const MARCHAMOS = ['rojo', 'naranja', 'celeste', 'amarillo', 'blanco'];

const limpiar = (obj) =>
    Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== null && v !== undefined && v !== '' && !(Array.isArray(v) && !v.length)));

const mismo = (a, b) => String(a ?? '') === String(b ?? '');

const pastilla = (activa) =>
    cn('inline-flex h-9 items-center rounded-full border px-4 text-sm font-medium transition-all duration-300 active:scale-95',
        activa ? 'border-ink bg-ink text-white ' : 'border-neutral-200 bg-white hover:border-neutral-400');

function Grupo({ titulo, children }) {
    return (
        <div className="border-b border-neutral-200 py-6 first:pt-0 last:border-0">
            <h3 className="mb-3.5 text-[11px] font-bold uppercase tracking-[0.18em] text-neutral-500">{titulo}</h3>
            {children}
        </div>
    );
}

function Filtros({ f, set, marcas, categorias, bodegas, tallas, puedeVerMarchamo }) {
    const [precio, setPrecio] = useState({ min: f.precioMin ?? '', max: f.precioMax ?? '' });
    useEffect(() => setPrecio({ min: f.precioMin ?? '', max: f.precioMax ?? '' }), [f.precioMin, f.precioMax]);

    return (
        <div>
            <Grupo titulo="Categoría">
                <div className="flex flex-wrap gap-2">
                    {categorias.map((c) => (
                        <button key={c.clave} onClick={() => set({ categoria: f.categoria === c.clave ? null : c.clave })}
                            className={cn(pastilla(f.categoria === c.clave), c.clave === 'ofertas' && f.categoria !== c.clave && 'text-brand')}>
                            {c.label}
                        </button>
                    ))}
                </div>
            </Grupo>

            <Grupo titulo="Marca">
                <div className="grid max-h-[17rem] grid-cols-2 gap-1.5 overflow-y-auto pr-1 [scrollbar-width:thin]">
                    {marcas.map((m) => (
                        <button key={m.marca} onClick={() => set({ marca: f.marca === m.marca ? null : m.marca })}
                            className={cn('flex h-9 items-center justify-between gap-1 rounded-lg px-3 text-left text-sm transition-colors',
                                f.marca === m.marca ? 'bg-ink text-white' : 'bg-neutral-100 hover:bg-neutral-200')}>
                            <span className="truncate font-medium">{capitalizar(m.marca)}</span>
                            <span className="shrink-0 text-xs tabular-nums opacity-60">{m.modelos}</span>
                        </button>
                    ))}
                </div>
            </Grupo>

            <Grupo titulo="Talla (US)">
                <div className="grid grid-cols-5 gap-1.5">
                    {tallas.map((t) => {
                        const activa = (f.tallas ?? []).includes(t);
                        return (
                            <button key={t} onClick={() => set({ tallas: activa ? f.tallas.filter((x) => x !== t) : [...(f.tallas ?? []), t] })}
                                className={cn('h-9 rounded-lg border text-sm font-medium tabular-nums transition-all duration-300 active:scale-95',
                                    activa ? 'border-ink bg-ink text-white' : 'border-neutral-200 hover:border-neutral-500')}>
                                {t}
                            </button>
                        );
                    })}
                </div>
            </Grupo>

            <Grupo titulo="Precio">
                <div className="flex flex-wrap gap-2">
                    {RANGOS.map((r) => {
                        const activo = mismo(f.precioMin, r.min) && mismo(f.precioMax, r.max);
                        return (
                            <button key={r.label} onClick={() => set(activo ? { precioMin: null, precioMax: null } : { precioMin: r.min, precioMax: r.max })} className={pastilla(activo)}>
                                {r.label}
                            </button>
                        );
                    })}
                </div>
                <form onSubmit={(e) => { e.preventDefault(); set({ precioMin: precio.min || null, precioMax: precio.max || null }); }}
                    className="mt-3 flex items-center gap-2">
                    <input type="number" min="0" inputMode="numeric" placeholder="Mín" value={precio.min} onChange={(e) => setPrecio((p) => ({ ...p, min: e.target.value }))}
                        className="h-9 w-full min-w-0 rounded-lg border-neutral-200 text-sm focus:border-ink focus:ring-ink" />
                    <span className="text-neutral-400">–</span>
                    <input type="number" min="0" inputMode="numeric" placeholder="Máx" value={precio.max} onChange={(e) => setPrecio((p) => ({ ...p, max: e.target.value }))}
                        className="h-9 w-full min-w-0 rounded-lg border-neutral-200 text-sm focus:border-ink focus:ring-ink" />
                    <Button type="submit" size="sm" className="h-9 shrink-0 rounded-lg bg-ink px-3">Ir</Button>
                </form>
            </Grupo>

            {bodegas.length > 0 && (
                <Grupo titulo="Disponible en">
                    <div className="flex flex-wrap gap-2">
                        {bodegas.map((b) => (
                            <button key={b.id} onClick={() => set({ bodega: mismo(f.bodega, b.id) ? null : b.id })} className={pastilla(mismo(f.bodega, b.id))}>
                                {b.bodega}
                            </button>
                        ))}
                    </div>
                </Grupo>
            )}

            <Grupo titulo="Ofertas">
                <label className="flex cursor-pointer items-center justify-between gap-4">
                    <span className="text-sm font-medium">Solo productos en oferta</span>
                    <Switch checked={!!f.ofertas} onCheckedChange={(v) => set({ ofertas: v ? 1 : null })} className="data-[state=checked]:bg-brand" />
                </label>
            </Grupo>

            {puedeVerMarchamo && (
                <Grupo titulo="Marchamo (administradores)">
                    <div className="flex flex-wrap gap-2">
                        {MARCHAMOS.map((m) => (
                            <button key={m} onClick={() => set({ marchamo: f.marchamo === m ? null : m })} className={cn(pastilla(f.marchamo === m), 'capitalize')}>{m}</button>
                        ))}
                    </div>
                </Grupo>
            )}
        </div>
    );
}

export default function Catalogo({ productos, filtros = {}, marcas = [], categorias = [], bodegas = [], tallas = [], puedeVerMarchamo = false }) {
    const [panel, setPanel] = useState(false);
    const [cargando, setCargando] = useState(false);
    const f = filtros;

    useEffect(() => {
        const inicio = router.on('start', () => setCargando(true));
        const fin = router.on('finish', () => setCargando(false));
        return () => { inicio(); fin(); };
    }, []);

    const aplicar = (cambios) =>
        router.get('/catalogo', limpiar({ ...f, ...cambios, page: null }), { preserveState: true, preserveScroll: true, replace: true });

    const categoria = categorias.find((c) => c.clave === f.categoria);
    const titulo = f.search ? `“${f.search}”` : f.marca ? capitalizar(f.marca) : categoria ? categoria.label : 'Catálogo';

    const chips = [
        f.search && { k: 'search', t: `Búsqueda: ${f.search}` },
        categoria && { k: 'categoria', t: categoria.label },
        f.marca && { k: 'marca', t: capitalizar(f.marca) },
        ...(f.tallas ?? []).map((t) => ({ k: 'tallas', v: t, t: `Talla ${t}` })),
        (f.precioMin || f.precioMax) && { k: 'precio', t: RANGOS.find((r) => mismo(r.min, f.precioMin) && mismo(r.max, f.precioMax))?.label ?? `Q${f.precioMin || 0} – Q${f.precioMax || '∞'}` },
        f.bodega && { k: 'bodega', t: bodegas.find((b) => mismo(b.id, f.bodega))?.bodega ?? 'Tienda' },
        f.ofertas && { k: 'ofertas', t: 'En oferta' },
        f.marchamo && { k: 'marchamo', t: `Marchamo ${f.marchamo}` },
    ].filter(Boolean);

    const quitarChip = (c) => {
        if (c.k === 'tallas') return aplicar({ tallas: f.tallas.filter((t) => t !== c.v) });
        if (c.k === 'precio') return aplicar({ precioMin: null, precioMax: null });
        aplicar({ [c.k]: null });
    };

    const props = { f, set: aplicar, marcas, categorias, bodegas, tallas, puedeVerMarchamo };

    return (
        <Layout>

            <section className="relative overflow-hidden border-b bg-neutral-50">
                <div className="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-brand/10 blur-3xl" />
                <div className={cn(contenedor, 'relative py-10 sm:py-12')}>
                    <nav className="text-sm text-neutral-500">
                        <Link href="/" className="transition-colors hover:text-ink">Inicio</Link>
                        <span className="mx-2">/</span>
                        <span className="text-neutral-800">Catálogo</span>
                    </nav>
                    <h1 className="mt-3 animate-fade-up font-display text-4xl uppercase leading-none sm:text-5xl">{titulo}</h1>
                    <p className="mt-3 text-neutral-500"><span className="font-semibold text-ink">{productos.total}</span> {productos.total === 1 ? 'modelo disponible' : 'modelos disponibles'}</p>
                </div>
            </section>

            <div className={cn(contenedor, 'py-8')}>
                <div className="flex gap-10 xl:gap-12">
                    <aside className="hidden w-64 shrink-0 lg:block">
                        <div className="sticky top-28 max-h-[calc(100vh-8rem)] overflow-y-auto pb-6 pr-2 [scrollbar-width:thin]">
                            <Filtros {...props} />
                        </div>
                    </aside>

                    <div className="min-w-0 flex-1">
                        <div className="mb-6 flex flex-wrap items-center gap-2">
                            <Button variant="outline" onClick={() => setPanel(true)} className="h-10 rounded-full border-neutral-200 px-4 font-semibold lg:hidden">
                                <SlidersHorizontal className="h-4 w-4" /> Filtros
                                {chips.length > 0 && <span className="flex h-5 min-w-5 items-center justify-center rounded-full bg-brand px-1 text-[11px] font-bold text-white">{chips.length}</span>}
                            </Button>
                            <div className="order-last flex w-full flex-wrap gap-2 sm:order-none sm:w-auto sm:flex-1">
                                {chips.map((c, n) => (
                                    <button key={n} onClick={() => quitarChip(c)}
                                        className="inline-flex h-8 animate-in items-center gap-1.5 rounded-full bg-brand-light px-3 text-sm font-medium text-brand-dark transition-colors duration-300 zoom-in-95 hover:bg-brand hover:text-white">
                                        {c.t} <X className="h-3.5 w-3.5" />
                                    </button>
                                ))}
                                {chips.length > 1 && (
                                    <button onClick={() => router.get('/catalogo')} className="h-8 px-2 text-sm font-semibold underline underline-offset-4 hover:text-brand">Limpiar todo</button>
                                )}
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" className="ml-auto h-10 rounded-full border-neutral-200 px-4 font-semibold">
                                        <ArrowUpDown className="h-4 w-4" />
                                        <span className="hidden sm:inline">{ORDENES[f.orden ?? 'recientes']}</span>
                                        <span className="sm:hidden">Ordenar</span>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-56 rounded-xl p-1.5">
                                    <DropdownMenuRadioGroup value={f.orden ?? 'recientes'} onValueChange={(v) => aplicar({ orden: v === 'recientes' ? null : v })}>
                                        {Object.entries(ORDENES).map(([v, t]) => (
                                            <DropdownMenuRadioItem key={v} value={v} className="rounded-lg py-2">{t}</DropdownMenuRadioItem>
                                        ))}
                                    </DropdownMenuRadioGroup>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <div className={cn('transition-opacity duration-500', cargando && 'pointer-events-none opacity-50')}>
                            {productos.data.length === 0 ? (
                                <div className="flex flex-col items-center rounded-3xl bg-neutral-50 px-6 py-20 text-center">
                                    <Logo alt="" className="w-36 opacity-20" />
                                    <h2 className="mt-6 text-xl font-bold">No encontramos modelos con esos filtros</h2>
                                    <p className="mt-2 max-w-sm text-neutral-500">Prueba quitando algún filtro o escríbenos por WhatsApp y te ayudamos.</p>
                                    <Button onClick={() => router.get('/catalogo')} className="mt-6 h-11 rounded-full bg-ink px-7">Ver todo el catálogo</Button>
                                </div>
                            ) : (
                                <div className="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3">
                                    {productos.data.map((p, n) => <ProductoCard key={p.id} producto={p} indice={n} />)}
                                </div>
                            )}
                        </div>

                        {productos.last_page > 1 && (
                            <nav className="mt-14 flex items-center justify-center gap-1" aria-label="Paginación">
                                {productos.links.map((l, n) => {
                                    const extremo = n === 0 || n === productos.links.length - 1;
                                    const contenido = n === 0 ? <ChevronLeft className="h-4 w-4" /> : extremo ? <ChevronRight className="h-4 w-4" /> : l.label;
                                    const clase = cn('h-10 min-w-10 items-center justify-center rounded-full px-3 text-sm font-semibold tabular-nums', extremo ? 'flex' : 'hidden sm:flex');
                                    return l.url ? (
                                        <Link key={n} href={l.url} className={cn(clase, 'transition-colors', l.active ? 'bg-ink text-white' : 'hover:bg-neutral-100')}>{contenido}</Link>
                                    ) : (
                                        <span key={n} className={cn(clase, 'text-neutral-300')}>{contenido}</span>
                                    );
                                })}
                                <span className="px-3 text-sm text-neutral-500 sm:hidden">{productos.current_page} / {productos.last_page}</span>
                            </nav>
                        )}
                    </div>
                </div>
            </div>

            {/* Filtros en celular */}
            <Sheet open={panel} onOpenChange={setPanel}>
                <SheetContent side="bottom" className="flex max-h-[88vh] flex-col rounded-t-3xl p-0">
                    <SheetHeader className="border-b px-5 py-4 text-left">
                        <SheetTitle className="font-display text-xl uppercase">Filtros</SheetTitle>
                        <SheetDescription className="sr-only">Filtra el catálogo por categoría, marca, talla y precio</SheetDescription>
                    </SheetHeader>
                    <div className="flex-1 overflow-y-auto px-5 py-6"><Filtros {...props} /></div>
                    <SheetFooter className="grid grid-cols-[auto_1fr] gap-3 border-t p-4">
                        <Button variant="outline" className="h-12 rounded-full px-6" onClick={() => router.get('/catalogo')}>Limpiar</Button>
                        <Button className="h-12 rounded-full bg-ink" onClick={() => setPanel(false)}>Ver {productos.total} modelos</Button>
                    </SheetFooter>
                </SheetContent>
            </Sheet>
        </Layout>
    );
}
