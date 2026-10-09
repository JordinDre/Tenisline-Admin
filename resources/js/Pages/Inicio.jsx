import ImagenProducto from '@/Components/tienda/ImagenProducto';
import LogoMarca from '@/Components/tienda/LogoMarca';
import ProductoCard from '@/Components/tienda/ProductoCard';
import { Button } from '@/Components/ui/button';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from '@/Components/ui/carousel';
import Layout from '@/Layouts/Layout';
import { capitalizar, quetzales } from '@/lib/tienda';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import Autoplay from 'embla-carousel-autoplay';
import { ArrowRight, ArrowUpRight, Baby, BadgeCheck, Clock, Footprints, Mars, MessageCircle, PackagePlus, Percent, Search, ShoppingBag, Store, Tag, Venus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const contenedor = 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8';

/* ---------- Portada ---------- */

function FotoFlotante({ producto, className, giro, retraso }) {
    if (!producto) return null;
    return (
        <Link href={route('producto', producto.slug)}
            className={cn('group absolute animate-flotar transform-gpu overflow-hidden rounded-3xl bg-white   ring-1 ring-white/10', className)}
            style={{ '--giro': `${giro}deg`, animationDelay: `${retraso}s` }}>
            <ImagenProducto src={producto.imagen} alt={producto.descripcion} sizes="280px" prioridad className="h-full w-full transition-transform duration-700 group-hover:scale-105" />
            <span className="absolute inset-x-2 bottom-2 flex items-center justify-between rounded-2xl bg-white/90 px-3 py-2 text-ink backdrop-blur">
                <span className="truncate text-xs font-semibold">{capitalizar(producto.marca)}</span>
                <span className="ml-2 shrink-0 text-xs font-bold text-brand">{quetzales(producto.precio_oferta || producto.precio)}</span>
            </span>
        </Link>
    );
}

function Portada({ destacados }) {
    const [a, b, c] = destacados;

    return (
        <section className="relative overflow-hidden bg-ink text-white">
            <div className="pointer-events-none absolute -right-32 -top-32 h-[28rem] w-[28rem] transform-gpu rounded-full bg-brand/30 blur-3xl" />
            <div className="pointer-events-none absolute -bottom-40 left-1/4 h-96 w-96 transform-gpu rounded-full bg-brand/15 blur-3xl" />

            <div className={cn(contenedor, 'relative grid items-center gap-12 py-14 md:py-20 lg:grid-cols-2 lg:py-24')}>
                <div>
                    <span className="inline-flex animate-fade-up items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] backdrop-blur">
                        <span className="relative flex h-2 w-2"><span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand opacity-70" /><span className="relative inline-flex h-2 w-2 rounded-full bg-brand" /></span> Nuevos modelos cada semana
                    </span>
                    <h1 className="mt-6 animate-fade-up font-display text-[2.75rem] uppercase leading-[0.95] sm:text-6xl xl:text-7xl" style={{ animationDelay: '140ms' }}>
                        No box,<br /><span className="text-brand">sí precio.</span>
                    </h1>
                    <p className="mt-6 max-w-md animate-fade-up text-lg leading-relaxed text-neutral-300" style={{ animationDelay: '280ms' }}>
                        Nike, adidas, Puma, New Balance, On, Hoka y más, a precios que no vas a encontrar en otro lado.
                    </p>
                    <div className="mt-8 flex animate-fade-up flex-wrap gap-3" style={{ animationDelay: '420ms' }}>
                        <Button asChild className="h-12 rounded-full bg-brand px-7 text-[15px] font-semibold hover:bg-brand-dark">
                            <Link href="/catalogo">Ver catálogo <ArrowRight className="h-4 w-4" /></Link>
                        </Button>
                        <Button asChild variant="outline" className="h-12 rounded-full border-white/30 bg-transparent px-7 text-[15px] font-semibold text-white hover:bg-white hover:text-ink">
                            <Link href="/catalogo?categoria=ofertas">Ofertas</Link>
                        </Button>
                    </div>

                    {/* Fotos en celular */}
                    {a && (
                        <div className="mt-10 grid animate-fade-up grid-cols-3 gap-3 lg:hidden" style={{ animationDelay: '560ms' }}>
                            {[a, b, c].filter(Boolean).map((p) => (
                                <Link key={p.id} href={route('producto', p.slug)} className="aspect-square overflow-hidden rounded-2xl bg-white">
                                    <ImagenProducto src={p.imagen} alt={p.descripcion} sizes="33vw" prioridad className="h-full w-full" />
                                </Link>
                            ))}
                        </div>
                    )}
                </div>

                {/* Collage en computadora */}
                {a && (
                    <div className="relative hidden h-[460px] lg:block">
                        <FotoFlotante producto={a} giro={-4} retraso={0} className="left-[22%] top-0 z-20 h-[360px] w-[280px]" />
                        <FotoFlotante producto={b} giro={6} retraso={1.2} className="right-0 top-10 z-10 h-[220px] w-[180px]" />
                        <FotoFlotante producto={c} giro={-8} retraso={2.4} className="bottom-0 left-0 z-10 h-[200px] w-[170px]" />
                    </div>
                )}
            </div>
        </section>
    );
}

function CarruselPromociones({ promociones }) {
    const autoplay = useRef(Autoplay({ delay: 8000, stopOnInteraction: true }));
    const [api, setApi] = useState(null);
    const [actual, setActual] = useState(0);

    useEffect(() => {
        if (!api) return;
        const sync = () => setActual(api.selectedScrollSnap());
        sync();
        api.on('select', sync);
        return () => api.off('select', sync);
    }, [api]);

    return (
        <section className="group relative bg-ink">
            <Carousel setApi={setApi} opts={{ loop: promociones.length > 1 }} plugins={promociones.length > 1 ? [autoplay.current] : []}>
                <CarouselContent className="ml-0">
                    {promociones.map((p, n) => {
                        const tarjeta = (
                            <div className="relative h-[68vh] max-h-[720px] min-h-[440px] w-full">
                                {p.imagen && (
                                    <picture>
                                        {p.imagen_movil && <source media="(max-width: 767px)" srcSet={p.imagen_movil} />}
                                        <img src={p.imagen} alt={p.titulo || 'Promoción Tenisline'} loading={n === 0 ? 'eager' : 'lazy'} className="absolute inset-0 h-full w-full object-cover" />
                                    </picture>
                                )}
                                {(p.titulo || p.subtitulo || p.boton) && (
                                    <div className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent">
                                        <div className={cn(contenedor, 'flex h-full flex-col justify-end pb-16')}>
                                            {p.titulo && <h2 className="max-w-3xl font-display text-4xl uppercase leading-[0.95] text-white sm:text-6xl">{p.titulo}</h2>}
                                            {p.subtitulo && <p className="mt-4 max-w-xl text-lg text-white/85">{p.subtitulo}</p>}
                                            {p.boton && p.enlace && (
                                                <span className="mt-6 inline-flex h-12 w-fit items-center gap-2 rounded-full bg-brand px-7 font-semibold text-white transition-colors hover:bg-brand-dark">
                                                    {p.boton} <ArrowRight className="h-4 w-4" />
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        );
                        return <CarouselItem key={n} className="pl-0">{p.enlace ? <Link href={p.enlace}>{tarjeta}</Link> : tarjeta}</CarouselItem>;
                    })}
                </CarouselContent>
                {promociones.length > 1 && (
                    <>
                        <CarouselPrevious className="left-5 hidden h-11 w-11 border-0 bg-white/85 opacity-0 backdrop-blur transition-opacity group-hover:opacity-100 md:flex" />
                        <CarouselNext className="right-5 hidden h-11 w-11 border-0 bg-white/85 opacity-0 backdrop-blur transition-opacity group-hover:opacity-100 md:flex" />
                    </>
                )}
            </Carousel>
            {promociones.length > 1 && (
                <div className="absolute bottom-6 left-1/2 flex -translate-x-1/2 gap-2">
                    {promociones.map((_, n) => (
                        <button key={n} onClick={() => api?.scrollTo(n)} aria-label={`Promoción ${n + 1}`}
                            className={cn('h-1.5 rounded-full transition-all duration-500', n === actual ? 'w-8 bg-brand' : 'w-3 bg-white/60 hover:bg-white')} />
                    ))}
                </div>
            )}
        </section>
    );
}

/* ---------- Secciones ---------- */

function Titulo({ titulo, subtitulo, enlace, texto = 'Ver todo', icono: Icono, children }) {
    return (
        <div className="mb-8 flex items-end justify-between gap-4">
            <div className="min-w-0">
                <div className="flex items-center gap-3">
                    {Icono && (
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-brand-light text-brand sm:h-11 sm:w-11">
                            <Icono className="h-5 w-5" strokeWidth={2.25} />
                        </span>
                    )}
                    <h2 className="font-display text-[1.75rem] uppercase leading-none sm:text-4xl">{titulo}</h2>
                </div>
                {subtitulo && <p className="mt-2 text-neutral-500">{subtitulo}</p>}
            </div>
            <div className="flex shrink-0 items-center gap-2">
                {enlace && (
                    <Link href={enlace} className="group hidden items-center gap-1 text-sm font-semibold transition-colors hover:text-brand sm:flex">
                        {texto} <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                    </Link>
                )}
                {children}
            </div>
        </div>
    );
}

const ESTILO_CATEGORIA = {
    dama: 'from-rose-100 via-orange-50 to-white',
    caballero: 'from-neutral-200 via-neutral-100 to-white',
    nino: 'from-sky-100 via-cyan-50 to-white',
    infante: 'from-amber-100 via-yellow-50 to-white',
    ofertas: 'from-brand via-orange-500 to-orange-400 text-white',
};

const ICONO_CATEGORIA = { dama: Venus, caballero: Mars, nino: Footprints, infante: Baby, ofertas: Percent };

function CarruselProductos({ titulo, subtitulo, enlace, productos, icono }) {
    if (!productos.length) return null;
    return (
        <section className={cn(contenedor, 'mt-20')}>
            <Carousel opts={{ align: 'start', dragFree: true }}>
                <Titulo titulo={titulo} subtitulo={subtitulo} enlace={enlace} icono={icono}>
                    <CarouselPrevious className="static hidden h-10 w-10 translate-y-0 border-neutral-200 sm:flex" />
                    <CarouselNext className="static hidden h-10 w-10 translate-y-0 border-neutral-200 sm:flex" />
                </Titulo>
                <CarouselContent className="-ml-4">
                    {productos.map((p, n) => (
                        <CarouselItem key={p.id} className="basis-[46%] pl-4 sm:basis-1/3 lg:basis-1/4">
                            <ProductoCard producto={p} indice={n} />
                        </CarouselItem>
                    ))}
                </CarouselContent>
            </Carousel>
        </section>
    );
}

export default function Inicio({ promociones = [], categorias = [], marcas = [], ofertas = [], novedades = [], destacados = [] }) {
    return (
        <Layout>

            {promociones.length ? <CarruselPromociones promociones={promociones} /> : <Portada destacados={destacados} />}

            {/* Categorías */}
            <section className={cn(contenedor, 'pt-16')}>
                <Titulo titulo="Compra por categoría" icono={Store} />
                <div className="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-5">
                    {categorias.map((c, n) => (
                        <Link key={c.clave} href={`/catalogo?categoria=${c.clave}`}
                            className={cn(
                                'group relative flex aspect-[4/3] animate-fade-up flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-br p-5 ring-1 ring-black/5 transition-transform duration-500 will-change-transform hover:-translate-y-1  md:aspect-[3/4] lg:p-6',
                                ESTILO_CATEGORIA[c.clave] ?? 'from-neutral-100 to-white',
                                n === categorias.length - 1 && categorias.length % 2 === 1 && 'col-span-2 aspect-[8/3] md:col-span-1 md:aspect-[3/4]',
                            )}
                            style={{ animationDelay: `${n * 100}ms` }}>
                            <div className="flex items-start justify-between">
                                <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/70 text-ink backdrop-blur">
                                    {(() => { const Icono = ICONO_CATEGORIA[c.clave] ?? Tag; return <Icono className="h-5 w-5" strokeWidth={2} />; })()}
                                </span>
                                <span className="flex h-9 w-9 items-center justify-center rounded-full bg-white/70 text-ink backdrop-blur transition-transform duration-500 group-hover:rotate-45">
                                    <ArrowUpRight className="h-4 w-4" />
                                </span>
                            </div>
                            <div>
                                <span className="text-xs font-semibold opacity-70">{c.modelos} modelos</span>
                                <h3 className="font-display text-xl uppercase leading-none sm:text-2xl md:text-lg lg:text-2xl xl:text-[1.75rem]">{c.label}</h3>
                            </div>
                        </Link>
                    ))}
                </div>
            </section>

            {/* Recién publicados (con foto) */}
            {destacados.length > 0 && (
                <section className={cn(contenedor, 'mt-20')}>
                    <Titulo icono={Clock} titulo="Recién publicados" subtitulo="Los últimos modelos con foto en nuestras tiendas" enlace="/catalogo" />
                    <div className="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 lg:grid-cols-4">
                        {destacados.map((p, n) => <ProductoCard key={p.id} producto={p} indice={n} />)}
                    </div>
                </section>
            )}

            {/* Marcas */}
            {marcas.length > 0 && (
                <section className="mt-20 border-y bg-neutral-50 py-12">
                    <div className={contenedor}><Titulo titulo="Nuestras marcas" enlace="/marcas" icono={BadgeCheck} /></div>
                    <div className="relative overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_6%,black_94%,transparent)]">
                        <div className="marquee-track flex w-max py-2" style={{ animationDuration: `${Math.max(marcas.length * 7, 80)}s` }}>
                            {[0, 1].map((copia) => (
                                <div key={copia} className="flex shrink-0 gap-4 pr-4" aria-hidden={copia === 1}>
                                    {marcas.map((m) => (
                                        <Link key={m.marca} href={`/catalogo?marca=${encodeURIComponent(m.marca)}`} tabIndex={copia ? -1 : 0}
                                            className="group flex h-24 w-44 items-center justify-center rounded-2xl bg-white ring-1 ring-neutral-200 transition-transform duration-500 hover:-translate-y-1 hover:ring-ink">
                                            <LogoMarca marca={m.marca} logo={m.logo} carga="eager" />
                                        </Link>
                                    ))}
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            <CarruselProductos titulo="Ofertas" subtitulo="Precios especiales por tiempo limitado" enlace="/catalogo?categoria=ofertas" productos={ofertas} icono={Percent} />
            <CarruselProductos titulo="Lo más nuevo" subtitulo="Recién llegados a nuestras tiendas" enlace="/catalogo" productos={novedades} icono={PackagePlus} />

            {/* Cómo comprar */}
            <section className={cn(contenedor, 'mt-24')}>
                <div className="relative overflow-hidden rounded-3xl bg-ink p-6 text-white sm:p-10">
                    <div className="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-brand/30 blur-3xl" />
                    <h2 className="relative font-display text-2xl uppercase sm:text-3xl">Así de fácil es comprar</h2>
                    <div className="relative mt-8 grid gap-4 md:grid-cols-3">
                        {[
                            { icono: Search, t: 'Elige tus tenis', d: 'Explora el catálogo y escoge tu talla.' },
                            { icono: ShoppingBag, t: 'Agrégalos al carrito', d: 'Junta todos los modelos que te interesan.' },
                            { icono: MessageCircle, t: 'Envíanos tu pedido', d: 'Te confirmamos disponibilidad por WhatsApp.' },
                        ].map(({ icono: Icono, t, d }, n) => (
                            <div key={t} className="flex gap-4 rounded-2xl bg-white/5 p-5 ring-1 ring-white/10 transition-colors hover:bg-white/10">
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand font-display text-white">{n + 1}</span>
                                <div>
                                    <h3 className="flex items-center gap-2 font-semibold"><Icono className="h-4 w-4 text-brand" />{t}</h3>
                                    <p className="mt-1 text-sm text-neutral-400">{d}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                    <Button asChild className="relative mt-8 h-12 rounded-full bg-white px-7 font-semibold text-ink hover:bg-brand hover:text-white">
                        <Link href="/catalogo">Empezar a comprar <ArrowRight className="h-4 w-4" /></Link>
                    </Button>
                </div>
            </section>
        </Layout>
    );
}
