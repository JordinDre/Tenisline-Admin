import ProductoCard from '@/Components/tienda/ProductoCard';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
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
import { Head, Link, router } from '@inertiajs/react';
import { ChevronDown, ChevronLeft, ChevronRight, SlidersHorizontal, X } from 'lucide-react';
import { useState } from 'react';

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

const MARCHAMOS = ['rojo', 'naranja', 'celeste', 'amarillo'];

const limpiar = (obj) =>
    Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== null && v !== undefined && v !== '' && !(Array.isArray(v) && !v.length)));

const mismo = (a, b) => String(a ?? '') === String(b ?? '');

function Opcion({ activo, onClick, children, contador }) {
    return (
        <button onClick={onClick} className="flex w-full items-center gap-3 py-1.5 text-left text-[15px] hover:text-neutral-500">
            <Checkbox checked={activo} className="pointer-events-none h-5 w-5 rounded-[4px] border-neutral-400 data-[state=checked]:border-black" tabIndex={-1} />
            <span className="flex-1">{children}</span>
            {contador !== undefined && <span className="text-sm text-neutral-400">{contador}</span>}
        </button>
    );
}

function Filtros({ f, set, marcas, categorias, bodegas, tallas, puedeVerMarchamo }) {
    return (
        <div>
            <nav className="mb-4 space-y-1">
                <Link href="/catalogo" className={cn('block py-1.5 text-[15px] font-medium hover:text-neutral-500', !f.categoria && 'underline underline-offset-4')}>Todos</Link>
                {categorias.map((c) => (
                    <button key={c.clave} onClick={() => set({ categoria: f.categoria === c.clave ? null : c.clave })}
                        className={cn('block py-1.5 text-left text-[15px] font-medium hover:text-neutral-500', f.categoria === c.clave && 'underline underline-offset-4', c.clave === 'ofertas' && 'text-red-600')}>
                        {c.label}
                    </button>
                ))}
            </nav>

            <Accordion type="multiple" defaultValue={['marca', 'talla', 'precio']} className="border-t">
                <AccordionItem value="marca">
                    <AccordionTrigger className="text-[15px] font-medium hover:no-underline">
                        Marca{f.marca ? ' (1)' : ''}
                    </AccordionTrigger>
                    <AccordionContent className="max-h-72 overflow-y-auto pr-1">
                        {marcas.map((m) => (
                            <Opcion key={m.marca} activo={f.marca === m.marca} contador={m.modelos} onClick={() => set({ marca: f.marca === m.marca ? null : m.marca })}>
                                {capitalizar(m.marca)}
                            </Opcion>
                        ))}
                    </AccordionContent>
                </AccordionItem>

                <AccordionItem value="talla">
                    <AccordionTrigger className="text-[15px] font-medium hover:no-underline">
                        Talla (US){f.tallas?.length ? ` (${f.tallas.length})` : ''}
                    </AccordionTrigger>
                    <AccordionContent>
                        <div className="grid grid-cols-4 gap-1.5 pt-1">
                            {tallas.map((t) => {
                                const activa = (f.tallas ?? []).includes(t);
                                return (
                                    <button key={t}
                                        onClick={() => set({ tallas: activa ? f.tallas.filter((x) => x !== t) : [...(f.tallas ?? []), t] })}
                                        className={cn('h-10 rounded-md border text-sm transition-colors', activa ? 'border-black ring-1 ring-black' : 'border-neutral-300 hover:border-black')}>
                                        {t}
                                    </button>
                                );
                            })}
                        </div>
                    </AccordionContent>
                </AccordionItem>

                <AccordionItem value="precio">
                    <AccordionTrigger className="text-[15px] font-medium hover:no-underline">Comprar por precio</AccordionTrigger>
                    <AccordionContent>
                        {RANGOS.map((r) => {
                            const activo = mismo(f.precioMin, r.min) && mismo(f.precioMax, r.max);
                            return (
                                <Opcion key={r.label} activo={activo} onClick={() => set(activo ? { precioMin: null, precioMax: null } : { precioMin: r.min, precioMax: r.max })}>
                                    {r.label}
                                </Opcion>
                            );
                        })}
                    </AccordionContent>
                </AccordionItem>

                {bodegas.length > 0 && (
                    <AccordionItem value="tienda">
                        <AccordionTrigger className="text-[15px] font-medium hover:no-underline">Disponible en tienda</AccordionTrigger>
                        <AccordionContent>
                            {bodegas.map((b) => (
                                <Opcion key={b.id} activo={mismo(f.bodega, b.id)} onClick={() => set({ bodega: mismo(f.bodega, b.id) ? null : b.id })}>
                                    {b.bodega}
                                </Opcion>
                            ))}
                        </AccordionContent>
                    </AccordionItem>
                )}

                <AccordionItem value="ofertas">
                    <AccordionTrigger className="text-[15px] font-medium hover:no-underline">Descuentos y ofertas</AccordionTrigger>
                    <AccordionContent>
                        <label className="flex cursor-pointer items-center justify-between py-1.5 text-[15px]">
                            Solo productos en oferta
                            <Switch checked={!!f.ofertas} onCheckedChange={(v) => set({ ofertas: v ? 1 : null })} />
                        </label>
                    </AccordionContent>
                </AccordionItem>

                {puedeVerMarchamo && (
                    <AccordionItem value="marchamo">
                        <AccordionTrigger className="text-[15px] font-medium hover:no-underline">Marchamo (administradores)</AccordionTrigger>
                        <AccordionContent>
                            {MARCHAMOS.map((m) => (
                                <Opcion key={m} activo={f.marchamo === m} onClick={() => set({ marchamo: f.marchamo === m ? null : m })}>
                                    {capitalizar(m)}
                                </Opcion>
                            ))}
                        </AccordionContent>
                    </AccordionItem>
                )}
            </Accordion>
        </div>
    );
}

export default function Catalogo({ productos, filtros = {}, marcas = [], categorias = [], bodegas = [], tallas = [], puedeVerMarchamo = false }) {
    const [mostrarFiltros, setMostrarFiltros] = useState(true);
    const [panel, setPanel] = useState(false);
    const f = filtros;

    const aplicar = (cambios) =>
        router.get('/catalogo', limpiar({ ...f, ...cambios, page: null }), { preserveState: true, preserveScroll: true, replace: true });

    const categoria = categorias.find((c) => c.clave === f.categoria);
    const titulo = f.search
        ? `Resultados para “${f.search}”`
        : [categoria && (categoria.clave === 'ofertas' ? 'Ofertas' : `Tenis para ${categoria.label.toLowerCase()}`), f.marca && capitalizar(f.marca)].filter(Boolean).join(' · ') || 'Todos los tenis';

    const chips = [
        f.marca && { k: 'marca', t: capitalizar(f.marca) },
        ...(f.tallas ?? []).map((t) => ({ k: 'tallas', v: t, t: `Talla ${t}` })),
        (f.precioMin || f.precioMax) && { k: 'precio', t: RANGOS.find((r) => mismo(r.min, f.precioMin) && mismo(r.max, f.precioMax))?.label ?? 'Precio' },
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
            <Head title={titulo} />

            {/* Barra de título, como en Nike: queda fija bajo el header */}
            <div className="bg-white">
                <div className="mx-auto flex max-w-[1600px] items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:py-5">
                    <h1 className="truncate text-xl font-medium tracking-tight sm:text-2xl">
                        {titulo} <span className="text-neutral-500">({productos.total})</span>
                    </h1>
                    <div className="flex shrink-0 items-center gap-1 sm:gap-5">
                        <button onClick={() => setMostrarFiltros((v) => !v)} className="hidden items-center gap-2 text-[15px] lg:flex">
                            {mostrarFiltros ? 'Ocultar filtros' : 'Mostrar filtros'} <SlidersHorizontal className="h-4 w-4" />
                        </button>
                        <Button variant="outline" onClick={() => setPanel(true)} className="rounded-full lg:hidden">
                            Filtros{chips.length ? ` (${chips.length})` : ''} <SlidersHorizontal className="h-4 w-4" />
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger className="hidden items-center gap-1.5 text-[15px] outline-none sm:flex">
                                <span>Ordenar por<span className="hidden text-neutral-500 xl:inline">: {ORDENES[f.orden ?? 'recientes']}</span></span>
                                <ChevronDown className="h-4 w-4" />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <DropdownMenuRadioGroup value={f.orden ?? 'recientes'} onValueChange={(v) => aplicar({ orden: v === 'recientes' ? null : v })}>
                                    {Object.entries(ORDENES).map(([v, t]) => <DropdownMenuRadioItem key={v} value={v}>{t}</DropdownMenuRadioItem>)}
                                </DropdownMenuRadioGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>

            <div className="mx-auto max-w-[1600px] px-4 sm:px-6">
                <div className="flex gap-10">
                    <aside className={cn('hidden shrink-0 transition-all duration-300 lg:block', mostrarFiltros ? 'w-60 opacity-100' : 'w-0 overflow-hidden opacity-0')}>
                        <div className="sticky top-24 max-h-[calc(100vh-7rem)] overflow-y-auto pb-10 pr-3 [scrollbar-width:thin]">
                            <Filtros {...props} />
                        </div>
                    </aside>

                    <div className="min-w-0 flex-1 pb-10">
                        {chips.length > 0 && (
                            <div className="mb-5 flex flex-wrap items-center gap-2">
                                {chips.map((c, n) => (
                                    <button key={n} onClick={() => quitarChip(c)} className="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm hover:border-black">
                                        {c.t} <X className="h-3.5 w-3.5" />
                                    </button>
                                ))}
                                <button onClick={() => router.get('/catalogo', limpiar({ categoria: f.categoria, search: f.search }))} className="px-2 text-sm underline underline-offset-4">Borrar filtros</button>
                            </div>
                        )}

                        {productos.data.length === 0 ? (
                            <div className="flex flex-col items-center bg-neutral-50 px-6 py-24 text-center">
                                <h2 className="text-xl font-medium">No encontramos modelos con esos filtros</h2>
                                <p className="mt-2 text-neutral-500">Prueba quitando algún filtro o escríbenos por WhatsApp y te ayudamos.</p>
                                <Button onClick={() => router.get('/catalogo')} className="mt-6 rounded-full px-7">Ver todo el catálogo</Button>
                            </div>
                        ) : (
                            <div className={cn('grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-4', mostrarFiltros ? 'md:grid-cols-3' : 'md:grid-cols-3 xl:grid-cols-4')}>
                                {productos.data.map((p, n) => <ProductoCard key={p.id} producto={p} indice={n} />)}
                            </div>
                        )}

                        {productos.last_page > 1 && (
                            <nav className="mt-14 flex items-center justify-center gap-1" aria-label="Paginación">
                                {productos.links.map((l, n) => {
                                    const extremo = n === 0 || n === productos.links.length - 1;
                                    const contenido = n === 0 ? <ChevronLeft className="h-4 w-4" /> : extremo ? <ChevronRight className="h-4 w-4" /> : l.label;
                                    const clase = cn('flex h-10 min-w-10 items-center justify-center rounded-full px-3 text-[15px]', !extremo && 'hidden sm:flex');
                                    return l.url ? (
                                        <Link key={n} href={l.url} className={cn(clase, l.active ? 'bg-black text-white' : 'hover:bg-neutral-100')}>{contenido}</Link>
                                    ) : (
                                        <span key={n} className={cn(clase, 'text-neutral-300')}>{contenido}</span>
                                    );
                                })}
                            </nav>
                        )}
                        {productos.last_page > 1 && (
                            <p className="mt-3 text-center text-sm text-neutral-500 sm:hidden">Página {productos.current_page} de {productos.last_page}</p>
                        )}
                    </div>
                </div>
            </div>

            {/* Filtros en celular */}
            <Sheet open={panel} onOpenChange={setPanel}>
                <SheetContent side="right" className="flex w-full flex-col p-0 sm:max-w-md">
                    <SheetHeader className="border-b px-6 py-5 text-left">
                        <SheetTitle className="text-xl font-medium">Filtrar</SheetTitle>
                        <SheetDescription className="sr-only">Filtra el catálogo por categoría, marca, talla y precio</SheetDescription>
                    </SheetHeader>
                    <div className="flex-1 overflow-y-auto px-6 py-5">
                        <div className="mb-5 sm:hidden">
                            <p className="mb-2 text-[15px] font-medium">Ordenar por</p>
                            {Object.entries(ORDENES).map(([v, t]) => (
                                <Opcion key={v} activo={(f.orden ?? 'recientes') === v} onClick={() => aplicar({ orden: v === 'recientes' ? null : v })}>{t}</Opcion>
                            ))}
                        </div>
                        <Filtros {...props} />
                    </div>
                    <SheetFooter className="grid grid-cols-2 gap-3 border-t px-6 py-4">
                        <Button variant="outline" className="h-12 rounded-full" onClick={() => router.get('/catalogo')}>Borrar</Button>
                        <Button className="h-12 rounded-full" onClick={() => setPanel(false)}>Aplicar ({productos.total})</Button>
                    </SheetFooter>
                </SheetContent>
            </Sheet>
        </Layout>
    );
}
