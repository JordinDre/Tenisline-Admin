import LogoMarca from '@/Components/tienda/LogoMarca';
import ProductoCard from '@/Components/tienda/ProductoCard';
import { Button } from '@/Components/ui/button';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from '@/Components/ui/carousel';
import Layout from '@/Layouts/Layout';
import { cn } from '@/lib/utils';
import { Head, Link } from '@inertiajs/react';
import Autoplay from 'embla-carousel-autoplay';
import { ArrowUpRight } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

function Hero({ promociones }) {
    const autoplay = useRef(Autoplay({ delay: 6000, stopOnInteraction: true }));
    const [api, setApi] = useState(null);
    const [actual, setActual] = useState(0);

    useEffect(() => {
        if (!api) return;
        const sync = () => setActual(api.selectedScrollSnap());
        sync();
        api.on('select', sync);
        return () => api.off('select', sync);
    }, [api]);

    if (!promociones.length) {
        return (
            <section className="mx-auto max-w-[1600px] px-4 pt-6 sm:px-6">
                <div className="relative flex min-h-[520px] items-end overflow-hidden bg-neutral-100 p-8 sm:p-14 md:min-h-[620px]">
                    <img src="/images/logo.png" alt="" aria-hidden="true"
                        className="pointer-events-none absolute -right-10 top-1/2 w-[85%] max-w-[900px] -translate-y-1/2 opacity-[0.07] mix-blend-multiply md:right-0 md:w-[60%]" />
                    <div className="relative max-w-2xl animate-in fade-in slide-in-from-bottom-4 duration-700">
                        <h1 className="font-display text-6xl font-extrabold uppercase leading-[0.9] tracking-tight sm:text-7xl md:text-8xl">
                            No box,<br />sí precio
                        </h1>
                        <p className="mt-5 max-w-md text-[17px] text-neutral-600">
                            Nike, adidas, Puma, New Balance, On, Hoka y más, a precios que no vas a encontrar en otro lado.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Button asChild size="lg" className="h-12 rounded-full px-7 text-[15px]"><Link href="/catalogo">Comprar</Link></Button>
                            <Button asChild size="lg" variant="outline" className="h-12 rounded-full border-black bg-transparent px-7 text-[15px] hover:bg-black hover:text-white"><Link href="/catalogo?categoria=ofertas">Ver ofertas</Link></Button>
                        </div>
                    </div>
                </div>
            </section>
        );
    }

    return (
        <section className="mx-auto max-w-[1600px] px-4 pt-6 sm:px-6">
            <Carousel setApi={setApi} opts={{ loop: true }} plugins={[autoplay.current]} className="group">
                <CarouselContent className="-ml-0">
                    {promociones.map((p, n) => {
                        const tarjeta = (
                            <div className="relative h-[72vh] max-h-[760px] min-h-[460px] overflow-hidden bg-neutral-200">
                                {p.imagen && (
                                    <picture>
                                        {p.imagen_movil && <source media="(max-width: 767px)" srcSet={p.imagen_movil} />}
                                        <img src={p.imagen} alt={p.titulo || 'Promoción Tenisline'} loading={n === 0 ? 'eager' : 'lazy'}
                                            className="absolute inset-0 h-full w-full object-cover" />
                                    </picture>
                                )}
                                {(p.titulo || p.subtitulo || p.boton) && (
                                    <div className="absolute inset-0 flex items-end bg-gradient-to-t from-black/55 via-black/10 to-transparent p-8 sm:p-14">
                                        <div className="max-w-2xl text-white">
                                            {p.titulo && <h2 className="font-display text-5xl font-extrabold uppercase leading-[0.9] tracking-tight sm:text-7xl">{p.titulo}</h2>}
                                            {p.subtitulo && <p className="mt-4 max-w-lg text-[17px] text-white/90">{p.subtitulo}</p>}
                                            {p.boton && p.enlace && (
                                                <span className="mt-7 inline-flex h-12 items-center rounded-full bg-white px-7 text-[15px] font-medium text-black transition-colors hover:bg-neutral-200">{p.boton}</span>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        );
                        return (
                            <CarouselItem key={n} className="pl-0">
                                {p.enlace ? <Link href={p.enlace}>{tarjeta}</Link> : tarjeta}
                            </CarouselItem>
                        );
                    })}
                </CarouselContent>
                {promociones.length > 1 && (
                    <>
                        <CarouselPrevious className="left-4 hidden h-11 w-11 border-0 bg-white/90 opacity-0 transition-opacity group-hover:opacity-100 md:flex" />
                        <CarouselNext className="right-4 hidden h-11 w-11 border-0 bg-white/90 opacity-0 transition-opacity group-hover:opacity-100 md:flex" />
                        <div className="absolute bottom-5 right-6 flex gap-1.5">
                            {promociones.map((_, n) => (
                                <button key={n} onClick={() => api?.scrollTo(n)} aria-label={`Promoción ${n + 1}`}
                                    className={cn('h-1 rounded-full transition-all', n === actual ? 'w-8 bg-white' : 'w-4 bg-white/50')} />
                            ))}
                        </div>
                    </>
                )}
            </Carousel>
        </section>
    );
}

function Encabezado({ titulo, enlace, texto = 'Ver todo', children }) {
    return (
        <div className="mb-6 flex items-end justify-between gap-4">
            <h2 className="text-2xl font-medium tracking-tight sm:text-[28px]">{titulo}</h2>
            <div className="flex items-center gap-3">
                {enlace && <Link href={enlace} className="text-[15px] font-medium underline-offset-4 hover:underline">{texto}</Link>}
                {children}
            </div>
        </div>
    );
}

function CarruselProductos({ titulo, enlace, productos }) {
    if (!productos.length) return null;
    return (
        <section className="mx-auto mt-20 max-w-[1600px] px-4 sm:px-6">
            <Carousel opts={{ align: 'start', dragFree: true }}>
                <Encabezado titulo={titulo} enlace={enlace}>
                    <div className="relative hidden h-11 w-24 sm:block">
                        <CarouselPrevious className="static h-11 w-11 translate-y-0 border-0 bg-neutral-100 hover:bg-neutral-200" />
                        <CarouselNext className="absolute right-0 top-0 h-11 w-11 translate-y-0 border-0 bg-neutral-100 hover:bg-neutral-200" />
                    </div>
                </Encabezado>
                <CarouselContent className="-ml-3">
                    {productos.map((p, n) => (
                        <CarouselItem key={p.id} className="basis-[72%] pl-3 sm:basis-1/2 md:basis-1/3 lg:basis-1/4">
                            <ProductoCard producto={p} indice={n} />
                        </CarouselItem>
                    ))}
                </CarouselContent>
            </Carousel>
        </section>
    );
}

const FONDOS = {
    dama: 'bg-[#f3e9e4]',
    caballero: 'bg-[#e7e9ec]',
    nino: 'bg-[#e4eef3]',
    infante: 'bg-[#f3efe2]',
    ofertas: 'bg-red-600 text-white',
};

export default function Inicio({ promociones = [], categorias = [], marcas = [], ofertas = [], novedades = [] }) {
    return (
        <Layout>
            <Head title="Inicio" />
            <Hero promociones={promociones} />

            {/* Categorías */}
            <section className="mx-auto mt-16 max-w-[1600px] px-4 sm:px-6">
                <Encabezado titulo="Compra por categoría" />
                <div className="grid grid-cols-2 gap-3 md:grid-cols-5">
                    {categorias.map((c, n) => (
                        <Link
                            key={c.clave}
                            href={`/catalogo?categoria=${c.clave}`}
                            className={cn(
                                'group relative flex aspect-square animate-in flex-col justify-between overflow-hidden p-5 fade-in fill-mode-both duration-500 sm:p-6 md:aspect-[3/4]',
                                FONDOS[c.clave] ?? 'bg-neutral-100',
                                n === categorias.length - 1 && categorias.length % 2 === 1 && 'col-span-2 aspect-[2/1] md:col-span-1 md:aspect-[3/4]',
                            )}
                            style={{ animationDelay: `${n * 60}ms` }}
                        >
                            <ArrowUpRight className="h-6 w-6 self-end transition-transform duration-300 group-hover:-translate-y-1 group-hover:translate-x-1" />
                            <div>
                                <h3 className="font-display text-3xl font-extrabold uppercase tracking-tight sm:text-4xl">{c.label}</h3>
                                <p className="mt-1 text-sm opacity-70">{c.modelos} modelos</p>
                            </div>
                        </Link>
                    ))}
                </div>
            </section>

            <CarruselProductos titulo="Lo más nuevo" enlace="/catalogo" productos={novedades} />
            <CarruselProductos titulo="Ofertas" enlace="/catalogo?categoria=ofertas" productos={ofertas} />

            {/* Marcas */}
            {marcas.length > 0 && (
                <section className="mx-auto mt-20 max-w-[1600px] px-4 sm:px-6">
                    <Encabezado titulo="Compra por marca" enlace="/marcas" />
                    <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-8">
                        {marcas.slice(0, 16).map((m) => (
                            <Link key={m.marca} href={`/catalogo?marca=${encodeURIComponent(m.marca)}`}
                                className="group flex aspect-[4/3] items-center justify-center bg-neutral-100 p-4 transition-colors hover:bg-neutral-200">
                                <LogoMarca marca={m.marca} logo={m.logo} className="h-8 w-20 transition-transform duration-300 group-hover:scale-110 sm:h-9 sm:w-24" />
                            </Link>
                        ))}
                    </div>
                </section>
            )}

            {/* Cómo comprar */}
            <section className="mx-auto mt-20 max-w-[1600px] px-4 sm:px-6">
                <div className="grid gap-px overflow-hidden bg-neutral-200 md:grid-cols-3">
                    {[
                        ['01', 'Elige tus tenis', 'Explora el catálogo y escoge tu talla.'],
                        ['02', 'Agrégalos a tu bolsa', 'Junta todos los modelos que te interesan.'],
                        ['03', 'Envíanos tu pedido', 'Te confirmamos disponibilidad y forma de pago por WhatsApp.'],
                    ].map(([n, t, d]) => (
                        <div key={n} className="bg-white p-8">
                            <span className="font-display text-sm font-bold text-neutral-400">{n}</span>
                            <h3 className="mt-3 text-xl font-medium tracking-tight">{t}</h3>
                            <p className="mt-1 text-[15px] text-neutral-500">{d}</p>
                        </div>
                    ))}
                </div>
            </section>
        </Layout>
    );
}
