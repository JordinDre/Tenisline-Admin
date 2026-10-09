import ProductoCard from '@/Components/tienda/ProductoCard';
import Layout from '@/Layouts/Layout';
import { capitalizar } from '@/lib/tienda';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, SlidersHorizontal, X } from 'lucide-react';
import { useEffect, useState } from 'react';

const ORDENES = {
    recientes: 'Más recientes',
    precio_asc: 'Precio: menor a mayor',
    precio_desc: 'Precio: mayor a menor',
    nombre: 'Nombre A-Z',
};

const MARCHAMOS = ['rojo', 'naranja', 'celeste', 'amarillo'];

function limpiar(obj) {
    return Object.fromEntries(
        Object.entries(obj).filter(([, v]) => v !== null && v !== undefined && v !== '' && !(Array.isArray(v) && !v.length)),
    );
}

function Grupo({ titulo, children }) {
    return (
        <div className="border-b border-neutral-200 py-5 first:pt-0">
            <h3 className="mb-3 text-xs font-bold uppercase tracking-widest text-neutral-500">{titulo}</h3>
            {children}
        </div>
    );
}

function Filtros({ f, set, marcas, categorias, bodegas, tallas, puedeVerMarchamo }) {
    const [precio, setPrecio] = useState({ min: f.precioMin ?? '', max: f.precioMax ?? '' });
    useEffect(() => setPrecio({ min: f.precioMin ?? '', max: f.precioMax ?? '' }), [f.precioMin, f.precioMax]);

    const alternarTalla = (t) => {
        const actuales = f.tallas ?? [];
        set({ tallas: actuales.includes(t) ? actuales.filter((x) => x !== t) : [...actuales, t] });
    };

    return (
        <div>
            <Grupo titulo="Categoría">
                <div className="flex flex-wrap gap-2">
                    {categorias.map((c) => (
                        <button
                            key={c.clave}
                            onClick={() => set({ categoria: f.categoria === c.clave ? null : c.clave })}
                            className={`rounded-full border px-3.5 py-1.5 text-sm font-medium transition ${f.categoria === c.clave ? 'border-ink bg-ink text-white' : 'hover:border-neutral-400'}`}
                        >
                            {c.label}
                        </button>
                    ))}
                </div>
            </Grupo>

            <Grupo titulo="Marca">
                <div className="grid max-h-72 grid-cols-2 gap-1.5 overflow-y-auto pr-1">
                    {marcas.map((m) => (
                        <button
                            key={m.marca}
                            onClick={() => set({ marca: f.marca === m.marca ? null : m.marca })}
                            className={`flex items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition ${f.marca === m.marca ? 'bg-ink text-white' : 'bg-neutral-100 hover:bg-neutral-200'}`}
                        >
                            <span className="truncate font-medium">{capitalizar(m.marca)}</span>
                            <span className="text-xs opacity-60">{m.modelos}</span>
                        </button>
                    ))}
                </div>
            </Grupo>

            <Grupo titulo="Talla (US)">
                <div className="grid grid-cols-5 gap-1.5">
                    {tallas.map((t) => (
                        <button
                            key={t}
                            onClick={() => alternarTalla(t)}
                            className={`rounded-lg border py-2 text-sm font-medium transition ${(f.tallas ?? []).includes(t) ? 'border-ink bg-ink text-white' : 'hover:border-neutral-400'}`}
                        >
                            {t}
                        </button>
                    ))}
                </div>
            </Grupo>

            <Grupo titulo="Precio (Q)">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        set({ precioMin: precio.min || null, precioMax: precio.max || null });
                    }}
                    className="flex items-center gap-2"
                >
                    <input type="number" min="0" placeholder="Mín" value={precio.min} onChange={(e) => setPrecio((p) => ({ ...p, min: e.target.value }))}
                        className="w-full rounded-lg border-neutral-300 text-sm focus:border-ink focus:ring-ink" />
                    <span className="text-neutral-400">–</span>
                    <input type="number" min="0" placeholder="Máx" value={precio.max} onChange={(e) => setPrecio((p) => ({ ...p, max: e.target.value }))}
                        className="w-full rounded-lg border-neutral-300 text-sm focus:border-ink focus:ring-ink" />
                    <button className="rounded-lg bg-ink px-3 py-2 text-sm font-semibold text-white">Ir</button>
                </form>
            </Grupo>

            {bodegas.length > 0 && (
                <Grupo titulo="Disponible en">
                    <div className="flex flex-wrap gap-2">
                        {bodegas.map((b) => (
                            <button
                                key={b.id}
                                onClick={() => set({ bodega: String(f.bodega) === String(b.id) ? null : b.id })}
                                className={`rounded-full border px-3.5 py-1.5 text-sm font-medium transition ${String(f.bodega) === String(b.id) ? 'border-ink bg-ink text-white' : 'hover:border-neutral-400'}`}
                            >
                                {b.bodega}
                            </button>
                        ))}
                    </div>
                </Grupo>
            )}

            <Grupo titulo="Ofertas">
                <label className="flex cursor-pointer items-center justify-between">
                    <span className="text-sm font-medium">Solo productos en oferta</span>
                    <input type="checkbox" checked={!!f.ofertas} onChange={(e) => set({ ofertas: e.target.checked ? 1 : null })}
                        className="h-5 w-5 rounded border-neutral-300 text-brand focus:ring-brand" />
                </label>
            </Grupo>

            {puedeVerMarchamo && (
                <Grupo titulo="Marchamo (solo administradores)">
                    <div className="flex flex-wrap gap-2">
                        {MARCHAMOS.map((m) => (
                            <button key={m} onClick={() => set({ marchamo: f.marchamo === m ? null : m })}
                                className={`rounded-full border px-3.5 py-1.5 text-sm capitalize transition ${f.marchamo === m ? 'border-ink bg-ink text-white' : 'hover:border-neutral-400'}`}>
                                {m}
                            </button>
                        ))}
                    </div>
                </Grupo>
            )}
        </div>
    );
}

export default function Catalogo({ productos, filtros = {}, marcas = [], categorias = [], bodegas = [], tallas = [], puedeVerMarchamo = false }) {
    const [panel, setPanel] = useState(false);
    const f = filtros;

    const aplicar = (cambios) => {
        router.get('/catalogo', limpiar({ ...f, ...cambios, page: null }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    useEffect(() => {
        document.body.style.overflow = panel ? 'hidden' : '';
        return () => (document.body.style.overflow = '');
    }, [panel]);

    const categoria = categorias.find((c) => c.clave === f.categoria);
    const titulo = f.search ? `“${f.search}”` : f.marca ? capitalizar(f.marca) : categoria ? categoria.label : 'Catálogo';

    const chips = [
        f.search && { k: 'search', t: `Búsqueda: ${f.search}` },
        categoria && { k: 'categoria', t: categoria.label },
        f.marca && { k: 'marca', t: capitalizar(f.marca) },
        ...(f.tallas ?? []).map((t) => ({ k: 'tallas', v: t, t: `Talla ${t}` })),
        (f.precioMin || f.precioMax) && { k: 'precio', t: `Q${f.precioMin || 0} – Q${f.precioMax || '∞'}` },
        f.bodega && { k: 'bodega', t: bodegas.find((b) => String(b.id) === String(f.bodega))?.bodega ?? 'Tienda' },
        f.ofertas && { k: 'ofertas', t: 'En oferta' },
        f.marchamo && { k: 'marchamo', t: `Marchamo ${f.marchamo}` },
    ].filter(Boolean);

    const quitarChip = (c) => {
        if (c.k === 'tallas') return aplicar({ tallas: (f.tallas ?? []).filter((t) => t !== c.v) });
        if (c.k === 'precio') return aplicar({ precioMin: null, precioMax: null });
        aplicar({ [c.k]: null });
    };

    const props = { f, set: aplicar, marcas, categorias, bodegas, tallas, puedeVerMarchamo };

    return (
        <Layout>
            <Head title={titulo} />

            <section className="border-b bg-neutral-50">
                <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
                    <nav className="text-sm text-neutral-500">
                        <Link href="/" className="hover:text-ink">Inicio</Link> / <span>Catálogo</span>
                    </nav>
                    <h1 className="mt-2 font-display text-4xl uppercase sm:text-5xl">{titulo}</h1>
                    <p className="mt-2 text-neutral-500">{productos.total} {productos.total === 1 ? 'modelo' : 'modelos'} disponibles</p>
                </div>
            </section>

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6">
                <div className="flex gap-10">
                    <aside className="hidden w-64 shrink-0 lg:block">
                        <div className="sticky top-28 max-h-[calc(100vh-8rem)] overflow-y-auto pr-2">
                            <Filtros {...props} />
                        </div>
                    </aside>

                    <div className="min-w-0 flex-1">
                        <div className="mb-6 flex flex-wrap items-center gap-3">
                            <button onClick={() => setPanel(true)} className="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold lg:hidden">
                                <SlidersHorizontal className="h-4 w-4" /> Filtros {chips.length > 0 && <span className="rounded-full bg-ink px-1.5 text-xs text-white">{chips.length}</span>}
                            </button>
                            <div className="flex flex-1 flex-wrap gap-2">
                                {chips.map((c, n) => (
                                    <button key={n} onClick={() => quitarChip(c)} className="inline-flex items-center gap-1.5 rounded-full bg-neutral-100 px-3 py-1.5 text-sm font-medium hover:bg-neutral-200">
                                        {c.t} <X className="h-3.5 w-3.5" />
                                    </button>
                                ))}
                                {chips.length > 1 && (
                                    <button onClick={() => router.get('/catalogo')} className="text-sm font-semibold underline">Limpiar todo</button>
                                )}
                            </div>
                            <select
                                value={f.orden ?? 'recientes'}
                                onChange={(e) => aplicar({ orden: e.target.value === 'recientes' ? null : e.target.value })}
                                className="ml-auto rounded-full border-neutral-300 py-2 pl-4 pr-9 text-sm font-medium focus:border-ink focus:ring-ink"
                            >
                                {Object.entries(ORDENES).map(([v, t]) => <option key={v} value={v}>{t}</option>)}
                            </select>
                        </div>

                        {productos.data.length === 0 ? (
                            <div className="flex flex-col items-center rounded-3xl bg-neutral-50 px-6 py-20 text-center">
                                <img src="/images/logo.png" alt="" className="w-40 opacity-20 mix-blend-multiply" />
                                <h2 className="mt-6 text-xl font-bold">No encontramos modelos con esos filtros</h2>
                                <p className="mt-2 text-neutral-500">Prueba quitando algún filtro o escríbenos por WhatsApp y te ayudamos.</p>
                                <button onClick={() => router.get('/catalogo')} className="mt-6 rounded-full bg-ink px-6 py-3 font-semibold text-white">Ver todo el catálogo</button>
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 xl:grid-cols-4">
                                {productos.data.map((p, n) => <ProductoCard key={p.id} producto={p} indice={n} />)}
                            </div>
                        )}

                        {productos.last_page > 1 && (
                            <nav className="mt-14 flex items-center justify-center gap-1" aria-label="Paginación">
                                {productos.links.map((l, n) => {
                                    const esFlecha = n === 0 || n === productos.links.length - 1;
                                    const contenido = n === 0 ? <ChevronLeft className="h-4 w-4" /> : n === productos.links.length - 1 ? <ChevronRight className="h-4 w-4" /> : l.label;
                                    return l.url ? (
                                        <Link key={n} href={l.url} preserveScroll={false}
                                            className={`flex h-10 min-w-10 items-center justify-center rounded-full px-3 text-sm font-semibold transition ${l.active ? 'bg-ink text-white' : 'hover:bg-neutral-100'} ${!esFlecha && !l.active ? 'hidden sm:flex' : ''}`}>
                                            {contenido}
                                        </Link>
                                    ) : (
                                        <span key={n} className={`flex h-10 min-w-10 items-center justify-center px-2 text-sm text-neutral-300 ${!esFlecha ? 'hidden sm:flex' : ''}`}>{contenido}</span>
                                    );
                                })}
                            </nav>
                        )}
                    </div>
                </div>
            </div>

            {/* Panel de filtros en celular */}
            <div className={`fixed inset-0 z-50 lg:hidden ${panel ? '' : 'pointer-events-none'}`}>
                <div onClick={() => setPanel(false)} className={`absolute inset-0 bg-black/40 transition-opacity ${panel ? 'opacity-100' : 'opacity-0'}`} />
                <aside className={`absolute inset-x-0 bottom-0 flex max-h-[85vh] flex-col rounded-t-3xl bg-white transition-transform duration-300 ${panel ? 'translate-y-0' : 'translate-y-full'}`}>
                    <div className="flex items-center justify-between border-b px-5 py-4">
                        <h2 className="font-display text-xl uppercase">Filtros</h2>
                        <button onClick={() => setPanel(false)} className="rounded-full p-2 hover:bg-neutral-100" aria-label="Cerrar filtros"><X className="h-5 w-5" /></button>
                    </div>
                    <div className="flex-1 overflow-y-auto px-5 py-5"><Filtros {...props} /></div>
                    <div className="border-t p-4">
                        <button onClick={() => setPanel(false)} className="w-full rounded-full bg-ink py-3.5 font-semibold text-white">
                            Ver {productos.total} modelos
                        </button>
                    </div>
                </aside>
            </div>
        </Layout>
    );
}
