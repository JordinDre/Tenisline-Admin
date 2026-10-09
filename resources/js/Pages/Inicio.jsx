import LogoMarca from '@/Components/tienda/LogoMarca';
import ProductoCard from '@/Components/tienda/ProductoCard';
import Layout from '@/Layouts/Layout';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ChevronLeft, ChevronRight, MessageCircle, ShoppingBag, Sparkles } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

function Hero({ promociones }) {
    const [actual, setActual] = useState(0);
    const toque = useRef(null);
    const total = promociones.length;

    useEffect(() => {
        if (total < 2) return;
        const t = setInterval(() => setActual((a) => (a + 1) % total), 6000);
        return () => clearInterval(t);
    }, [total]);

    if (!total) {
        return (
            <section className="relative overflow-hidden bg-ink text-white">
                <div className="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand/30 blur-3xl" />
                <div className="absolute -bottom-32 left-1/3 h-80 w-80 rounded-full bg-brand/20 blur-3xl" />
                <div className="relative mx-auto flex max-w-7xl flex-col items-start gap-6 px-4 py-20 sm:px-6 md:py-28">
                    <span className="inline-flex animate-fade-up items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest">
                        <Sparkles className="h-3.5 w-3.5 text-brand" /> Tenis 100% originales
                    </span>
                    <h1 className="max-w-3xl animate-fade-up font-display text-5xl uppercase leading-[0.95] sm:text-6xl md:text-7xl" style={{ animationDelay: '80ms' }}>
                        No box, <span className="text-brand">sí precio.</span>
                    </h1>
                    <p className="max-w-xl animate-fade-up text-lg text-neutral-300" style={{ animationDelay: '160ms' }}>
                        Las mejores marcas para correr, entrenar y salir, a precios que no vas a encontrar en otro lado.
                    </p>
                    <div className="flex animate-fade-up flex-wrap gap-3" style={{ animationDelay: '240ms' }}>
                        <Link href="/catalogo" className="inline-flex items-center gap-2 rounded-full bg-brand px-7 py-3.5 font-semibold text-white transition hover:bg-brand-dark">
                            Ver catálogo <ArrowRight className="h-4 w-4" />
                        </Link>
                        <Link href="/catalogo?categoria=ofertas" className="rounded-full border border-white/30 px-7 py-3.5 font-semibold transition hover:bg-white hover:text-ink">
                            Ofertas
                        </Link>
                    </div>
                </div>
            </section>
        );
    }

    const ir = (n) => setActual((n + total) % total);

    return (
        <section
            className="relative overflow-hidden bg-ink"
            onTouchStart={(e) => (toque.current = e.touches[0].clientX)}
            onTouchEnd={(e) => {
                if (toque.current === null) return;
                const dx = e.changedTouches[0].clientX - toque.current;
                if (Math.abs(dx) > 40) ir(actual + (dx < 0 ? 1 : -1));
                toque.current = null;
            }}
        >
            <div className="flex transition-transform duration-700 ease-out" style={{ transform: `translateX(-${actual * 100}%)` }}>
                {promociones.map((p, n) => {
                    const Contenido = (
                        <div className="relative h-[70vh] max-h-[720px] min-h-[420px] w-full shrink-0">
                            {p.imagen && (
                                <picture>
                                    {p.imagen_movil && <source media="(max-width: 767px)" srcSet={p.imagen_movil} />}
                                    <img src={p.imagen} alt={p.titulo || 'Promoción Tenisline'} className="absolute inset-0 h-full w-full object-cover" loading={n === 0 ? 'eager' : 'lazy'} />
                                </picture>
                            )}
                            {(p.titulo || p.subtitulo || p.boton) && (
                                <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent">
                                    <div className="mx-auto flex h-full max-w-7xl flex-col justify-end px-4 pb-16 sm:px-6">
                                        {p.titulo && <h2 className="max-w-3xl font-display text-4xl uppercase leading-none text-white sm:text-6xl">{p.titulo}</h2>}
                                        {p.subtitulo && <p className="mt-4 max-w-xl text-lg text-white/85">{p.subtitulo}</p>}
                                        {p.boton && p.enlace && (
                                            <span className="mt-6 inline-flex w-fit items-center gap-2 rounded-full bg-white px-7 py-3.5 font-semibold text-ink transition hover:bg-brand hover:text-white">
                                                {p.boton} <ArrowRight className="h-4 w-4" />
                                            </span>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    );
                    return p.enlace ? (
                        <Link key={n} href={p.enlace} className="w-full shrink-0">{Contenido}</Link>
                    ) : (
                        <div key={n} className="w-full shrink-0">{Contenido}</div>
                    );
                })}
            </div>

            {total > 1 && (
                <>
                    <button onClick={() => ir(actual - 1)} className="absolute left-4 top-1/2 hidden -translate-y-1/2 rounded-full bg-white/80 p-3 backdrop-blur transition hover:bg-white md:block" aria-label="Anterior">
                        <ChevronLeft className="h-5 w-5" />
                    </button>
                    <button onClick={() => ir(actual + 1)} className="absolute right-4 top-1/2 hidden -translate-y-1/2 rounded-full bg-white/80 p-3 backdrop-blur transition hover:bg-white md:block" aria-label="Siguiente">
                        <ChevronRight className="h-5 w-5" />
                    </button>
                    <div className="absolute bottom-5 left-1/2 flex -translate-x-1/2 gap-2">
                        {promociones.map((_, n) => (
                            <button key={n} onClick={() => ir(n)} aria-label={`Promoción ${n + 1}`}
                                className={`h-1.5 rounded-full transition-all ${n === actual ? 'w-8 bg-white' : 'w-3 bg-white/50'}`} />
                        ))}
                    </div>
                </>
            )}
        </section>
    );
}

function Titulo({ titulo, subtitulo, enlace, texto = 'Ver todo' }) {
    return (
        <div className="mb-8 flex items-end justify-between gap-4">
            <div>
                <h2 className="font-display text-3xl uppercase leading-none sm:text-4xl">{titulo}</h2>
                {subtitulo && <p className="mt-2 text-neutral-500">{subtitulo}</p>}
            </div>
            {enlace && (
                <Link href={enlace} className="group hidden shrink-0 items-center gap-1 text-sm font-semibold sm:flex">
                    {texto} <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                </Link>
            )}
        </div>
    );
}

const ESTILO_CATEGORIA = {
    dama: 'from-rose-100 to-orange-50',
    caballero: 'from-neutral-200 to-neutral-50',
    nino: 'from-sky-100 to-cyan-50',
    infante: 'from-amber-100 to-yellow-50',
    ofertas: 'from-brand to-orange-400 text-white',
};

export default function Inicio({ promociones = [], categorias = [], marcas = [], ofertas = [], novedades = [] }) {
    return (
        <Layout>
            <Head title="Tenis originales" />
            <Hero promociones={promociones} />

            {/* Categorías */}
            <section className="mx-auto max-w-7xl px-4 pt-16 sm:px-6">
                <Titulo titulo="Compra por categoría" />
                <div className="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-5">
                    {categorias.map((c, n) => (
                        <Link
                            key={c.clave}
                            href={`/catalogo?categoria=${c.clave}`}
                            className={`group relative flex aspect-[4/3] animate-fade-up flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-br p-5 transition-transform hover:-translate-y-1 md:aspect-[3/4] ${ESTILO_CATEGORIA[c.clave] ?? 'from-neutral-100 to-white'} ${n === categorias.length - 1 && categorias.length % 2 ? 'col-span-2 aspect-[2.4/1] md:col-span-1 md:aspect-[3/4]' : ''}`}
                            style={{ animationDelay: `${n * 60}ms` }}
                        >
                            <span className="text-sm font-semibold opacity-70">{c.modelos} modelos</span>
                            <div className="flex items-end justify-between">
                                <h3 className="font-display text-2xl uppercase leading-none sm:text-3xl">{c.label}</h3>
                                <ArrowRight className="h-6 w-6 transition-transform group-hover:translate-x-1" />
                            </div>
                        </Link>
                    ))}
                </div>
            </section>

            {/* Marcas */}
            {marcas.length > 0 && (
                <section className="mt-20 border-y bg-neutral-50 py-10">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6">
                        <Titulo titulo="Nuestras marcas" enlace="/marcas" />
                    </div>
                    <div className="group relative overflow-hidden">
                        <div className="flex w-max animate-marquee gap-4 group-hover:[animation-play-state:paused]">
                            {[...marcas, ...marcas].map((m, n) => (
                                <Link
                                    key={`${m.marca}-${n}`}
                                    href={`/catalogo?marca=${encodeURIComponent(m.marca)}`}
                                    className="flex h-24 w-44 shrink-0 items-center justify-center rounded-2xl bg-white px-6 ring-1 ring-neutral-200 grayscale transition hover:grayscale-0 hover:ring-neutral-900"
                                >
                                    <LogoMarca marca={m.marca} logo={m.logo} className="h-9" />
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* Ofertas */}
            {ofertas.length > 0 && (
                <section className="mx-auto mt-20 max-w-7xl px-4 sm:px-6">
                    <Titulo titulo="Ofertas" subtitulo="Precios especiales por tiempo limitado" enlace="/catalogo?categoria=ofertas" />
                    <div className="-mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-4 sm:mx-0 sm:px-0 [&::-webkit-scrollbar]:hidden">
                        {ofertas.map((p, n) => (
                            <div key={p.id} className="w-[46%] shrink-0 snap-start sm:w-[31%] lg:w-[23%]">
                                <ProductoCard producto={p} indice={n} />
                            </div>
                        ))}
                    </div>
                </section>
            )}

            {/* Novedades */}
            <section className="mx-auto mt-20 max-w-7xl px-4 sm:px-6">
                <Titulo titulo="Lo más nuevo" subtitulo="Recién llegados a nuestras tiendas" enlace="/catalogo" texto="Ver catálogo" />
                <div className="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 lg:grid-cols-4">
                    {novedades.map((p, n) => (
                        <ProductoCard key={p.id} producto={p} indice={n} />
                    ))}
                </div>
                <div className="mt-12 text-center">
                    <Link href="/catalogo" className="inline-flex items-center gap-2 rounded-full bg-ink px-8 py-4 font-semibold text-white transition hover:bg-neutral-800">
                        Ver todo el catálogo <ArrowRight className="h-4 w-4" />
                    </Link>
                </div>
            </section>

            {/* Cómo comprar */}
            <section className="mx-auto mt-24 max-w-7xl px-4 sm:px-6">
                <div className="grid gap-4 rounded-3xl bg-neutral-100 p-6 sm:p-10 md:grid-cols-3">
                    {[
                        { icono: Sparkles, t: 'Elige tus tenis', d: 'Explora el catálogo y escoge tu talla.' },
                        { icono: ShoppingBag, t: 'Agrégalos al carrito', d: 'Junta todos los modelos que te interesan.' },
                        { icono: MessageCircle, t: 'Envíanos tu pedido', d: 'Te confirmamos disponibilidad por WhatsApp.' },
                    ].map(({ icono: Icono, t, d }, n) => (
                        <div key={t} className="flex gap-4 rounded-2xl bg-white p-6">
                            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-ink font-display text-white">{n + 1}</span>
                            <div>
                                <h3 className="flex items-center gap-2 font-semibold"><Icono className="h-4 w-4 text-brand" />{t}</h3>
                                <p className="mt-1 text-sm text-neutral-500">{d}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </section>
        </Layout>
    );
}
