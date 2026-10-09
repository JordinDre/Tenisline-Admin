import { IconoWhatsApp } from '@/Components/tienda/CarritoDrawer';
import ImagenProducto from '@/Components/tienda/ImagenProducto';
import ProductoCard from '@/Components/tienda/ProductoCard';
import { Button } from '@/Components/ui/button';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from '@/Components/ui/carousel';
import { useCarrito } from '@/Contexts/CarritoContext';
import Layout from '@/Layouts/Layout';
import { capitalizar, descuento, enlaceWhatsApp, quetzales } from '@/lib/tienda';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { Check, MapPin, MessageCircle, ShoppingBag, Store, Tag } from 'lucide-react';
import { useMemo, useState } from 'react';
import Zoom from 'react-medium-image-zoom';
import 'react-medium-image-zoom/dist/styles.css';

const contenedor = 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8';

function Contenido({ producto, variantes, mostrarExistencia, relacionados }) {
    const { agregar } = useCarrito();
    const sucursales = usePage().props.tienda?.sucursales ?? [];
    const inicial = variantes.find((v) => v.slug === producto.slug) ?? variantes[0];
    const [seleccion, setSeleccion] = useState(inicial?.id ?? null);
    const [foto, setFoto] = useState(0);
    const [agregado, setAgregado] = useState(false);

    const variante = variantes.find((v) => v.id === seleccion) ?? inicial;
    const imagenes = producto.imagenes?.length ? producto.imagenes : [null];
    const pct = descuento(variante);

    const item = useMemo(
        () =>
            variante && {
                id: variante.id,
                slug: variante.slug,
                codigo: variante.codigo,
                talla: variante.talla,
                precio: variante.precio,
                precio_oferta: variante.precio_oferta,
                descripcion: producto.descripcion,
                marca: producto.marca,
                color: producto.color,
                imagen: producto.imagenes?.[0] ?? null,
            },
        [variante, producto],
    );

    const alAgregar = () => {
        if (!item) return;
        agregar(item);
        setAgregado(true);
        setTimeout(() => setAgregado(false), 1800);
    };

    return (
        <>
            <div className={cn(contenedor, 'py-6')}>
                <nav className="truncate text-sm text-neutral-500">
                    <Link href="/" className="transition-colors hover:text-ink">Inicio</Link>
                    <span className="mx-2">/</span>
                    <Link href="/catalogo" className="transition-colors hover:text-ink">Catálogo</Link>
                    {producto.marca && (
                        <>
                            <span className="mx-2">/</span>
                            <Link href={`/catalogo?marca=${encodeURIComponent(producto.marca)}`} className="transition-colors hover:text-ink">{capitalizar(producto.marca)}</Link>
                        </>
                    )}
                </nav>

                <div className="mt-6 grid gap-8 lg:grid-cols-2 lg:gap-14">
                    {/* Galería */}
                    <div className="animate-fade-up lg:sticky lg:top-28 lg:self-start">
                        <Carousel className="lg:hidden" opts={{ loop: imagenes.length > 1 }}>
                            <CarouselContent className="ml-0">
                                {imagenes.map((img, n) => (
                                    <CarouselItem key={n} className="pl-0">
                                        <div className="relative aspect-square overflow-hidden rounded-3xl bg-neutral-100">
                                            <ImagenProducto src={img} alt={producto.descripcion} className="h-full w-full" />
                                        </div>
                                    </CarouselItem>
                                ))}
                            </CarouselContent>
                            {imagenes.length > 1 && (
                                <>
                                    <CarouselPrevious className="left-3 border-0 bg-white/90 shadow" />
                                    <CarouselNext className="right-3 border-0 bg-white/90 shadow" />
                                </>
                            )}
                        </Carousel>

                        <div className="relative hidden aspect-square overflow-hidden rounded-3xl bg-neutral-100 lg:block">
                            {imagenes[foto] ? (
                                <Zoom><ImagenProducto src={imagenes[foto]} alt={producto.descripcion} className="h-full w-full" /></Zoom>
                            ) : (
                                <ImagenProducto src={null} alt={producto.descripcion} className="h-full w-full" />
                            )}
                            {pct > 0 && <span className="absolute left-5 top-5 rounded-full bg-brand px-3 py-1.5 text-sm font-bold leading-none text-white shadow">-{pct}%</span>}
                        </div>
                        {imagenes.length > 1 && (
                            <div className="mt-3 hidden gap-3 lg:flex">
                                {imagenes.map((img, n) => (
                                    <button key={n} onClick={() => setFoto(n)} onMouseEnter={() => setFoto(n)}
                                        className={cn('h-20 w-20 overflow-hidden rounded-2xl bg-neutral-100 ring-2 ring-offset-2 transition-all', foto === n ? 'ring-brand' : 'ring-transparent opacity-70 hover:opacity-100')}>
                                        <ImagenProducto src={img} alt="" className="h-full w-full" />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Información */}
                    <div className="animate-fade-up" style={{ animationDelay: '80ms' }}>
                        <p className="text-xs font-bold uppercase tracking-[0.18em] text-brand">{producto.marca}</p>
                        <h1 className="mt-2 font-display text-3xl uppercase leading-[1.05] sm:text-4xl">{producto.descripcion}</h1>
                        <div className="mt-4 flex flex-wrap gap-2">
                            {producto.color && <span className="rounded-full bg-neutral-100 px-3 py-1 text-sm font-medium text-neutral-700">{capitalizar(producto.color)}</span>}
                            {producto.genero && <span className="rounded-full bg-neutral-100 px-3 py-1 text-sm font-medium text-neutral-700">{capitalizar(producto.genero)}</span>}
                        </div>

                        {variante && (
                            <div className="mt-6 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <span className={cn('text-3xl font-extrabold tabular-nums', pct ? 'text-brand' : 'text-ink')}>{quetzales(variante.precio_oferta || variante.precio)}</span>
                                {pct > 0 && (
                                    <>
                                        <span className="text-lg text-neutral-400 line-through">{quetzales(variante.precio)}</span>
                                        <span className="inline-flex items-center gap-1 rounded-full bg-brand-light px-2.5 py-1 text-sm font-bold text-brand-dark"><Tag className="h-3.5 w-3.5" /> Ahorras {pct}%</span>
                                    </>
                                )}
                            </div>
                        )}

                        <div className="mt-8">
                            <div className="mb-3 flex items-center justify-between">
                                <h2 className="font-semibold">Elige tu talla (US)</h2>
                                {variante && <span className="text-sm text-neutral-500">Código <span className="font-semibold text-ink">{variante.codigo}</span></span>}
                            </div>
                            <div className="grid grid-cols-4 gap-2 sm:grid-cols-5">
                                {variantes.map((v) => (
                                    <button key={v.id} onClick={() => setSeleccion(v.id)}
                                        className={cn('h-12 rounded-xl border-2 text-[15px] font-semibold tabular-nums transition-all duration-200 active:scale-95',
                                            v.id === seleccion ? 'border-ink bg-ink text-white shadow-md' : 'border-neutral-200 hover:border-neutral-500')}>
                                        {v.talla}
                                    </button>
                                ))}
                            </div>
                            <p className="mt-4 flex items-center gap-2 text-sm font-medium text-emerald-700">
                                <span className="relative flex h-2 w-2"><span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-60" /><span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" /></span>
                                Disponible en tienda
                            </p>
                            {mostrarExistencia && variante?.sucursales?.length > 0 && (
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {variante.sucursales.map((s) => (
                                        <span key={s.sucursal} className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-sm text-emerald-800">
                                            <MapPin className="h-3.5 w-3.5" /> {s.sucursal}: {s.existencia}
                                        </span>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="mt-8 grid gap-3 sm:grid-cols-2">
                            <Button onClick={alAgregar} disabled={!variante}
                                className={cn('h-14 rounded-full text-base font-semibold transition-colors', agregado ? 'bg-emerald-600 hover:bg-emerald-600' : 'bg-ink hover:bg-brand')}>
                                {agregado ? <><Check className="h-5 w-5" /> Agregado</> : <><ShoppingBag className="h-5 w-5" /> Agregar al carrito</>}
                            </Button>
                            {sucursales[0] && (
                                <Button asChild variant="outline" className="h-14 rounded-full border-2 border-[#25D366] text-base font-semibold text-[#128C7E] hover:bg-[#25D366] hover:text-white">
                                    <a href={item ? enlaceWhatsApp(sucursales[0].telefono, [{ ...item, cantidad: 1 }], sucursales[0].nombre) : `https://wa.me/${sucursales[0].telefono}`}
                                        target="_blank" rel="noopener noreferrer">
                                        <IconoWhatsApp className="h-5 w-5" /> Preguntar
                                    </a>
                                </Button>
                            )}
                        </div>

                        <ul className="mt-10 divide-y rounded-3xl bg-neutral-50 px-6 text-sm">
                            <li className="flex gap-3 py-4"><Store className="h-5 w-5 shrink-0 text-brand" /><span><b>Visítanos</b> en Zacapa, Chiquimula y Esquipulas, o pide por WhatsApp.</span></li>
                            <li className="flex gap-3 py-4"><MessageCircle className="h-5 w-5 shrink-0 text-brand" /><span><b>Sin pagos en línea.</b> Confirmamos disponibilidad y forma de pago por WhatsApp.</span></li>
                            <li className="flex gap-3 py-4"><Tag className="h-5 w-5 shrink-0 text-brand" /><span><b>Tallas disponibles:</b> {variantes.map((v) => v.talla).join(' · ')}</span></li>
                        </ul>
                    </div>
                </div>
            </div>

            {relacionados.length > 0 && (
                <section className={cn(contenedor, 'mt-16')}>
                    <Carousel opts={{ align: 'start', dragFree: true }}>
                        <div className="mb-8 flex items-end justify-between gap-4">
                            <h2 className="font-display text-[1.75rem] uppercase leading-none sm:text-4xl">También de {capitalizar(producto.marca)}</h2>
                            <div className="flex shrink-0 gap-2">
                                <CarouselPrevious className="static hidden h-10 w-10 translate-y-0 border-neutral-200 sm:flex" />
                                <CarouselNext className="static hidden h-10 w-10 translate-y-0 border-neutral-200 sm:flex" />
                            </div>
                        </div>
                        <CarouselContent className="-ml-4">
                            {relacionados.map((p, n) => (
                                <CarouselItem key={p.id} className="basis-[46%] pl-4 sm:basis-1/3 lg:basis-1/4">
                                    <ProductoCard producto={p} indice={n} />
                                </CarouselItem>
                            ))}
                        </CarouselContent>
                    </Carousel>
                </section>
            )}
        </>
    );
}

export default function Producto(props) {
    return (
        <Layout>
            <Contenido key={props.producto.slug} {...props} variantes={props.variantes ?? []} relacionados={props.relacionados ?? []} />
        </Layout>
    );
}
